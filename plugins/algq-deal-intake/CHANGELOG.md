# Changelog

## 2.1.0

- Rebased the production upgrade on the latest canonical `main` source rather than the older conflicted release branch.
- Standardized plugin metadata to **Algonquian Real Estate, LLC**, the Technology Division author URL, and the dedicated Deal Intake plugin page.
- Added complete branded ARE interfaces for public intake, property submission, internal intake, quick capture, homeowner options, property review, seller portal, and About Plugin rendering.
- Preserved the new `[algq_property_review]` interface and `/request-property-review/` route from current `main`.
- Expanded homeowner options to route traditional sale, direct/as-is review, repair-before-sale, retention, seller-financing discussion, Property Stewardship, inherited/transition property, and development/redevelopment opportunities.
- Added production seller-funnel reconciliation for `/submit-a-property/` and `/sell-your-property/` without overwriting administrator-authored content.
- Added a permanent redirect from the legacy `/submit-property/` route after reconciliation.
- Added optional supporting-document uploads with server-side MIME verification, UUID storage names, configurable count/size limits, protected storage, SHA-256 hashes, and audit events.
- Added optional Cloudflare Turnstile server verification while retaining nonce, honeypot, minimum-submit-time, and rate-limit controls.
- Added an automatic PDF intake record after successful persistence.
- Added private Media Library registration for generated PDFs with SHA-256 metadata and a capability-gated signed download controller.
- Added PDF archival delivery to `algonquianre@gmail.com` by default, with a configurable destination.
- Added a default 15 MB email-attachment ceiling so an oversized PDF remains safely archived even when it should not be attached to email.
- Preserved duplicate-review gating and exactly-one Pipeline CRM deal handoff semantics.
- Expanded security and release documentation to require real staging verification before live-production certification.
- Applied the current ARE navy/gold/teal/white responsive interface system across page-facing shortcodes.

## 2.0.0

- Replaced the incomplete release-candidate scaffold with a self-contained production-candidate bootstrap.
- Raised the declared baseline to WordPress 6.8 and PHP 8.2.
- Added versioned custom tables for submissions, sellers, properties, consent evidence, attachment metadata, and duplicate review.
- Added dedicated Deal Intake capabilities and acquisition roles.
- Added public, internal, and quick-capture workflows with server-side validation.
- Added public honeypot, minimum-submit-time, and configurable hourly rate limiting.
- Added versioned consent, privacy, terms, IP, user-agent, and acceptance-time evidence.
- Added weighted duplicate detection using seller email, seller phone, normalized property address, and parcel.
- Added duplicate review and controlled resolution actions.
- Added lead scoring based on timing and property-situation signals.
- Added idempotent WPBakery-compatible page generation that preserves administrator content.
- Added protected REST list, create, accept, and duplicate-check endpoints under `algq/v1`.
- Added controlled Pipeline CRM handoff with idempotent canonical deal-ID storage.
- Added Platform Mail Gateway and audit-service compatibility hooks.
- Added secured CSV export with formula-injection protection.
- Added a dedicated administrator About Plugin page, Plugins-screen About link, public About shortcode, generated `/plugin/deal-intake/about/` page, and integration-health summary.
- Changed uninstall behavior to preserve operational records by default.

## 1.0.2-rc.2

- Prior installed release-candidate package used as migration reference.
