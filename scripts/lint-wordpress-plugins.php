<?php
/**
 * PHP syntax lint for all manifest-discovered Algonquian plugin source trees.
 */

declare(strict_types=1);

$repositoryRoot = dirname(__DIR__);
$manifestPath = $repositoryRoot . '/config/plugin-manifest.json';

if (!is_file($manifestPath)) {
    fwrite(STDERR, "Missing manifest: {$manifestPath}\n");
    exit(2);
}

try {
    $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Manifest JSON error: ' . $exception->getMessage() . "\n");
    exit(2);
}

$failures = [];
$warnings = [];
$linted = 0;

foreach ((array) ($manifest['plugins'] ?? []) as $plugin) {
    $slug = (string) ($plugin['slug'] ?? 'unknown-plugin');
    $candidates = (array) ($plugin['source_candidates'] ?? []);
    if ($candidates === []) {
        $candidates = [
            'plugins/' . $slug,
            $slug === 'algonquian-real-estate-platform' ? 'plugin' : 'modules/' . preg_replace('/^algq-/', '', $slug),
        ];
    }

    $directory = null;
    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate, '/');
        if ($candidate !== '' && is_dir($repositoryRoot . '/' . $candidate)) {
            $directory = $repositoryRoot . '/' . $candidate;
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

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'php') {
            continue;
        }

        ++$linted;
        $path = $fileInfo->getPathname();
        $output = [];
        $status = 0;
        exec(sprintf('php -l %s 2>&1', escapeshellarg($path)), $output, $status);
        if ($status !== 0) {
            $failures[] = "{$slug}: {$path}: " . implode(' ', $output);
        }
    }
}

foreach ($warnings as $warning) {
    fwrite(STDERR, "WARNING: {$warning}\n");
}

if ($linted === 0) {
    $failures[] = 'No PHP files were discovered from manifest source candidates.';
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    fwrite(STDERR, sprintf("PHP lint failed with %d blocking issue(s).\n", count($failures)));
    exit(1);
}

echo "PHP lint passed for {$linted} files across manifest-discovered plugin sources.\n";
exit(0);
