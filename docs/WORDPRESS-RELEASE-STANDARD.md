# Algonquian WordPress Plugin Release Standard

**Authority:** Algonquian Real Estate Technology Division  
**Parent entity:** Algonquian Real Estate LLC  
**Effective date:** 2026-07-26  
**Applies to:** Every plugin under `plugins/`

## Canonical product position

Algonquian Real Estate LLC is a Connecticut real estate acquisition and technology company organized on February 11, 2026. ARE Tech is its internal technology division. Plugin descriptions must accurately present the software as operational infrastructure for acquisition, underwriting, pipeline management, offer preparation, funding relationships, buyer access, documents, automation, commerce, reporting, and system oversight.

Plugins must not claim completed acquisitions, revenue, investor returns, legal advice, fiduciary authority, or regulated professional services unless separately documented and authorized.

## Required plugin header

Every installable plugin must declare:

- Plugin Name
- Description
- Version using semantic versioning
- Author: `Onegodian | Algonquian Real Estate`
- Plugin URI and Author URI
- Text Domain matching the plugin slug
- Requires at least: WordPress 6.5
- Requires PHP: 8.1
- License: GPL-2.0-or-later

A production ZIP may not use an `rc`, `beta`, or `dev` version suffix.

## Required package structure

Each directory in `plugins/{plugin-slug}/` must contain:

```text
{plugin-slug}.php
README.md
CHANGELOG.md
SECURITY.md
uninstall.php
includes/
assets/
```

Additional directories may include `admin/`, `public/`, `templates/`, `languages/`, `tests/`, and `docs/`.

The ZIP root must be exactly one plugin directory. WordPress must be able to install it through **Plugins → Add New → Upload Plugin** without repacking.

## Bootstrap and lifecycle

Every plugin must:

1. Block direct PHP access.
2. Verify minimum WordPress and PHP versions.
3. Register activation and deactivation hooks from the main plugin file.
4. Run versioned database migrations only during controlled activation or upgrade routines.
5. Load translations.
6. Validate required dependencies before initializing operational services.
7. Fail safely with an administrator notice rather than a fatal error.
8. Preserve authoritative records when deactivated.
9. Use conservative, explicit opt-in uninstall cleanup.

## Platform authority and dependencies

The Algonquian Real Estate Platform Plugin owns shared infrastructure:

- plugin registry and dependency health;
- roles and capabilities;
- shared UI components and design tokens;
- centralized audit service;
- Algonquian Mail Gateway;
- private file service;
- generated-page service;
- platform health checks;
- shared API contracts.

Operational plugins own only their documented domain records. They may not duplicate authoritative deal, underwriting, offer, document, signature, automation, or audit records.

Required dependencies must be declared and checked. Optional integrations—including WooCommerce and Stripe—must degrade gracefully when unavailable.

## Security gate

Every state-changing action must include:

- a granular capability check;
- nonce verification;
- server-side validation;
- sanitization before storage;
- prepared database queries;
- context-appropriate escaping on output;
- an audit event for material changes.

Public forms additionally require rate limiting, honeypot or CAPTCHA integration, upload MIME and size controls, consent capture, anti-enumeration behavior, and private attachment storage.

Private downloads, reports, buyer records, lender records, seller records, stewardship photographs, and signature evidence require record-level authorization.

No plugin may log credentials, full authentication tokens, document bodies, signatures, bank account numbers, or unnecessary personal data.

## Shared interface standard

All plugins must use the platform design system:

- navy, gold, teal, white, and neutral design tokens;
- shared page header, cards, KPI cards, forms, tables, badges, alerts, tabs, timelines, empty states, and health indicators;
- consistent action labels and status vocabulary;
- responsive behavior;
- keyboard navigation, visible focus states, semantic headings, accessible labels, and sufficient contrast.

Plugin-specific CSS may extend but must not replace the shared design language.

## Generated pages and WPBakery

Generated pages must be idempotent, preserve administrator-edited content, store generated page IDs, and avoid duplicates.

WPBakery text blocks must always use:

```text
[vc_column_text]
Content or plugin shortcode
[/vc_column_text]
```

Never use `</vc_column_text>`.

Every plugin should provide, where applicable:

- Overview
- Getting Started
- Documentation
- Settings or operational workspace
- Health status

## Documentation gate

Every package must document:

- purpose and authoritative responsibility;
- installation and activation;
- generated pages and shortcodes;
- roles and capabilities;
- settings;
- integrations and dependencies;
- data ownership and storage;
- security controls;
- hooks, filters, and REST routes;
- uninstall behavior;
- troubleshooting;
- version history.

## Export and report gate

CSV exports must mitigate spreadsheet formula injection and apply the current user's record-level permissions. PDF reports must be generated and delivered through protected temporary storage. Export actions require a nonce, `export_algq_reports` or an approved equivalent capability, and an audit event.

## Commerce integrations

WooCommerce and Stripe widgets must be read-only dashboard integrations unless a documented command explicitly delegates a change to the owning integration. Secret keys must never be stored in widget settings, exports, or logs.

## Production test matrix

A plugin may be labeled production-ready only after evidence exists for:

- clean installation on a current WordPress release;
- activation and reactivation;
- dependency unavailable and degraded-mode behavior;
- database migration and upgrade behavior;
- generated-page idempotency;
- shortcode rendering;
- admin navigation;
- capability and nonce enforcement;
- REST and AJAX permission callbacks;
- input validation and output escaping;
- private file authorization;
- mail delivery and failure handling where applicable;
- uninstall preservation and explicit cleanup;
- PHP 8.1, 8.2, and 8.3 syntax checks;
- WordPress coding standards review;
- responsive and accessibility review;
- WooCommerce compatibility where applicable;
- WPBakery rendering where applicable;
- end-to-end workflow integration.

The displayed version number alone is not evidence of production readiness.
