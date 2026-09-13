# Changelog

## 2.1.0

- Added a canonical transaction-event bridge for event-driven ARE workflows.
- Added standardized events for `deal.qualified`, `underwriting.completed`, `approval.requested`, `offer.sent`, `funding.required`, `closing.scheduled`, property-service follow-ups, buyer activity, and customer follow-ups.
- Added Pipeline CRM stage adapters that translate authoritative stage transitions into automation milestones without taking ownership of the Deal record.
- Added compatibility ingestion for the existing `algq_automation_event` cross-plugin hook, including MAO underwriting approval and Offer Generator events.
- Added draft workflow templates for qualified-deal review, underwriting strategy review, human approvals, offer follow-up, funding, closing readiness, property-service follow-up, buyer activity, and customer follow-up.
- Added the `automation.workflows` Platform Service Interface provider with event dispatch, event catalog, and workflow-template discovery operations.
- Preserved mandatory human authority: templates do not auto-approve offers, contracts, capital commitments, funds movement, legal decisions, or closing actions.
- Added storage-safe trigger compatibility for the Automation 2.0 rule table while exposing the canonical dotted event vocabulary in the trigger catalog.
- Normalized plugin ownership metadata to Algonquian Real Estate, LLC and the dedicated Automation Engine plugin page.
- Kept schema version at 2.0.0 because this release does not alter the existing rules/jobs/logs/tasks table structures.

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