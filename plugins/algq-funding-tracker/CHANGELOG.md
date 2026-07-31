# Changelog

## 1.0.0 — 2026-07-31

### Added

- Production bootstrap and semantic version metadata.
- Capital-source, funding-commitment, and funding-activity tables.
- Capability-controlled WordPress administration interface.
- Capital-source and deal-funding creation workflows with nonce and permission checks.
- Funding KPI summaries, progress indicators, operational tables, status updates, and CSV export.
- REST API endpoints with explicit permission callbacks.
- Shortcodes for overview, protected dashboard, and capital-source views.
- WPBakery-compatible generated pages.
- Central platform audit-event integration.
- Responsive Algonquian design-system styling.
- Conservative uninstall routine and production documentation.

### Changed

- Replaced the nonfunctional `0.1.0` scaffold with a production foundation.

### Security

- Added granular capabilities, nonce verification, sanitization, output escaping, prepared SQL, permission callbacks, and redacted activity metadata.
