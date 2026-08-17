# Changelog

## 2.0.0

- Replaced the 1.0.0 event logger scaffold with an executable workflow engine.
- Added trigger and action registries.
- Added condition evaluation and controlled JSON payloads.
- Added durable automation jobs with idempotency keys.
- Added queue processing, locking, retry backoff, and dead-letter handling.
- Added rule, queue, log, task, and health administration.
- Added protected REST routes for rules, jobs, retries, and test events.
- Added Platform Mail Gateway and centralized audit integration points.
- Added Stripe event intake.
- Added migration-safe database upgrades.
- Corrected nested generated-page creation and preserved administrator content.
- Changed uninstall behavior to preserve records unless explicit deletion is enabled.
- Updated capabilities to the shared `manage_algq_*` convention.
- Raised minimum requirements to WordPress 6.8 and PHP 8.2.

## 1.0.0

- Initial plugin bootstrap, basic event logging, tables, shortcodes, generated pages, and administrative placeholders.
