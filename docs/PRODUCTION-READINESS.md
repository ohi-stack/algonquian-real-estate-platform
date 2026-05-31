# Production Readiness Checklist

Release Status: 1.0.0 Release Candidate  
Owner: Onegodian | Algonquian Real Estate

## Pre-Zip Requirements

Before generating installable plugin zip files, confirm the following items for every Algonquian Real Estate plugin:

- Main plugin bootstrap file exists at the plugin root.
- WordPress plugin header is valid.
- Plugin version is set to `1.0.0-rc.1` or `1.0.0`.
- Author displays as `Onegodian | Algonquian Real Estate` where branding is required.
- Plugin blocks direct file access with `defined( 'ABSPATH' )` checks.
- Activation hook creates required options, tables, and default pages.
- Deactivation does not delete business records.
- Shortcodes return buffered HTML and do not echo directly.
- All user input is sanitized.
- All output is escaped.
- Admin forms include nonces.
- Admin handlers check `current_user_can( 'manage_options' )` or the plugin-specific capability.
- REST endpoints validate permission callbacks and nonces where applicable.
- Database calls use `$wpdb->prepare()` for dynamic values.
- No secret keys, API keys, or webhook secrets are committed.
- Missing external API credentials produce admin notices, not fatal errors.
- README states `Release Status: 1.0.0 Release Candidate`.
- CHANGELOG contains the release-candidate entry.
- Zip archive excludes `.git`, local caches, node_modules, tests not intended for production, and development screenshots.

## WordPress Compatibility

Minimum target:

- WordPress: 6.0+
- PHP: 7.4+
- MySQL/MariaDB compatible with standard WordPress installations

Recommended production environment:

- HTTPS enabled
- Pretty permalinks enabled
- WP_DEBUG disabled on public production
- Error logging enabled at the hosting layer
- Daily database backups
- File backup before plugin upgrades

## Algonquian Real Estate Core Pages

The platform should create or support these pages:

- Seller Intake: `[algq_seller_intake]`
- MAO Calculator: `[algq_mao_calculator]`
- Buyer Registration: `[algq_buyer_registration]`
- Admin Dashboard: `[algq_admin_dashboard]`

Additional monetization plugins should create their own pages with embedded shortcodes on activation.

## Production Go / No-Go

A plugin is ready to zip when:

1. It activates without fatal errors on a clean WordPress installation.
2. Admin screens load without PHP warnings.
3. Public shortcodes render without direct output.
4. Activation-generated pages appear correctly in WordPress Pages.
5. Forms reject invalid nonces.
6. Admin-only actions reject non-admin users.
7. Database tables are created with `dbDelta()`.
8. External integrations fail safely when credentials are absent.

## Branding Standard

Use the following public display standard unless a plugin requires a shorter WordPress header:

```text
By Onegodian | Algonquian Real Estate
```

Visual treatment:

- White background
- Black text
- Gold accent
- Institutional card layout
- Conservative real estate operating-company tone

## Deployment Notes

Generate the installable zip from the plugin directory itself, not the full repository root, unless the repository is a single-plugin repository.

Correct example:

```text
algonquian-real-estate-platform.zip
└── algonquian-real-estate-platform/
    ├── algonquian-real-estate-platform.php
    ├── includes/
    ├── templates/
    ├── assets/
    └── README.md
```

Incorrect example:

```text
repo-root.zip
└── plugin/
    └── algonquian-real-estate-platform.php
```

WordPress expects the primary plugin file inside the uploaded plugin folder.
