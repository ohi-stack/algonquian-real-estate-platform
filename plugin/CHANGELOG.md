# Changelog

## 3.1.0 — 2026-09-10

### Added

- Platform Service Interface supporting both object-backed authoritative providers and callable service providers.
- Modules 13–20: Seller Portal, Title & Closing Engine, Disposition Engine, Investor Portal, Reporting & Analytics, Document Vault, Task / Project Management, and Communications Hub.
- Shared operational work-item table with assignments, statuses, next actions, deadlines, external references, audit events, and REST endpoints.
- `[algq_operational_modules]` and `[algq_platform_modules]` overview interfaces plus eight module-specific shortcodes.
- Platform 3.1 integration contract for Pipeline CRM 2.2.1 while preserving Pipeline CRM as canonical Deal authority.

### Changed

- Standardized current plugin ownership metadata to Algonquian Real Estate, LLC.
- Reconciled both `algq_platform_registry` and `algq_platform_plugin_registry` compatibility filters.
- Promoted the shared ARE Admin UI from the unreleased line into the 3.1 platform release.
- Kept protected operational domains in their designated companion plugins; Platform workspaces coordinate rather than duplicate those records.

## Unreleased

### Added

- Shared ARE WordPress admin design system for Platform and companion-plugin screens.
- Canonical navy, blue, gold, teal, neutral, success, warning, and critical design tokens.
- Reusable KPI, card, panel, toolbar, badge, status, progress, skeleton, empty-state, and button utility classes.
- Restrained card entrance, hover, progress, live-status, and loading motion with `prefers-reduced-motion` support.
- Screen-scoped loading so unrelated WordPress and third-party plugin administration pages are not restyled.
- `algq_admin_ui_is_are_screen` filter for companion-plugin screen registration.
- ARE Admin UI implementation and release standard documentation.

### Changed

- Standard WordPress postboxes, cards, tables, forms, buttons, notices, tabs, and existing ALGQ KPI widgets now inherit the common ARE admin presentation when rendered on an ARE screen.

## 2.0.0 — 2026-07-31

### Added

- Production infrastructure bootstrap for WordPress 6.8+ and PHP 8.2+.
- Authoritative companion-plugin registry and compatibility reporting.
- Shared platform capabilities and Platform Manager role.
- Activation-order-safe reconciliation of the shared `algq_buyer` role.
- Append-only structured audit log with sensitive-value redaction.
- Algonquian Mail Gateway with standard SMTP, sender identity controls, success/failure logging, and test mail.
- Private storage abstraction with access guards and tokenized one-time downloads.
- Scheduled health monitoring and permission-protected REST health endpoint.
- Safe, idempotent page generation that creates missing pages without overwriting administrator content.
- Legacy shortcode bridges that yield control to authoritative companion plugins.
- Conservative uninstall behavior.

### Changed

- Reclassified the Platform Plugin as shared infrastructure rather than the owner of deals, buyers, underwriting, documents, funding, or automation records.
- Replaced release-candidate metadata with production infrastructure status.
- Raised minimum requirements to WordPress 6.8 and PHP 8.2.

### Removed

- Platform-owned deal and buyer table creation.
- Monolithic placeholder implementations for companion-plugin workflows.
- Destructive activity-log truncation control.
- Automatic replacement of existing page content.
