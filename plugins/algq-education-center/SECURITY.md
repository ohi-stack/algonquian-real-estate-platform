# Security Policy

## Supported Versions

| Version | Supported |
|---|---|
| 1.0.0-rc1 | Yes |

## Security Scope

This policy applies to the Algonquian Education Center WordPress plugin located at:

```text
plugins/algq-education-center
```

The plugin includes LMS functionality, course and lesson records, guide records, learning-progress tracking, WooCommerce-linked access control, shortcode rendering, automatic page generation, and administrative settings.

## Reporting a Security Issue

Report suspected vulnerabilities privately to the repository owner or project maintainer. Do not disclose exploit details publicly until the issue has been reviewed, patched, and released.

Recommended report contents:

- Affected file or component
- Steps to reproduce
- Expected result
- Actual result
- WordPress version
- PHP version
- WooCommerce version, if applicable
- Any relevant screenshots or logs

## Security Controls Implemented

The plugin includes the following baseline controls:

- Direct file access protection using `ABSPATH` checks
- Nonce verification for AJAX learning-progress actions
- Nonce verification for metadata save operations
- Capability checks for admin pages and post metadata operations
- Settings sanitization through the WordPress Settings API
- Input sanitization using WordPress helpers such as `sanitize_text_field`, `sanitize_key`, `absint`, and `esc_url_raw`
- Output escaping using `esc_html`, `esc_attr`, `esc_url`, and `wp_kses_post`
- Prepared SQL queries for progress reads and summaries
- WooCommerce purchase verification through WooCommerce APIs when available
- Optional uninstall cleanup controlled by an explicit option

## Administrative Hardening

Production deployments should follow these rules:

- Limit `manage_options` access to trusted administrators only.
- Limit course, lesson, and guide editing to trusted staff.
- Keep WordPress, PHP, WooCommerce, and all plugins updated.
- Use HTTPS on all public and administrative pages.
- Use strong administrator passwords and multi-factor authentication where available.
- Review user roles before enabling buyer, lender, paid, internal, or admin access levels.
- Test WooCommerce product gating on staging before selling premium education access.

## Data Handling

The plugin stores learning-progress records in:

```text
wp_algq_learning_progress
```

Stored fields include user ID, course ID, lesson ID, completion status, and timestamps. The plugin does not intentionally store payment data. Payment and order records remain under WooCommerce control.

## Uninstall Behavior

By default, uninstall removes plugin options and transients. Full content and table removal only occurs if:

```text
algq_education_delete_data_on_uninstall
```

is enabled.

## Developer Standards

Future development should preserve these requirements:

- All shortcode callbacks must return buffered HTML.
- All admin forms must use nonces.
- All write actions must verify capabilities.
- All input must be sanitized before storage.
- All output must be escaped before rendering.
- SQL must use `$wpdb->prepare()` unless the query is static and contains no user input.
- WooCommerce access checks must use WooCommerce APIs rather than direct order-table assumptions.

## Disclosure Policy

Security fixes should be prioritized before feature work. Public release notes may describe the affected component and remediation, but should not include exploit instructions.
