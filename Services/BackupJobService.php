<?php
/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport\Services;

use Piwik\CliMulti\CliPhp;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\DataExport\Services\Dump\MysqlCli;
use Psr\Log\LoggerInterface;

/**
 * Runs database backups started from the admin page in the background.
 *
 * Dumping a multi-GB database takes longer than web server and proxy
 * timeouts allow, so the request only starts the job and returns. The page
 * then polls the job status and downloads the file when it is ready.
 *
 * The job runs as `./console dataexport:backup-job` in a separate process
 * when exec() and a PHP CLI binary are available. Otherwise it runs in the
 * same PHP process after the response has been sent to the browser.
 *
 * Only one job runs at a time; its state is kept in a small JSON file in the
 * backup folder (hidden from the file list).
 */
class BackupJobService
{
    public const STATUS_FILE = '.dataexport-backup-job.json';
    public const LOG_FILE = '.dataexport-backup-job.log';

    public const STATE_RUNNING = 'running';
    public const STATE_DONE = 'done';
    public const STATE_FAILED = 'failed';

    /** A job that hasn't finished after this long is considered dead. */
    private const STALE_AFTER_SECONDS = 43200;

    private const COMPRESSIONS = ['none', 'gzip', 'zip', 'tar'];

    /** @var FileService */
    private $fileService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: StaticContainer::get(LoggerInterface::class);
        $this->fileService = new FileService($this->logger);
    }

    public static function isValidCompression($compression): bool
    {
        return in_array($compression, self::COMPRESSIONS, true);
    }

    /**
     * Current job status, or null if no backup was started from the UI yet.
     */
    public function getStatus(): ?array
    {
        $path = $this->getStatusPath();
        if (!is_file($path)) {
            return null;
        }
        $status = json_decode((string) @file_get_contents($path), true);
        if (!is_array($status)) {
            return null;
        }

        if (($status['state'] ?? null) === self::STATE_RUNNING && $this->isDead($status)) {
            $status['state'] = self::STATE_FAILED;
            $status['error'] = 'The backup process stopped unexpectedly. Check the Matomo logs, or run ./console db:dump.';
            $status['finishedAt'] = time();
            $this->writeStatus($status);
        }

        return $status;
    }

    public function isRunning(): bool
    {
        $status = $this->getStatus();
        return $status !== null && $status['state'] === self::STATE_RUNNING;
    }

    /**
     * Starts a backup job.
     *
     * @return bool true when the job runs in a separate process. false means
     *              the caller must call runAfterResponse() after sending its
     *              response, so the job runs in this process.
     * @throws \Exception if a job is already running
     */
    public function start(string $compression): bool
    {
        if (!self::isValidCompression($compression)) {
            throw new \Exception("Invalid compression '$compression'.");
        }
        if ($this->isRunning()) {
            throw new \Exception('A backup is already running.');
        }
        if ($this->fileService->ensure_directory_exists($this->fileService->getBackupDir()) !== true) {
            throw new \Exception('Failed to create backup directory.');
        }

        $jobId = bin2hex(random_bytes(8));
        $this->writeStatus([
            'id' => $jobId,
            'state' => self::STATE_RUNNING,
            'compression' => $compression,
            'startedAt' => time(),
            'pid' => null,
            'mode' => null,
        ]);

        $command = $this->getBackgroundCommand($jobId);
        if ($command !== null) {
            $this->logger->info('Starting background backup: ' . $command);
            exec($command, $output, $returnVar);
            if ($returnVar === 0) {
                $this->updateStatus($jobId, ['mode' => 'cli']);
                return true;
            }
            $this->logger->notice('Could not start the background backup process, running it in the web request instead.');
        }

        $this->updateStatus($jobId, ['mode' => 'web', 'pid' => getmypid()]);
        return false;
    }

    /**
     * Sends $body to the browser, closes the response and then runs the
     * started job in this process.
     */
    public function respondAndRun(string $body, string $contentType = 'application/json'): void
    {
        ignore_user_abort(true);
        @set_time_limit(0);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: ' . $contentType);
            header('Content-Length: ' . strlen($body));
            header('Connection: close');
        }
        echo $body;
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $status = $this->getStatus();
        if ($status !== null && $status['state'] === self::STATE_RUNNING) {
            $this->run($status['id']);
        }
    }

    /**
     * Runs the job with the given id (called by the console command or after the response).
     */
    public function run(string $jobId): bool
    {
        $status = $this->getStatus();
        if ($status === null || ($status['id'] ?? null) !== $jobId || $status['state'] !== self::STATE_RUNNING) {
            $this->logger->notice("Backup job $jobId is not pending, nothing to do.");
            return false;
        }

        $this->updateStatus($jobId, ['pid' => getmypid()]);

        try {
            $service = new DatabaseDumpService($this->logger);
            $path = $service->generateDump($status['compression']);
            $this->updateStatus($jobId, [
                'state' => self::STATE_DONE,
                'file' => basename($path),
                'size' => @filesize($path) ?: 0,
                'finishedAt' => time(),
            ]);
            $this->logger->info('Background backup finished: ' . $path);
            return true;
        } catch (\Throwable $e) {
            $this->updateStatus($jobId, [
                'state' => self::STATE_FAILED,
                'error' => $e->getMessage(),
                'finishedAt' => time(),
            ]);
            $this->logger->error('Background backup failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Shell command that starts the job detached, or null if that isn't possible.
     */
    private function getBackgroundCommand(string $jobId): ?string
    {
        if (!MysqlCli::canExec() || DIRECTORY_SEPARATOR === '\\') {
            return null;
        }

        $console = PIWIK_INCLUDE_PATH . '/console';
        if (!is_file($console)) {
            return null;
        }

        try {
            $php = (new CliPhp())->findPhpBinary();
        } catch (\Throwable $e) {
            $php = null;
        }
        if (empty($php)) {
            return null;
        }

        // Output goes to a hidden log next to the status file, for troubleshooting
        return sprintf(
            'nohup %s %s dataexport:backup-job %s > %s 2>&1 &',
            $php, // already a command, e.g. "/usr/bin/php8.3 -q" (as used by Matomo's CliMulti)
            escapeshellarg($console),
            escapeshellarg($jobId),
            escapeshellarg($this->fileService->getBackupDir() . self::LOG_FILE)
        );
    }

    private function isDead(array $status): bool
    {
        $startedAt = (int) ($status['startedAt'] ?? 0);
        if (time() - $startedAt > self::STALE_AFTER_SECONDS) {
            return true;
        }
        // The detached CLI process never picked the job up
        if (($status['mode'] ?? null) === 'cli' && empty($status['pid']) && time() - $startedAt > 120) {
            return true;
        }
        // A detached CLI process that no longer exists
        if (($status['mode'] ?? null) === 'cli' && !empty($status['pid']) && function_exists('posix_kill')) {
            return !@posix_kill((int) $status['pid'], 0) && posix_get_last_error() === 3; // ESRCH
        }
        return false;
    }

    private function getStatusPath(): string
    {
        return $this->fileService->getBackupDir() . self::STATUS_FILE;
    }

    private function updateStatus(string $jobId, array $changes): void
    {
        $status = $this->getStatus();
        if ($status === null || ($status['id'] ?? null) !== $jobId) {
            return;
        }
        $this->writeStatus(array_merge($status, $changes));
    }

    private function writeStatus(array $status): void
    {
        $path = $this->getStatusPath();
        $tmp = $path . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, json_encode($status)) === false || !@rename($tmp, $path)) {
            @unlink($tmp);
            throw new \Exception('Cannot write the backup status file in ' . dirname($path));
        }
    }
}
