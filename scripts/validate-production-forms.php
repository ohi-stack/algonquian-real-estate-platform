<?php
/**
 * Static production-form gate for the Algonquian Real Estate plugin suite.
 *
 * This gate does not replace staging/end-to-end tests. It prevents known unsafe
 * form patterns and unresolved placeholders from being promoted as a release.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$scanRoots = array_filter([
    $root . '/plugin',
    $root . '/plugins',
    $root . '/modules',
], 'is_dir');

$failures = [];
$warnings = [];
$packages = [];

$placeholderPatterns = [
    'FORM_PLUGIN_SHORTCODE',
    'YOUR_FORM_PLUGIN_SHORTCODE_HERE',
    '[algq_seller_intake_entry]',
    '[ocp_dashboard role=',
];

foreach ($scanRoots as $scanRoot) {
    $children = basename($scanRoot) === 'plugin'
        ? [$scanRoot]
        : array_values(array_filter(glob($scanRoot . '/*') ?: [], 'is_dir'));

    foreach ($children as $packageDir) {
        $phpFiles = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($packageDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'php') {
                $phpFiles[] = $fileInfo->getPathname();
            }
        }
        if ($phpFiles === []) {
            continue;
        }

        $source = '';
        foreach ($phpFiles as $file) {
            $contents = (string) file_get_contents($file);
            $source .= "\n/* FILE: {$file} */\n" . $contents;

            foreach ($placeholderPatterns as $placeholder) {
                if (str_contains($contents, $placeholder)) {
                    $failures[] = basename($packageDir) . ": unresolved production placeholder '{$placeholder}' in {$file}";
                }
            }
        }

        $hasPostForm = (bool) preg_match('/<form\b[^>]*method\s*=\s*[\'\"]post[\'\"]/i', $source);
        $hasAdminPostMutation = str_contains($source, 'admin_post_');
        $hasPublicMutation = str_contains($source, 'admin_post_nopriv_');
        $hasRestMutation = (str_contains($source, 'WP_REST_Server::CREATABLE') || str_contains($source, "'POST'") || str_contains($source, '"POST"'))
            && str_contains($source, 'register_rest_route');

        if (!$hasPostForm && !$hasAdminPostMutation && !$hasRestMutation) {
            continue;
        }

        $package = basename($packageDir);
        $packages[] = $package;

        $hasNonceOutput = (bool) preg_match('/\b(wp_nonce_field|wp_create_nonce)\s*\(/', $source);
        $hasNonceCheck = (bool) preg_match('/\b(check_admin_referer|check_ajax_referer|wp_verify_nonce)\s*\(/', $source);
        $hasSanitization = (bool) preg_match('/\b(sanitize_[a-z0-9_]+|absint|intval|floatval|wp_kses|wp_unslash)\s*\(/i', $source);
        $hasEscaping = (bool) preg_match('/\b(esc_html|esc_attr|esc_url|esc_textarea|wp_kses_post)\s*\(/', $source);
        $hasCapabilityOrAuth = (bool) preg_match('/\b(current_user_can|is_user_logged_in|permission_callback|wc_get_checkout_url)\b/', $source);
        $hasAudit = (bool) preg_match('/\b(algq_log_event|algq_audit_event|[A-Za-z0-9_]*Audit_Log::(?:log|record)|do_action\s*\(\s*[\'\"]algq_[^\'\"]*(created|submitted|updated|failed|completed))/', $source);
        $hasErrorHandling = (bool) preg_match('/\b(WP_Error|is_wp_error|wp_die|redirect_error|wc_add_notice|throw\s+new)\b/', $source);

        if ($hasPostForm && (!$hasNonceOutput || !$hasNonceCheck)) {
            $failures[] = "{$package}: POST form package must render and verify a WordPress nonce";
        }
        if (($hasAdminPostMutation || $hasRestMutation) && !$hasSanitization) {
            $failures[] = "{$package}: mutating form/API package does not show server-side sanitization";
        }
        if ($hasPostForm && !$hasEscaping) {
            $failures[] = "{$package}: form package does not show output escaping";
        }
        if (($hasAdminPostMutation || $hasRestMutation) && !$hasCapabilityOrAuth) {
            $failures[] = "{$package}: mutating form/API package does not show capability, authentication, or delegated checkout authorization";
        }
        if (($hasAdminPostMutation || $hasRestMutation) && !$hasErrorHandling) {
            $failures[] = "{$package}: mutating form/API package does not show explicit error handling";
        }
        if (($hasAdminPostMutation || $hasRestMutation) && !$hasAudit) {
            $failures[] = "{$package}: material form/API mutations must emit a durable audit event";
        }

        if ($hasPublicMutation) {
            $hasAbuseControl = (bool) preg_match('/\b(rate[_-]?limit|throttl|honeypot|captcha|turnstile|recaptcha|started_at|minimum[_-]?submit|transient)\b/i', $source);
            if (!$hasAbuseControl) {
                $failures[] = "{$package}: public unauthenticated mutation must include rate limiting, bot controls, or equivalent abuse protection";
            }
        }

        if (preg_match('/type\s*=\s*[\'\"]file[\'\"]/i', $source)) {
            $hasUploadValidation = (bool) preg_match('/\b(wp_check_filetype_and_ext|wp_check_filetype|media_handle_upload|wp_handle_upload|mime|filesize|hash_file)\b/i', $source);
            if (!$hasUploadValidation) {
                $failures[] = "{$package}: file-upload form lacks visible MIME/type/size/integrity validation controls";
            }
        }
    }
}

foreach (array_unique($packages) as $package) {
    echo "PASS form source scan: {$package}\n";
}
foreach ($warnings as $warning) {
    fwrite(STDERR, "WARNING: {$warning}\n");
}
if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    fwrite(STDERR, sprintf("Production form validation failed with %d blocking issue(s).\n", count($failures)));
    exit(1);
}

echo "All discovered form-owning packages passed the static production-form gate with no unresolved security warnings.\n";
exit(0);
