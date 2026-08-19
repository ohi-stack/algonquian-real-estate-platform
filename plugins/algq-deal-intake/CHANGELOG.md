# Changelog

## 2.1.0

- Reconciled the public `/submit-a-property/` and `/sell-your-property/` conversion routes to the canonical `[algq_deal_intake_form]` interface without overwriting administrator-authored page content.
- Added transitional `[algq_seller_intake_entry]` compatibility rendering and a permanent redirect from the older generated `/submit-property/` route.
- Added multipart support and optional supporting-document uploads to the public, internal, and quick-capture intake forms.
- Added server-side file-count, file-size, extension, and MIME validation for PDF, JPEG, PNG, WEBP, and DOCX supporting documents.
- Added UUID-based private filenames, SHA-256 integrity hashes, protected Deal Intake storage, and durable attachment-table records.
- Added optional Cloudflare Turnstile rendering and server-side verification when deployment credentials are configured.
- Added automatic PDF generation for each successfully committed intake submission.
- Added protected WordPress Media Library registration for generated intake PDFs, including submission linkage and SHA-256 metadata.
- Added a capability-gated private PDF download controller instead of exposing generated PDFs through ordinary public media URLs.
- Added PDF archival delivery to the configured business mailbox, defaulting to `algonquianre@gmail.com`.
- Added artifact, attachment-rejection, Turnstile-failure, PDF archival, and archive-email audit events.
- Preserved the 2.0.0 database schema; no schema migration is required for this feature release.

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
