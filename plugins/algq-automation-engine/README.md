# Algonquian Automation Engine

**Version:** 2.1.0  
**Developed by:** Algonquian Real Estate, LLC  
**Division:** Algonquian Real Estate Technology Division  
**Platform:** Algonquian Real Estate

Algonquian Automation Engine executes auditable trigger, condition, action, queue, retry, and standardized event workflows across the Algonquian Real Estate operating platform.

## 2.1.0 event-driven release

Automation 2.1 consumes meaningful business milestones instead of relying only on low-level plugin actions. It translates authoritative plugin events into controlled workflow triggers while leaving each business record with its owning plugin.

Primary canonical events:

- `deal.qualified`
- `underwriting.completed`
- `approval.requested`
- `offer.sent`
- `funding.required`
- `closing.scheduled`
- `service.follow_up_due`
- `buyer.activity`
- `customer.follow_up_due`
- `customer.service_activity`

Supporting events include `deal.stage_changed`, `offer.saved`, and `offer.document_generated`.

### Event ownership

- Pipeline CRM remains authoritative for the Deal lifecycle. Automation listens to `algq_pipeline_stage_changed` and translates qualifying transitions into workflow milestones.
- MAO Engine remains authoritative for underwriting. Its approved-underwriting event is translated into `underwriting.completed`.
- Offer Generator remains authoritative for offers. Automation responds to offer events and Pipeline's authorized `offer_sent` stage.
- Funding Tracker remains authoritative for capital/funding records. Automation coordinates the next action but does not commit capital.
- Buyer Portal and Deal Marketplace remain authoritative for buyer identity, access, and marketplace activity.
- Property Stewardship remains authoritative for stewardship/service records. Automation can coordinate follow-ups.

## Production-safe workflow templates

2.1.0 seeds the following workflow templates as **draft rules**:

1. Qualified Deal → Underwriting Review
2. Underwriting Complete → Strategy Review
3. Approval Request → Administrator Alert
4. Offer Sent → Seller Follow-Up
5. Funding Required → Capital Action
6. Closing Scheduled → Readiness Review
7. Property Service → Follow-Up
8. Buyer Activity → Relationship Follow-Up
9. Customer Relationship → Follow-Up

They are intentionally not auto-activated. An authorized administrator must review routing, recipients, deadlines, and transaction-specific requirements before activation.

## Human approval boundary

Automation may research, route, notify, schedule, create administrative tasks, and execute previously authorized workflow actions. It does **not** independently approve or bind Algonquian Real Estate to:

- offers or negotiated terms;
- contracts or amendments;
- legal decisions;
- capital commitments;
- movement or release of funds;
- closing authorization; or
- activities requiring a licensed professional.

`approval.requested` is therefore a routing and escalation event, not an approval action.

## Platform Service Interface

When the Algonquian Real Estate Platform Service Interface is available, Automation 2.1 registers:

`automation.workflows`

Operations:

- `dispatch_event` — dispatch one supported canonical automation event into the durable rule/queue engine.
- `event_catalog` — discover the canonical event vocabulary and storage keys.
- `workflow_templates` — discover the production-safe draft templates shipped with 2.1.0.

Example internal service call:

```php
$result = algq_platform_service_call(
    'automation.workflows',
    'dispatch_event',
    array(
        'event_key'   => 'approval.requested',
        'object_type' => 'offer',
        'object_id'   => 123,
        'payload'     => array(
            'deal_id'       => 456,
            'approval_type' => 'offer_release',
        ),
    ),
    array(
        'caller_plugin' => 'algq-offer-generator',
    )
);
```

## Trigger-key compatibility

Automation 2.0 stored trigger keys with WordPress `sanitize_key()`, which removes periods. Automation 2.1 retains that database compatibility so existing rules do not require a schema migration.

The exact dotted canonical event name is preserved in `payload.canonical_event` and displayed in the trigger catalog. For example:

`deal.qualified` → storage key `dealqualified`

This compatibility behavior is deliberate for the 2.1 release. The table schema remains version 2.0.0.

## Existing engine capabilities

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

## Existing 2.0 event compatibility

The 2.1 catalog retains compatibility for:

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

The Automation Engine owns automation rules, jobs, execution logs, retries, workflow tasks, event translation, and dead-letter state. It does not become the authoritative owner of deals, underwriting, offers, documents, signatures, buyer records, funding records, property-service records, or payment transactions.