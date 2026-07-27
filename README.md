# Algonquian Real Estate Platform

Enterprise WordPress platform for real estate lead intake, canonical deal management, underwriting, offer preparation, buyer and capital relationships, document workflows, automation, commerce, reporting, and executive oversight.

## Organization

**Parent entity:** Algonquian Real Estate LLC  
**Technology division:** Algonquian Real Estate Technology Division (ARE Tech)  
**Primary market:** Connecticut  
**Author:** Onegodian | Algonquian Real Estate

Algonquian Real Estate LLC was organized in Connecticut on February 11, 2026. ARE Tech is the internal operating division responsible for the design, development, maintenance, documentation, and commercialization of the platform's software assets.

## Canonical source policy

The unpacked source under `plugins/{plugin-slug}/` is the source of record. Uploaded or generated ZIP files are release artifacts and must not replace canonical source control.

Each WordPress package is built so the archive contains one top-level plugin directory and can be installed through:

```text
WordPress Admin → Plugins → Add New → Upload Plugin
```

## Platform products

The repository is intended to maintain the complete Algonquian plugin ecosystem, including:

- Algonquian Real Estate Platform Plugin
- Algonquian Deal Intake
- Algonquian Pipeline CRM
- Algonquian MAO Engine
- Algonquian Offer Generator
- Algonquian Funding Tracker
- Algonquian Buyer Portal
- Algonquian Deal Marketplace
- Algonquian Document Library
- Algonquian PDF & Signature Engine
- Algonquian Automation Engine
- Algonquian Admin Command Center
- Algonquian Digital Store and Digital Products
- ALGQ WooCommerce Bridge

The protected foundation architecture assigns one authoritative operational domain to each plugin. Plugins must use documented interfaces and must not silently duplicate master deal, underwriting, offer, document, signature, automation, or audit records.

## Shared platform services

The Platform Plugin is the authority for:

- plugin registry and dependency health;
- shared roles and granular capabilities;
- common navigation and UI components;
- Algonquian Mail Gateway;
- centralized append-only audit events;
- secure file storage and private downloads;
- idempotent generated pages;
- shared API contracts;
- platform health monitoring;
- design tokens and accessibility standards.

Optional integrations such as WooCommerce and Stripe must degrade safely when inactive and may not expose credentials in settings, reports, exports, or logs.

## WordPress release gate

The authoritative requirements are in [`docs/WORDPRESS-RELEASE-STANDARD.md`](docs/WORDPRESS-RELEASE-STANDARD.md).

Every production package must include, at minimum:

- valid WordPress plugin metadata;
- direct-access protection;
- activation and deactivation lifecycle handling;
- stable semantic versioning;
- dependency checks and safe degraded behavior;
- granular capabilities and nonces;
- validation, sanitization, prepared queries, and escaping;
- automatic page generation where applicable;
- shortcodes and admin navigation where applicable;
- `README.md`, `CHANGELOG.md`, `SECURITY.md`, and `uninstall.php`;
- shared ARE interface styling;
- documented data ownership, integrations, and uninstall behavior.

A version label does not establish production readiness. Validation and test evidence must pass before a package is released.

## Build and validation

Run the release validator:

```bash
php build/validate-wordpress-plugins.php
```

Create installable ZIP files and checksums:

```bash
bash build/package-wordpress-plugins.sh
```

Generated packages are placed in `releases/`. GitHub Actions validates the plugin tree on PHP 8.1, 8.2, and 8.3 and uploads packaged ZIP files as a workflow artifact when the release gate passes.

## WPBakery requirement

Generated WPBakery text blocks must use the correct closing shortcode:

```text
[vc_column_text]
[algq_shortcode]
[/vc_column_text]
```

Never use `</vc_column_text>`.

## Interface standard

All plugins must use one shared interaction model and institutional identity:

- Algonquian navy, gold, teal, white, and neutral design tokens;
- consistent page headers, cards, KPI blocks, forms, tables, badges, alerts, tabs, timelines, and health indicators;
- responsive layouts;
- accessible labels, focus states, contrast, headings, tables, and modal behavior;
- common status and action terminology.

## Repository layout

```text
plugins/     Canonical WordPress plugin source
modules/     Approved modular extensions
assets/      Shared assets and branding support
branding/    Brand standards and approved visual references
build/       Validation and packaging scripts
database/    Schema and migration documentation
docs/        Architecture, installation, security, and user documentation
packages/    Import manifests and source-artifact records
releases/    Generated installable ZIP files and checksums
roadmap/     Product and release planning
.github/     Automated validation and packaging workflows
```

## Current release status

The suite remains under production hardening until every canonical plugin passes the automated release validator and the manual WordPress installation, activation, permissions, integration, accessibility, and end-to-end test matrix.
