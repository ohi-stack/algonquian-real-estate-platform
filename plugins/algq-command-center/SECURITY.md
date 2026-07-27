# Security Policy

## Supported Release

The supported release line is `1.0.0-rc.x` while the plugin remains in production hardening. Security fixes should be applied to the current release-candidate branch and included in the next packaged build.

## Reporting a Vulnerability

Do not disclose suspected vulnerabilities in a public issue. Report them privately to the repository owner with:

- affected version;
- affected route, action, shortcode, or file;
- reproduction steps;
- required user role or capability;
- impact assessment;
- logs or screenshots with sensitive data removed.

## Security Requirements

The Command Center must enforce:

- direct-access protection on PHP files;
- granular capability checks on every administrative screen and action;
- nonce verification for all state-changing requests;
- server-side validation and sanitization;
- context-appropriate output escaping;
- prepared SQL queries;
- permission-aware report queries and exports;
- CSV formula-injection mitigation;
- protected PDF generation and download authorization;
- append-only audit events for material actions;
- safe handling of degraded or inactive companion plugins;
- no exposure of credentials, tokens, document bodies, personal financial data, or private seller and buyer records in logs.

## Export Security

CSV and PDF exports must:

- require `export_algq_reports` or the approved equivalent capability;
- verify a nonce;
- apply the current user's record-level permissions;
- exclude inaccessible fields and records;
- escape spreadsheet formula prefixes where applicable;
- avoid predictable public file URLs;
- delete temporary files after delivery or expiration;
- create an audit event recording report type, filters, user, and result.

## Integration Security

WooCommerce and Stripe widgets must use approved APIs and must not store secret keys in Command Center options or logs. Stripe webhook or API credentials belong in the platform integration layer or environment configuration.

The Command Center must treat data from other plugins as read-only unless a documented, capability-protected command explicitly invokes the owning plugin's public service.

## Data Retention

Deactivation must preserve operational records. Uninstall cleanup must be explicit and conservative. Shared platform data, authoritative deal records, audit records, and records owned by companion plugins must never be deleted by the Command Center uninstaller.

## Release Gate

A release must not be classified as production-ready until capability, nonce, export, report, integration, deactivation, reactivation, and uninstall tests pass and the results are documented.
