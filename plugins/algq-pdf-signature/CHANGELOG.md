# Changelog

## 2.0.0 — 2026-07-31

### Added

- Protected PDF generation with a built-in text renderer and extensible rich-renderer filter.
- UUID, version, SHA-256 hash, source-record, deal, author, and lifecycle metadata.
- Dedicated signature-request, signer, and append-only event tables.
- Granular capabilities and permission-checked admin, shortcode, download, and REST operations.
- Provider registry, request adapter, webhook authentication, normalization, and replay protection contracts.
- Manual signature status tracking without representing the plugin as a standalone electronic-signature provider.
- Private file storage controls for Apache, IIS, and direct-index access.
- Idempotent WPBakery page generation using valid shortcode closure syntax.
- Platform audit and health integration points.
- Conservative uninstall control and formal testing documentation.

### Changed

- Replaced the 1.0.0 single-table record tracker with a controlled production architecture.
- Replaced broad `manage_options` access with dedicated platform capabilities.
- Removed public file URLs from the document model; downloads now pass through authorization and integrity checks.

### Security

- Added nonce validation, capability checks, REST permission callbacks, path traversal controls, file-hash verification, minimized webhook evidence, and duplicate provider-event rejection.
