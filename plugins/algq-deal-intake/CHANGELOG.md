# Changelog

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
- Changed uninstall behavior to preserve operational records by default.

## 1.0.2-rc.2

- Prior installed release-candidate package used as migration reference.
