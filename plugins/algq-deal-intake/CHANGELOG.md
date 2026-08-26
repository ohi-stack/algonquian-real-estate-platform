# Changelog

## 2.1.0

- Rebuilt the Deal Intake administrator landing screen as an operational dashboard aligned with the Algonquian Real Estate unified admin design system.
- Added five KPI cards for total submissions, new submissions, review volume, qualified leads, and Pipeline CRM deals created.
- Added selectable 7-, 30-, and 90-day reporting windows with prior-period trend comparisons.
- Added a submission-pipeline snapshot using the actual Deal Intake states: pending review, duplicate review, qualified lead score, awaiting CRM handoff, and accepted/deal-created records.
- Added seven-day submission activity visualization without introducing a JavaScript chart dependency.
- Added lead-source reporting from persisted `lead_source` data.
- Expanded the recent-submissions workspace with seller, property, source, score, status, and controlled acceptance actions.
- Added quick actions for public intake, duplicate review, CSV export, and settings.
- Added system-status indicators for the public intake page, notification email, Pipeline CRM integration, and duplicate detection.
- Preserved Deal Intake authority boundaries: Deal Intake owns intake-time records and accepted opportunities continue to hand off to Pipeline CRM for the canonical deal record.
- Standardized plugin metadata to Algonquian Real Estate, LLC and the dedicated Deal Intake public plugin page.
- No database schema changes were introduced; schema remains at 2.0.0.

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
