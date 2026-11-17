<?php
/**
 * vendor/ is committed so the plugin works when installed from the Matomo
 * Marketplace. This check fails when composer.lock was updated without
 * running `composer install --no-dev` and committing vendor/ as well, or the
 * other way round.
 */

$root = dirname(__DIR__, 2);
$lock = json_decode(file_get_contents($root . '/composer.lock'), true);
$installed = json_decode(file_get_contents($root . '/vendor/composer/installed.json'), true);
$installed = $installed['packages'] ?? $installed; // Composer 2 / Composer 1 format

$expected = [];
foreach ($lock['packages'] as $package) {
    $expected[$package['name']] = $package['version'];
}
$actual = [];
foreach ($installed as $package) {
    $actual[$package['name']] = $package['version'];
}

$errors = [];
foreach ($expected as $name => $version) {
    if (!isset($actual[$name])) {
        $errors[] = "$name $version is in composer.lock but not in vendor/";
    } elseif ($actual[$name] !== $version) {
        $errors[] = "$name is $version in composer.lock but {$actual[$name]} in vendor/";
    } elseif (!is_dir($root . '/vendor/' . $name)) {
        $errors[] = "$name is listed in vendor/composer/installed.json but vendor/$name is missing";
    }
}
foreach (array_diff_key($actual, $expected) as $name => $version) {
    $errors[] = "$name $version is in vendor/ but not in composer.lock (dev dependency?)";
}
foreach (glob($root . '/vendor/*/*/.git', GLOB_ONLYDIR) ?: [] as $gitDir) {
    $errors[] = substr(dirname($gitDir), strlen($root) + 1) . ' contains a .git folder (install with --prefer-dist)';
}

if ($errors) {
    fwrite(STDERR, "vendor/ does not match composer.lock:\n  - " . implode("\n  - ", $errors) . "\n");
    fwrite(STDERR, "Run `composer install --no-dev --prefer-dist` and commit vendor/.\n");
    exit(1);
}
echo count($expected) . " packages in vendor/ match composer.lock\n";
