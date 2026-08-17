# Algonquian Automation Engine

**Version:** 2.0.0  
**Author:** Onegodian  
**Platform:** Algonquian Real Estate

Algonquian Automation Engine executes auditable trigger, condition, and action workflows across the Algonquian Real Estate platform.

## 2.0.0 production capabilities

- Registered trigger and action libraries.
- JSON condition evaluation with controlled operators.
- Durable database queue.
- Idempotency and duplicate suppression.
- Exponential retry policy.
- Dead-letter job state and manual retry.
- Rule creation, activation, pause, and archival.
- Queue, task, and audit-log administration.
- REST endpoints protected by granular capabilities.
- Platform audit and mail-gateway integration when available.
- Stripe event intake through `algq_stripe_event`.
- Migration-safe schema updates from the 1.0.0 tables.
- Conservative uninstall behavior that preserves operational data by default.
- Idempotent WPBakery page generation using valid `[vc_column_text]...[/vc_column_text]` syntax.

## Built-in triggers

- `deal.status_changed`
- `document.generated`
- `offer.generated`
- `signature.completed`
- `buyer.interest_received`
- `funding.status_changed`
- `stripe.event`
- `automation.manual_test`

## Built-in actions

- `log_only`
- `create_task`
- `send_email`
- `notify_admin`
- `generate_document`
- `request_signature`
- `archive_record`
- `platform_action`

Custom triggers and actions can be registered with `algq_automation_triggers`, `algq_automation_actions`, and `algq_automation_execute_action`.

## Shortcodes

- `[algq_automation_overview]`
- `[algq_automation_getting_started]`
- `[algq_automation_docs]`
- `[algq_automation_rules]`

## Generated pages

- `/plugin/automation-engine/`
- `/plugin/automation-engine/start/`
- `/plugin/automation-engine/docs/`
- `/automation-rules/`

Existing pages are never overwritten.

## Requirements

- WordPress 6.8+
- PHP 8.2+

## Operational boundary

The Automation Engine owns automation rules, jobs, execution logs, retries, and dead-letter state. It does not become the authoritative owner of deals, offers, documents, signatures, funding records, or Stripe transactions.
