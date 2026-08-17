<?php
/**
 * Validate Algonquian WordPress plugin source directories before packaging.
 *
 * Usage:
 *   php scripts/validate-wordpress-plugins.php
 */

declare(strict_types=1);

$repositoryRoot = dirname(__DIR__);
$manifestPath   = $repositoryRoot . '/config/plugin-manifest.json';

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
$requirements = (array) ($manifest['release_requirements'] ?? []);
$requiredFiles = (array) ($requirements['required_root_files'] ?? $requirements['required_files'] ?? ['README.md', 'CHANGELOG.md', 'SECURITY.md', 'uninstall.php']);
$requiredHeaders = (array) ($requirements['required_header_fields'] ?? ['Plugin Name', 'Description', 'Version', 'Author', 'Text Domain', 'Requires at least', 'Requires PHP', 'License']);

$resolveDirectory = static function (array $plugin) use ($repositoryRoot): ?string {
    $slug = (string) ($plugin['slug'] ?? '');
    $candidates = (array) ($plugin['source_candidates'] ?? []);
    if ($candidates === []) {
        $candidates = [
            'plugins/' . $slug,
            $slug === 'algonquian-real-estate-platform' ? 'plugin' : 'modules/' . preg_replace('/^algq-/', '', $slug),
        ];
    }

    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate, '/');
        if ($candidate === '') {
            continue;
        }
        $directory = $repositoryRoot . '/' . $candidate;
        if (is_dir($directory)) {
            return $directory;
        }
    }
    return null;
};

foreach ((array) ($manifest['plugins'] ?? []) as $plugin) {
    $slug = (string) ($plugin['slug'] ?? 'unknown-plugin');
    $directory = $resolveDirectory((array) $plugin);

    if ($directory === null) {
        $message = "{$slug}: source directory not found";
        if (!empty($plugin['required'])) {
            $failures[] = $message;
        } else {
            $warnings[] = $message;
        }
        continue;
    }

    $entryFile = null;
    $entryCandidates = (array) ($plugin['main_file_candidates'] ?? []);
    if ($entryCandidates === [] && !empty($plugin['expected_entry_file'])) {
        $entryCandidates[] = (string) $plugin['expected_entry_file'];
    }

    foreach ($entryCandidates as $candidate) {
        $candidatePath = $directory . '/' . ltrim((string) $candidate, '/');
        if (is_file($candidatePath)) {
            $entryFile = $candidatePath;
            break;
        }
    }

    if ($entryFile === null) {
        $rootPhpFiles = glob($directory . '/*.php') ?: [];
        $pluginHeaders = [];
        foreach ($rootPhpFiles as $phpFile) {
            $contents = (string) file_get_contents($phpFile);
            if (preg_match('/^[ \t\/*#@]*Plugin Name\s*:/mi', $contents)) {
                $pluginHeaders[] = $phpFile;
            }
        }
        if (count($pluginHeaders) === 1) {
            $entryFile = $pluginHeaders[0];
            $warnings[] = "{$slug}: manifest entry filename differs; discovered " . basename($entryFile);
        } else {
            $failures[] = "{$slug}: missing unambiguous plugin entry file";
            continue;
        }
    }

    $entryContents = (string) file_get_contents($entryFile);
    foreach ($requiredHeaders as $header) {
        if (!preg_match('/^[ \t\/*#@]*' . preg_quote((string) $header, '/') . '\s*:/mi', $entryContents)) {
            $failures[] = "{$slug}: missing plugin header '{$header}'";
        }
    }

    if (!preg_match('/defined\s*\(\s*[\'\"]ABSPATH[\'\"]\s*\)/', $entryContents)) {
        $failures[] = "{$slug}: missing direct-access ABSPATH guard";
    }

    $declaredVersion = null;
    if (preg_match('/^[ \t\/*#@]*Version\s*:\s*([^\r\n]+)/mi', $entryContents, $matches)) {
        $declaredVersion = trim((string) $matches[1]);
    }
    $sourceVersion = isset($plugin['source_version']) ? trim((string) $plugin['source_version']) : '';
    if ($sourceVersion !== '' && $declaredVersion !== null && $declaredVersion !== $sourceVersion) {
        $failures[] = "{$slug}: plugin header version {$declaredVersion} does not match manifest source_version {$sourceVersion}";
    }

    foreach ($requiredFiles as $requiredFile) {
        if (!is_file($directory . '/' . (string) $requiredFile)) {
            $failures[] = "{$slug}: missing {$requiredFile}";
        }
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $path = $fileInfo->getPathname();
        $extension = strtolower($fileInfo->getExtension());

        if ($extension === 'php') {
            $output = [];
            $status = 0;
            exec(sprintf('php -l %s 2>&1', escapeshellarg($path)), $output, $status);
            if ($status !== 0) {
                $failures[] = "{$slug}: PHP syntax failure in {$path}: " . implode(' ', $output);
            }
        }

        // Check executable/source content, not Markdown documentation that may intentionally cite invalid examples.
        if (in_array($extension, ['php', 'txt', 'html'], true)) {
            $contents = (string) file_get_contents($path);
            if (str_contains($contents, '</vc_column_text>')) {
                $failures[] = "{$slug}: malformed WPBakery closing tag in {$path}";
            }
            if (preg_match('/(password|secret|api[_-]?key)\s*[=:>]\s*[\'\"][^\'\"]{8,}[\'\"]/i', $contents)) {
                $warnings[] = "{$slug}: inspect possible embedded credential in {$path}";
            }
        }
    }

    echo "PASS source scan: {$slug} (" . basename($entryFile) . ")\n";
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
