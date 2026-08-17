# Changelog

## 2.0.0 — 2026-07-31

### Added

- Canonical source package under `plugins/algq-deal-marketplace/`.
- Versioned database migrations and schema tracking.
- Additive shared buyer capability migration.
- Deal-level access grants with optional expiration and entitlement references.
- Versioned NDA acceptance table with document, IP, and user-agent hashes.
- Controlled download controller for private package attachments.
- Validated buyer offer creation and offer-status administration.
- Platform audit, mail, automation, and Stripe-entitlement integration hooks.
- Capability-protected REST API.
- Production health checks and release acceptance documentation.

### Changed

- The Marketplace no longer owns `/buyer-dashboard/`.
- The buyer offer form derives the deal from an authorized route or shortcode attribute.
- Package URLs are no longer displayed or accepted as the production delivery mechanism.
- Buyer access is checked for every NDA, offer, download, and REST operation.

### Security

- Added nonce enforcement, record-level authorization, rate limiting, output escaping, structured audit records, privacy-preserving client hashes, and conservative uninstall behavior.

### Compatibility

- Legacy Marketplace shortcodes remain available.
- Legacy `algq_dm_nda_accepted` metadata is imported as a historical acceptance record.
