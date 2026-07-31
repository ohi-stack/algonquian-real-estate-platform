# Changelog

## 2.0.0 — Production Candidate

### Added

- Central offer service with validation, normalized metadata, offer numbering, creation, update, approval, and version snapshots.
- Five supported offer strategies.
- Granular capabilities and repaired role upgrades.
- Protected dashboard, builder, and history shortcodes.
- Functional builder submission and draft creation.
- `algq/v1` REST endpoints for offer workflow operations.
- Document metadata, SHA-256 hashes, and Document Library integration hook.
- PDF & Signature Engine delegation with explicit failure when no actual PDF provider responds.
- Idempotent generated-page metadata and default offer templates.
- Conservative uninstall controls.

### Changed

- Replaced generic `edit_posts` authorization with Offer Generator capabilities.
- Replaced hard-coded front-end URLs with resolved WordPress page permalinks.
- Standardized purchase-price metadata while preserving the legacy `_algq_offer_price` key.
- Updated plugin metadata, minimum WordPress and PHP versions, version display, and documentation.
- Moved REST routes from `algq-offers/v1` to the platform-standard `algq/v1` namespace.

### Fixed

- Builder forms now create persistent offer records.
- Offer History no longer exposes records to unauthenticated visitors.
- Offer document rendering reads the same metadata keys written by the builder.
- The former “PDF” response no longer downloads HTML with a misleading file label.
- Existing Offer Manager roles receive newly required capabilities during upgrade.

### Validation required

- WordPress activation and upgrade testing.
- Cross-plugin deal, underwriting, document, PDF, signature, automation, and audit tests.
- Role and record-level permission tests.
- End-to-end offer creation through execution.

## 1.0.0

- Added initial plugin bootstrap, post types, shortcodes, styling, and integration scaffolds.
