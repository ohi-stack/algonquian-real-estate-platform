<?php
/**
 * Validate every canonical plugin package before creating release ZIP files.
 *
 * Usage: php build/validate-wordpress-plugins.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$pluginsDirectory = $root . '/plugins';

if (!is_dir($pluginsDirectory)) {
    fwrite(STDERR, "Missing plugins directory: {$pluginsDirectory}\n");
    exit(1);
}

$requiredFiles = [
    'README.md',
    'CHANGELOG.md',
    'SECURITY.md',
    'uninstall.php',
];

$errors = [];
$warnings = [];
$validated = [];
$directories = glob($pluginsDirectory . '/*', GLOB_ONLYDIR) ?: [];
sort($directories);

foreach ($directories as $directory) {
    $slug = basename($directory);

    if (str_starts_with($slug, '.')) {
        continue;
    }

    $phpFiles = glob($directory . '/*.php') ?: [];
    $mainFile = null;

    foreach ($phpFiles as $phpFile) {
        $contents = (string) file_get_contents($phpFile);
        if (preg_match('/^\s*Plugin Name:\s*(.+)$/mi', $contents)) {
            $mainFile = $phpFile;
            break;
        }
    }

    if ($mainFile === null) {
        $errors[] = "{$slug}: no root PHP file with a WordPress Plugin Name header.";
        continue;
    }

    $mainContents = (string) file_get_contents($mainFile);
    $headers = [
        'Plugin Name',
        'Description',
        'Version',
        'Author',
        'Text Domain',
        'Requires at least',
        'Requires PHP',
        'License',
    ];

    foreach ($headers as $header) {
        if (!preg_match('/^\s*' . preg_quote($header, '/') . ':\s*\S.+$/mi', $mainContents)) {
            $errors[] = "{$slug}: missing or empty plugin header '{$header}'.";
        }
    }

    if (preg_match('/^\s*Version:\s*([^\r\n]+)/mi', $mainContents, $matches)) {
        $version = trim($matches[1]);
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $errors[] = "{$slug}: production version must be stable semantic versioning; found '{$version}'.";
        }
    }

    if (!str_contains($mainContents, "defined( 'ABSPATH' )") && !str_contains($mainContents, "defined('ABSPATH')")) {
        $errors[] = "{$slug}: main plugin file does not visibly block direct access with ABSPATH.";
    }

    if (!str_contains($mainContents, 'register_activation_hook')) {
        $errors[] = "{$slug}: activation hook is not registered in the main plugin file.";
    }

    foreach ($requiredFiles as $requiredFile) {
        if (!is_file($directory . '/' . $requiredFile)) {
            $errors[] = "{$slug}: missing {$requiredFile}.";
        }
    }

    foreach (['includes', 'assets'] as $requiredDirectory) {
        if (!is_dir($directory . '/' . $requiredDirectory)) {
            $warnings[] = "{$slug}: missing recommended {$requiredDirectory}/ directory.";
        }
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        $relativePath = substr($path, strlen($root) + 1);

        if ($file->getExtension() === 'php') {
            $command = sprintf('php -l %s 2>&1', escapeshellarg($path));
            exec($command, $output, $status);
            if ($status !== 0) {
                $errors[] = "{$relativePath}: PHP syntax check failed: " . implode(' ', $output);
            }
            $output = [];
        }

        if (in_array($file->getExtension(), ['php', 'md', 'txt'], true)) {
            $contents = (string) file_get_contents($path);
            if (str_contains($contents, '</vc_column_text>')) {
                $errors[] = "{$relativePath}: malformed WPBakery closing tag </vc_column_text>.";
            }
        }
    }

    $validated[] = $slug;
}

if ($warnings !== []) {
    fwrite(STDOUT, "Warnings:\n- " . implode("\n- ", $warnings) . "\n\n");
}

if ($errors !== []) {
    fwrite(STDERR, "Release validation failed:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

fwrite(STDOUT, 'Validated ' . count($validated) . " WordPress plugin packages:\n- " . implode("\n- ", $validated) . "\n");
exit(0);
