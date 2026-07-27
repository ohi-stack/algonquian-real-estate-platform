# Algonquian Real Estate Plugin Package Import Manifest

## Repository

`ohi-stack/algonquian-real-estate-platform`

## Canonical-source rule

Extracted source under `plugins/{plugin-slug}/` is the source of record. ZIP files are import or release artifacts only. Production packages must be rebuilt from the reviewed canonical directories and must pass the automated and manual release gates.

## Source artifacts received

The project workspace has included the following package artifacts:

1. `Algonquian-Real-Estate-Platform-v1.0.0.zip`
2. `algonquian-real-estate-plugin-suite.zip`
3. `algonquian-real-estate-plugin-suite-updated.zip`
4. `algq-automation-engine-1.0.0-production.zip`
5. `algq-buyer-portal-1.0.0.zip`
6. `algq-deal-intake-1.0.2-rc.2.zip`
7. `algq-deal-marketplace-1.0.0.zip`
8. `algq-digital-store-1.0.0.zip`
9. `algq-document-library-1.0.0.zip`
10. `algq-pdf-signature-1.0.0.zip`
11. `algq-pipeline-crm-1.0.0-production.zip`
12. `algq-woocommerce-bridge-1.0.0-rc3-dashboard-branding.zip`

Bundle ZIP files may contain products not represented by separate source artifacts. No artifact should be called production-ready solely because its filename contains `production` or `1.0.0`.

## Expected product inventory

The canonical repository should maintain and package the complete ecosystem:

- `algq-platform`
- `algq-deal-intake`
- `algq-pipeline-crm`
- `algq-mao-engine`
- `algq-offer-generator`
- `algq-funding-tracker`
- `algq-buyer-portal`
- `algq-deal-marketplace`
- `algq-document-library`
- `algq-pdf-signature`
- `algq-automation-engine`
- `algq-command-center`
- `algq-digital-store`
- `algq-digital-products`
- `algq-woocommerce-bridge`

A missing canonical directory is a release blocker even when a corresponding ZIP exists elsewhere.

## Required product information

Every plugin must consistently identify:

- Parent organization: Algonquian Real Estate LLC
- Technology division: Algonquian Real Estate Technology Division (ARE Tech)
- Author: Onegodian | Algonquian Real Estate
- Primary market context: Connecticut
- Product purpose and authoritative data responsibility
- Dependencies and optional integrations
- Stable semantic version
- Minimum supported WordPress and PHP versions
- Generated pages, shortcodes, capabilities, REST routes, scheduled jobs, and owned tables

Descriptions must not overstate company revenue, acquisitions, portfolio ownership, investor returns, legal authority, fiduciary authority, or regulated professional services.

## Universal WordPress release gate

Each canonical package must pass:

- valid WordPress plugin header;
- stable `MAJOR.MINOR.PATCH` version;
- direct-access protection;
- controlled activation, upgrade, deactivation, and uninstall behavior;
- dependency validation and nonfatal degraded mode;
- database migration controls;
- automatic idempotent page generation where applicable;
- registered and documented shortcodes;
- admin navigation and settings where applicable;
- granular capabilities;
- nonce validation;
- server-side validation and sanitization;
- prepared SQL;
- context-appropriate escaping;
- private-file and record-level authorization;
- audit events for material operations;
- centralized mail integration;
- shared interface and accessibility standards;
- `README.md`;
- `CHANGELOG.md`;
- `SECURITY.md`;
- `uninstall.php`;
- PHP 8.1, 8.2, and 8.3 syntax validation;
- clean WordPress upload, installation, activation, reactivation, and uninstall testing;
- end-to-end integration testing.

The full requirements are defined in `docs/WORDPRESS-RELEASE-STANDARD.md`.

## Packaging

Run:

```bash
php build/validate-wordpress-plugins.php
bash build/package-wordpress-plugins.sh
```

The packaging script creates one WordPress-installable ZIP per validated plugin and generates `releases/SHA256SUMS.txt`. GitHub Actions runs the same gate and exposes the release packages as a workflow artifact.

## WPBakery rule

All generated content must use:

```text
[vc_column_text]
[plugin_shortcode]
[/vc_column_text]
```

The malformed closing tag `</vc_column_text>` is prohibited and causes release validation to fail.

## Release-status rule

Until the validator and the complete manual WordPress test matrix pass, packages must be described as **production hardening** or **release candidate**, not final production releases.
