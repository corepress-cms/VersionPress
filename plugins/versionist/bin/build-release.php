<?php

/**
 * Builds the release ZIP into dist/: copies the plugin (minus .distignore), installs production
 * dependencies, prefixes their namespaces with Strauss and regenerates the autoloader.
 *
 * Runs inside the wp-env `wordpress` container, which has the right PHP and Composer:
 *   php bin/build-release.php <version>
 */

declare(strict_types=1);

const STRAUSS_VERSION = '0.31.1';

$pluginDir = dirname(__DIR__);
$version = preg_replace('/^v(?=\d)/', '', $argv[1] ?? 'dev'); // WordPress versions have no "v" prefix
$distDir = "$pluginDir/dist";
$stageDir = "$distDir/versionist";

function step(string $message): void
{
    fwrite(STDOUT, "\n> $message\n");
}

function run(string $command, string $cwd): void
{
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $cwd);
    if (!is_resource($process) || proc_close($process) !== 0) {
        fwrite(STDERR, "Command failed: $command\n");
        exit(1);
    }
}

function removeDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

/** @return string[] Paths relative to the plugin directory, starting with "/" */
function readDistignore(string $file): array
{
    $lines = array_map('trim', file($file));
    return array_values(array_filter($lines, fn($line) => $line !== '' && $line[0] !== '#'));
}

function isIgnored(string $relativePath, array $ignored): bool
{
    foreach ($ignored as $pattern) {
        if ($relativePath === $pattern || str_starts_with($relativePath, rtrim($pattern, '/') . '/')) {
            return true;
        }
    }
    return false;
}

step('Clean dist/');
removeDirectory($stageDir);
@mkdir($stageDir, 0755, true);

step('Copy plugin files');
$ignored = readDistignore("$pluginDir/.distignore");
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pluginDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($files as $file) {
    $relativePath = substr($file->getPathname(), strlen($pluginDir));
    if (isIgnored($relativePath, $ignored)) {
        continue;
    }
    $target = $stageDir . $relativePath;
    $file->isDir() ? @mkdir($target, 0755, true) : copy($file->getPathname(), $target);
}

if (!is_file("$stageDir/admin/public/build/index.js")) {
    fwrite(STDERR, "admin/public/build is missing; build the admin UI first (pnpm run build:admin).\n");
    exit(1);
}

step('Install production dependencies');
copy("$pluginDir/composer.lock", "$stageDir/composer.lock");
run('composer install --no-dev --no-interaction --no-progress --no-scripts --no-plugins', $stageDir);

step('Prefix dependencies with Strauss');
$composerJson = json_decode(file_get_contents("$stageDir/composer.json"), true);
$composerJson['extra']['strauss']['update_call_sites'] = true; // rewrite references in our code too
file_put_contents("$stageDir/composer.json", json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$strauss = sys_get_temp_dir() . '/strauss-' . STRAUSS_VERSION . '.phar';
if (!is_file($strauss)) {
    $url = 'https://github.com/BrianHenryIE/strauss/releases/download/' . STRAUSS_VERSION . '/strauss.phar';
    $phar = file_get_contents($url);
    if ($phar === false) {
        fwrite(STDERR, "Could not download $url\n");
        exit(1);
    }
    file_put_contents($strauss, $phar);
}
run('php -d memory_limit=1G ' . escapeshellarg($strauss), $stageDir);

step('Remove development files of the dependencies');
// Packages keep their license and composer.json; tests, tools and CI configs are not needed at runtime.
foreach (glob("$stageDir/vendor-prefixed/*/*", GLOB_ONLYDIR) as $packageDir) {
    foreach (['tests', 'test', 'bin', 'docs', '.github'] as $dir) {
        removeDirectory("$packageDir/$dir");
    }
    foreach (['phpunit.xml*', 'phpstan*.neon*', 'psalm*.xml', 'phpcs.xml*', '.php-cs-fixer*', 'Makefile'] as $pattern) {
        array_map('unlink', glob("$packageDir/$pattern"));
    }
}

step('Regenerate the autoloader without the unprefixed packages');
// Only Versionist itself is autoloaded by Composer; prefixed dependencies come from vendor-prefixed/.
$composerJson['require'] = array_filter(
    $composerJson['require'],
    fn($package) => $package === 'php' || str_starts_with($package, 'ext-'),
    ARRAY_FILTER_USE_KEY
);
unset($composerJson['require-dev'], $composerJson['autoload-dev'], $composerJson['extra']['strauss']);
$composerJson['autoload']['files'][] = 'vendor-prefixed/autoload.php';
file_put_contents("$stageDir/composer.json", json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
removeDirectory("$stageDir/vendor");
run('composer dump-autoload --no-dev --optimize --no-interaction --no-plugins', $stageDir);
unlink("$stageDir/composer.json");
unlink("$stageDir/composer.lock");

step("Set version $version");
$mainFile = "$stageDir/versionist.php";
file_put_contents(
    $mainFile,
    preg_replace('/^( \* Version:\s+).*$/m', '${1}' . $version, file_get_contents($mainFile), 1)
);

step('Create ZIP');
$zipPath = "$distDir/versionist-$version.zip";
@unlink($zipPath);
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE);
$stageFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($stageDir, FilesystemIterator::SKIP_DOTS)
);
foreach ($stageFiles as $file) {
    $zip->addFile($file->getPathname(), 'versionist' . substr($file->getPathname(), strlen($stageDir)));
}
$zip->close();

fwrite(STDOUT, "\nBuild ready: dist/" . basename($zipPath) . "\n");
