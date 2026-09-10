# Changelog

## 1.2.0 — 2026-08-18

### Added

- Production `/investors/` page managed by the Buyer Portal integration.
- `[algq_investors_page]` shortcode for the Investors & Capital buyer-access experience.
- Runtime reconciliation of the shared `algq_buyer` role with Buyer Portal and Deal Marketplace base capabilities.
- Buyer login routing to the canonical Marketplace page when the Deal Marketplace is active.
- Marketplace route resolution that supports the Marketplace page option, legacy `/deal-marketplace/`, and v2 `/marketplace/` fallback.

### Changed

- Buyer registration now feeds a deterministic path: account creation → Buyer Login → Marketplace.
- Registered buyers receive base Marketplace capabilities without depending on plugin activation order.
- Registered-tier marketplace access remains subject to deal publication, expiration, NDA, and record-level restrictions.
- Private and premium deal access continues to require Marketplace access grants.

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
