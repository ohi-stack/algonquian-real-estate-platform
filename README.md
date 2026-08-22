# Algonquian Real Estate Platform

Enterprise WordPress platform for real estate acquisition, seller intake, canonical deal management, underwriting, offers, documents, signatures, automation, buyer access, capital relationships, property stewardship, commerce, reporting, and system administration.

## Organization

- **Parent entity:** Algonquian Real Estate LLC
- **Technology division:** Algonquian Real Estate Technology Division
- **Primary market:** Connecticut
- **Author display:** Onegodian | Algonquian Real Estate

Algonquian Real Estate LLC was organized as a Connecticut limited liability company on February 11, 2026.

## Release Status

**Production Candidate — acceptance testing required**

The repository contains production-directed source and release controls, but a plugin is not classified as production-ready solely because a ZIP or plugin header says `1.0.0`. Each package must pass the installation-readiness gate in `docs/wordpress-installation-readiness.md`.

## Protected Foundation

1. Algonquian Real Estate Platform Plugin
2. Algonquian Deal Intake
3. Algonquian Pipeline CRM
4. Algonquian MAO Engine
5. Algonquian Offer Generator
6. Algonquian Document Library
7. Algonquian PDF & Signature Engine
8. Algonquian Automation Engine
9. Algonquian Admin Command Center

The Platform Plugin owns shared bootstrap, registry, dependency checks, roles and capabilities, security utilities, mail delivery, audit logging, private file services, page generation, common UI, and health monitoring. Operational plugins retain authority over their own record types.

## Additional Platform Modules

- Algonquian Buyer Portal
- Algonquian Deal Marketplace
- Algonquian Digital Store
- Algonquian WooCommerce Bridge
- Algonquian Property Stewardship Services

The canonical authority, dependency, entry-file, and release requirements are defined in `config/plugin-manifest.json`.

## Core Workflow

```text
Seller submission
→ Deal Intake
→ canonical deal creation in Pipeline CRM
→ versioned underwriting in MAO Engine
→ approved offer in Offer Generator
→ document storage and PDF generation
→ signature workflow
→ funding, buyer distribution and closing activity
→ automation actions
→ Command Center reporting and audit verification
```

## Shared Production Standards

Every independently installable plugin must include:

- Valid WordPress plugin metadata.
- A protected bootstrap process and direct-access guard.
- Safe dependency validation.
- Activation, deactivation, migration, upgrade, and conservative uninstall behavior.
- Granular capabilities, nonce checks, REST permission callbacks, validation, sanitization, and escaping.
- Secure private-file access and upload validation where applicable.
- Centralized Platform Mail Gateway and audit services where applicable.
- `README.md`, `CHANGELOG.md`, `SECURITY.md`, and `uninstall.php`.
- No plaintext credentials or uncontrolled debug output.
- Accessible and responsive administration screens using the common Algonquian interface system.

### Shortcode UI standard

Registered shortcode tags must render useful production interfaces rather than blank shells, placeholders, or generic cards. The canonical 16-plugin shortcode registry, current Deal Intake correction, compatibility-bridge rules, transaction-control UI requirements, Property Stewardship portal requirements, ARE visual language, and page acceptance rules are maintained in `docs/canonical-shortcode-ui-standard.md`.

## WPBakery Rule

Generated WPBakery content must use:

```text
[vc_column_text]
[algq_shortcode]
[/vc_column_text]
```

Never use the malformed closing tag `</vc_column_text>`.

Generated pages must be idempotent and must preserve administrator-edited content when the required shortcode or generation metadata remains present.

## Repository Layout

```text
plugin/       Legacy or current Platform Plugin source
plugins/      Canonical independently installable plugin source directories
modules/      Platform-integrated or transitional modules
assets/       Shared front-end and administrative assets
branding/     Brand standards and approved placeholders
database/     Schema and migration documentation
docs/         Architecture, installation, security and user documentation
config/       Machine-readable plugin and route manifests
scripts/      Validation and build tooling
roadmap/      Version roadmap and launch planning
releases/     Generated release artifacts; never the only source of record
```

## Validation

Run:

```bash
php scripts/validate-wordpress-plugins.php
```

The GitHub Actions **WordPress Plugin Release Gate** verifies manifest integrity, source layout, plugin headers, required package files, PHP syntax, malformed WPBakery tags, debug indicators, and release-policy documentation.

Static validation does not replace activation and end-to-end testing in a disposable WordPress environment.

## Documentation

- `docs/wordpress-installation-readiness.md` — mandatory installation and production acceptance matrix.
- `docs/canonical-shortcode-ui-standard.md` — canonical page-facing shortcode registry, UI/content contract and compatibility rules.
- `SECURITY.md` — vulnerability handling and platform security baseline.
- `CHANGELOG.md` — release history and outstanding production requirements.
- `config/plugin-manifest.json` — authoritative plugin inventory, dependency graph, and release contract.

## Current Objective

Reconcile every plugin package with the canonical manifest, complete the page-facing shortcode UI/content implementation, complete WordPress activation and integration testing, generate release ZIPs from tagged canonical source, and publish only packages supported by recorded test evidence.
