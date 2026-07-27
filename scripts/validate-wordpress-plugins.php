<?php
/**
 * Validate Algonquian WordPress plugin source directories before packaging.
 *
 * Usage:
 *   php scripts/validate-wordpress-plugins.php [plugin-root]
 */

declare(strict_types=1);

$repositoryRoot = dirname(__DIR__);
$pluginRoot = $argv[1] ?? $repositoryRoot . '/plugins';
$manifestPath = $repositoryRoot . '/config/plugin-manifest.json';

if (!is_file($manifestPath)) {
    fwrite(STDERR, "Missing manifest: {$manifestPath}\n");
    exit(2);
}

$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
$failures = [];
$warnings = [];

if (!is_dir($pluginRoot)) {
    fwrite(STDERR, "Plugin source directory does not exist: {$pluginRoot}\n");
    exit(2);
}

$requiredFiles = $manifest['release_requirements']['required_files'] ?? [];
$requiredHeaders = $manifest['release_requirements']['required_header_fields'] ?? [];

foreach ($manifest['plugins'] as $plugin) {
    $slug = (string) $plugin['slug'];

    if ($slug === 'algonquian-real-estate-platform') {
        $candidateDirectories = [
            $pluginRoot . '/' . $slug,
            $repositoryRoot . '/plugin',
        ];
    } else {
        $candidateDirectories = [
            $pluginRoot . '/' . $slug,
            $repositoryRoot . '/modules/' . preg_replace('/^algq-/', '', $slug),
        ];
    }

    $directory = null;
    foreach ($candidateDirectories as $candidate) {
        if (is_dir($candidate)) {
            $directory = $candidate;
            break;
        }
    }

    if ($directory === null) {
        $message = "{$slug}: source directory not found";
        if (!empty($plugin['required'])) {
            $failures[] = $message;
        } else {
            $warnings[] = $message;
        }
        continue;
    }

    $entryFile = $directory . '/' . $plugin['expected_entry_file'];
    if (!is_file($entryFile)) {
        $phpFiles = glob($directory . '/*.php') ?: [];
        if (count($phpFiles) === 1) {
            $entryFile = $phpFiles[0];
            $warnings[] = "{$slug}: expected entry filename differs; found " . basename($entryFile);
        } else {
            $failures[] = "{$slug}: missing unambiguous plugin entry file";
            continue;
        }
    }

    $entryContents = (string) file_get_contents($entryFile);
    foreach ($requiredHeaders as $header) {
        if (!preg_match('/^[ \t\/*#@]*' . preg_quote($header, '/') . '\s*:/mi', $entryContents)) {
            $failures[] = "{$slug}: missing plugin header '{$header}'";
        }
    }

    if (!str_contains($entryContents, "defined( 'ABSPATH' )") && !str_contains($entryContents, 'defined(\'ABSPATH\')')) {
        $failures[] = "{$slug}: missing direct-access guard";
    }

    foreach ($requiredFiles as $requiredFile) {
        if (!is_file($directory . '/' . $requiredFile)) {
            $failures[] = "{$slug}: missing {$requiredFile}";
        }
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $path = $fileInfo->getPathname();
        $extension = strtolower($fileInfo->getExtension());

        if ($extension === 'php') {
            $command = sprintf('php -l %s 2>&1', escapeshellarg($path));
            exec($command, $output, $status);
            if ($status !== 0) {
                $failures[] = "{$slug}: PHP syntax failure in {$path}: " . implode(' ', $output);
            }
            $output = [];
        }

        if (in_array($extension, ['php', 'txt', 'md', 'html'], true)) {
            $contents = (string) file_get_contents($path);
            if (str_contains($contents, '</vc_column_text>')) {
                $failures[] = "{$slug}: malformed WPBakery closing tag in {$path}";
            }
            if (preg_match('/(password|secret|api[_-]?key)\s*[=:>]\s*[\'\"][^\'\"]{8,}[\'\"]/i', $contents)) {
                $warnings[] = "{$slug}: inspect possible embedded credential in {$path}";
            }
        }
    }

    echo "PASS source scan: {$slug}\n";
}

foreach ($warnings as $warning) {
    fwrite(STDERR, "WARNING: {$warning}\n");
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    fwrite(STDERR, sprintf("Validation failed with %d blocking issue(s).\n", count($failures)));
    exit(1);
}

echo "All discovered plugin packages passed the static installation-readiness gate.\n";
exit(0);
