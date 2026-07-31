# Changelog

## 1.1.0 — 2026-07-31

### Security

- Replaced broad logged-in deal visibility with buyer-specific authorization.
- Replaced public package URL redirects with validated WordPress attachment streaming.
- Added deal-scoped NDA acceptance and verification.
- Added hashed IP and user-agent evidence without storing raw values.
- Added granular buyer capabilities and role reconciliation.
- Added deal-specific nonces for NDA, interest, and download actions.

### Added

- Buyer company, phone, target-market, property-type, and terms-consent fields.
- NDA acceptance UUIDs and version enforcement.
- Attachment ID and file-hash download records.
- Authorization hooks for buyer interest activity.
- WPBakery-compatible idempotent generated pages.

### Changed

- Buyer Dashboard now reports only authorized deals.
- Deal packages now use `_algq_package_attachment_id`.
- Buyer assignment now uses `_algq_authorized_buyer_ids`.
- Shared `algq_buyer` capabilities are merged instead of depending on plugin activation order.
