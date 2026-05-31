# Universal Production Standard for Every ARE Plugin

Release Status: 1.0.0 Production Readiness Standard  
Applies To: All Algonquian Real Estate WordPress plugins  
Author Standard: Onegodian | Algonquian Real Estate

## Required in Every Plugin

Every plugin must include the following before a production ZIP is generated:

1. Plugin Bootstrap
2. Activation Hook
3. Automatic Page Generation
4. Shortcodes
5. Admin Menu
6. Capabilities
7. Nonces
8. Input Sanitization
9. Output Escaping
10. README
11. Documentation
12. Branding Assets
13. Changelog
14. Uninstall Cleanup

## Plugin Bootstrap

Each plugin must include one root bootstrap file named after the plugin slug.

Example:

```text
algq-document-library/algq-document-library.php
```

Minimum WordPress header:

```php
<?php
/**
 * Plugin Name: Algonquian Document Library
 * Description: Centralized institutional document library and package generation system for Algonquian Real Estate.
 * Version: 1.0.0
 * Author: Onegodian
 * Text Domain: algq-document-library
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */
```

The bootstrap file must block direct access:

```php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

## Activation Hook

Every plugin must register an activation hook:

```php
register_activation_hook( __FILE__, array( 'ALGQ_Plugin_Activator', 'activate' ) );
```

Activation must:

- Store plugin version.
- Create required options.
- Create database tables if needed.
- Create required roles or capabilities.
- Generate required pages.
- Flush rewrite rules.

## Automatic Page Generation

Every plugin must create its own pages on activation and insert the correct shortcode automatically.

Pages must not be duplicated. Existing pages should be reused by slug where possible.

Generated content must be WPBakery-safe:

```text
[vc_row][vc_column][vc_column_text]
[algq_shortcode]
[/vc_column_text][/vc_column][/vc_row]
```

Never use HTML closing tags such as `</vc_column_text>` for WPBakery shortcode content.

## Required Page Pattern

Each plugin should create at least:

| Page Type | Route Pattern | Purpose |
|---|---|---|
| Overview | /plugin/{plugin-slug} | Product-style overview |
| Getting Started | /plugin/{plugin-slug}/start | Setup steps |
| Documentation | /plugin/{plugin-slug}/docs | User and admin documentation |
| Operational Page | Plugin-specific route | Live operating interface |

## Shortcodes

Every plugin must register public-facing and/or internal shortcodes.

Shortcode callbacks must return buffered HTML. Do not echo directly from shortcode callbacks.

Correct pattern:

```php
public static function render_shortcode() {
    ob_start();
    include ALGQ_PLUGIN_DIR . 'templates/main.php';
    return ob_get_clean();
}
```

## Admin Menu

Every plugin must register an admin menu or submenu.

Admin pages must use capability checks:

```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have permission to access this page.', 'algq-plugin' ) );
}
```

## Capabilities

Every plugin must define the minimum capability needed to use its admin functions.

Recommended shared capability:

```text
manage_algq_platform
```

Recommended role/capability model:

- Administrator: full access
- ARE Administrator: full ARE plugin access
- ARE Acquisition Manager: deal and pipeline access
- ARE Underwriter: underwriting and offer access
- ARE Buyer: buyer portal access only
- ARE Lender: limited document/package access

## Nonces

Every form and state-changing action must use a nonce.

Form:

```php
wp_nonce_field( 'algq_save_settings', 'algq_nonce' );
```

Validation:

```php
check_admin_referer( 'algq_save_settings', 'algq_nonce' );
```

AJAX/REST requests must validate a nonce before changing data.

## Input Sanitization

Every value from `$_GET`, `$_POST`, `$_REQUEST`, REST input, AJAX input, or uploaded metadata must be sanitized.

Use:

```php
sanitize_text_field()
sanitize_email()
sanitize_textarea_field()
esc_url_raw()
absint()
floatval()
wp_kses_post()
```

Never store raw request input.

## Output Escaping

Every dynamic output must be escaped.

Use:

```php
esc_html()
esc_attr()
esc_url()
wp_kses_post()
```

Never print unescaped database or request values.

## README

Every plugin must include `README.md` with:

- Plugin name
- Version
- Purpose
- Installation steps
- Generated pages
- Shortcodes
- Admin settings
- Capabilities
- Integrations
- Release status

## Documentation

Every plugin must include a `docs/` directory with at least:

```text
docs/overview.md
docs/getting-started.md
docs/shortcodes.md
docs/admin.md
docs/security.md
```

Where relevant, also include:

```text
docs/api.md
docs/workflow.md
docs/formulas.md
docs/database.md
```

## Branding Assets

Every plugin must include branded assets:

```text
assets/images/icon.png
assets/images/banner.png
assets/css/admin.css
assets/css/frontend.css
assets/js/admin.js
assets/js/frontend.js
```

Branding should follow the Algonquian institutional black, white, and gold visual system.

## Changelog

Every plugin must include `CHANGELOG.md`.

Minimum entry:

```text
# Changelog

## 1.0.0
- Initial production release.
- Added plugin bootstrap.
- Added activation hook.
- Added automatic page generation.
- Added shortcodes.
- Added admin menu.
- Added security hardening.
- Added uninstall cleanup.
```

## Uninstall Cleanup

Every plugin must include `uninstall.php`.

Minimum requirements:

- Block direct access.
- Only run when `WP_UNINSTALL_PLUGIN` is defined.
- Remove plugin-specific options.
- Remove scheduled events.
- Preserve business records by default unless a delete-data option is explicitly enabled.

Example:

```php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'algq_plugin_version' );
```

## Database Standards

If a plugin creates custom tables, it must use `dbDelta()` and maintain a database version option.

```php
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta( $sql );
update_option( 'algq_plugin_db_version', '1.0.0' );
```

## ZIP Packaging Gate

Do not generate a production ZIP unless all required files exist:

```text
plugin-bootstrap.php
includes/class-activator.php
includes/class-page-generator.php
includes/class-shortcodes.php
includes/class-admin.php
README.md
CHANGELOG.md
uninstall.php
docs/
assets/css/
assets/js/
assets/images/
```

## Production Approval Rule

A plugin is production-ready only when it can be installed, activated, and used without manual page creation.

Activation must produce:

- Required WordPress pages
- Correct WPBakery shortcode wrappers
- Registered shortcodes
- Admin menu access
- Stored version option
- No fatal errors
