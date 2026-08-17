# Changelog

## 2.0.0 — 2026-07-31

### Added

- Protected private document storage and authorized download controller.
- SHA-256 integrity hashes and file-version metadata history.
- Durable access-request and download-audit database tables.
- Request UUIDs, workflow statuses, consent versioning, rate limiting, and privacy hashes.
- Granular document, request, package, download, and audit capabilities.
- Hierarchical institutional document taxonomy.
- Document access, confidentiality, retention, expiration, legal-hold, template, and related-deal controls.
- Document package records and package membership management.
- Restricted REST metadata endpoint.
- Platform registry, health-check, shared mail, and audit integration points.
- Branded, searchable public library interface and unified administrator dashboard.
- Idempotent WPBakery-compatible generated pages.
- Conservative uninstall routine.

### Changed

- Replaced raw file URL storage and direct links with protected delivery.
- Replaced request storage in a single WordPress option with normalized database records.
- Replaced broad `manage_options` checks with least-privilege capabilities.
- Expanded the documented institutional seed library.

### Removed

- Public exposure of administrator-entered file URLs.
- Automatic download access based only on whether a visitor is logged in.
