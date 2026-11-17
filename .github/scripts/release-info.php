<?php
/**
 * Checks that a release is consistent and prints its details for the
 * Release workflow.
 *
 * Usage: php release-info.php [tag] [notes-file]
 *
 * Without a tag, the tag is derived from plugin.json ("v" . version).
 * Fails when:
 * - plugin.json, the DataExport.php header and the tag disagree on the version,
 * - CHANGELOG.md has no entry for the version,
 * - the Matomo range in plugin.json doesn't match the release line
 *   (1.x = Matomo 5, 2.x = Matomo 6, and so on: Matomo major = plugin major + 4).
 *
 * Writes the CHANGELOG entry to notes-file and prints version=, tag= and
 * matomo= lines for $GITHUB_OUTPUT.
 */

$root = dirname(__DIR__, 2);
$errors = [];

$plugin = json_decode(file_get_contents($root . '/plugin.json'), true);
$version = $plugin['version'] ?? '';
if (!preg_match('/^(\d+)\.\d+\.\d+(-[0-9A-Za-z.]+)?$/', $version, $m)) {
    fwrite(STDERR, "plugin.json has no valid version (found '$version').\n");
    exit(1);
}
$major = (int) $m[1];

$tag = $argv[1] ?? '';
if ($tag === '') {
    $tag = 'v' . $version;
} elseif (ltrim($tag, 'v') !== $version) {
    $errors[] = "Tag $tag does not match version $version in plugin.json. Update plugin.json or tag v$version.";
}

$header = file_get_contents($root . '/DataExport.php');
if (preg_match('/^\s*\*\s*Version:\s*(\S+)/mi', $header, $hm) && $hm[1] !== $version) {
    $errors[] = "DataExport.php says Version: {$hm[1]}, plugin.json says $version.";
}

$range = $plugin['require']['matomo'] ?? '';
$matomoMajor = $major + 4;
if (!preg_match('/^>=\s*' . $matomoMajor . '\.\d+\.\d+(-\w+)?\s*,\s*<\s*' . ($matomoMajor + 1) . '\.0\.0(-b1)?$/', $range)) {
    $errors[] = "Version $version belongs to the $major.x line for Matomo $matomoMajor, but plugin.json requires matomo \"$range\". "
        . "Release $major.x from the branch for Matomo $matomoMajor.";
}

// CHANGELOG.md entries start with a line that is just the version, e.g. "1.2.0"
$notes = null;
$lines = file($root . '/CHANGELOG.md', FILE_IGNORE_NEW_LINES);
foreach ($lines as $i => $line) {
    if (trim($line) === $version || preg_match('/^#+\s*v?' . preg_quote($version, '/') . '\b/', trim($line))) {
        $notes = [];
        for ($j = $i + 1; $j < count($lines); $j++) {
            if (preg_match('/^(#+\s*)?v?\d+\.\d+\.\d+(-[0-9A-Za-z.]+)?\s*$/', trim($lines[$j]))) {
                break;
            }
            $notes[] = $lines[$j];
        }
        $notes = trim(implode("\n", $notes));
        break;
    }
}
if ($notes === null || $notes === '') {
    $errors[] = "CHANGELOG.md has no entry for $version.";
}

if ($errors) {
    foreach ($errors as $error) {
        echo "::error::$error\n";
    }
    exit(1);
}

$notesFile = $argv[2] ?? '';
if ($notesFile !== '') {
    $body = $notes . "\n\n"
        . "Requires Matomo " . str_replace(',', ', ', $range) . ".";
    file_put_contents($notesFile, $body . "\n");
}

echo "version=$version\n";
echo "tag=$tag\n";
echo "major=$major\n";
echo "matomo=$matomoMajor\n";
