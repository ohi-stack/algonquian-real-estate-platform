# Automation Engine 2.1 — Canonical Event Contract

**Plugin:** Algonquian Automation Engine  
**Version:** 2.1.0  
**Authority:** Automation rules, event translation, queue execution, retries, workflow tasks, and execution history  
**Not authoritative for:** Deals, underwriting, offers, documents, buyers, funding, property-service records, payments, contracts, or closing decisions

## Purpose

This contract gives ARE plugins, the Agent Engine, and approved platform integrations a stable business-event vocabulary for transaction advancement.

The rule is:

```text
Owning plugin changes authoritative record
        ↓
Owning plugin emits event
        ↓
Automation translates/captures event
        ↓
Automation rule creates administrative next action
        ↓
Human or owning plugin performs consequential operation
```

## Event catalog

| Canonical event | Meaning | Primary producer / adapter | Typical safe automation |
|---|---|---|---|
| `deal.qualified` | A deal has cleared pre-underwriting screening and entered underwriting | Pipeline stage adapter | Create underwriting review task |
| `underwriting.completed` | Authoritative underwriting has been approved/completed | MAO automation event adapter | Create strategy-review task |
| `approval.requested` | A consequential action requires human approval | Owning plugin / Agent Engine / Platform service | Alert administrator; create review task |
| `offer.sent` | An authorized offer has been sent | Pipeline stage adapter | Schedule seller follow-up |
| `funding.required` | A deal has entered a funding-required state | Pipeline stage adapter / Funding integration | Create capital-resolution task |
| `closing.scheduled` | Canonical deal stage is closing scheduled | Pipeline stage adapter | Create closing-readiness task |
| `service.follow_up_due` | A property/service record requires follow-up | Property Stewardship / approved service integration | Create property-service task |
| `buyer.activity` | Meaningful buyer activity occurred | Buyer Portal / Marketplace adapter | Create buyer-response task |
| `customer.follow_up_due` | Customer relationship follow-up is due | CRM/relationship integration | Create customer follow-up task |
| `customer.service_activity` | Customer-facing service activity occurred | Service/relationship integration | Route service next action |
| `deal.stage_changed` | Canonical Pipeline stage changed | Pipeline stage adapter | Audit/conditional workflow |
| `offer.saved` | Offer record was saved | Existing Offer Generator automation hook | Audit/conditional workflow |
| `offer.document_generated` | Offer document was generated | Existing Offer Generator automation hook | Document/delivery follow-up |

## Pipeline mappings

Automation listens to the authoritative Pipeline event:

```php
algq_pipeline_stage_changed( $deal_id, $old_stage, $new_stage, $context )
```

Mappings:

```text
pre-underwriting stage → underwriting = deal.qualified
any stage → offer_sent             = offer.sent
any stage → funding                = funding.required
any stage → closing_scheduled      = closing.scheduled
```

All transitions also generate `deal.stage_changed`.

Automation does not change Pipeline stage ownership or bypass Pipeline transition validation.

## Existing generic hook compatibility

Automation 2.1 consumes:

```php
algq_automation_event( $event_key, $payload )
```

Known aliases include:

```text
mao.underwriting_approved → underwriting.completed
offer_saved               → offer.saved
offer_document_generated  → offer.document_generated
```

Unknown generic events are not silently converted. They are surfaced through:

```php
algq_automation_unmapped_event
```

so integrations can be corrected deliberately.

## Platform Service Interface

Service ID:

```text
automation.workflows
```

### dispatch_event

Input:

```php
array(
    'event_key'   => 'approval.requested',
    'object_type' => 'offer',
    'object_id'   => 123,
    'payload'     => array(
        'deal_id'       => 456,
        'approval_type' => 'offer_release',
    ),
)
```

Context should identify the caller when available:

```php
array(
    'caller_plugin' => 'algq-offer-generator',
    'request_id'    => '...',
)
```

Output:

```php
array(
    'event_key'   => 'approval.requested',
    'storage_key' => 'approvalrequested',
    'queued_jobs' => array( 101, 102 ),
)
```

### event_catalog

Returns canonical event names, storage keys, and labels.

### workflow_templates

Returns production-safe draft workflow template metadata.

## Payload conventions

Canonical dispatch preserves the exact event in:

```text
payload.canonical_event
```

Where applicable, payloads should include:

```text
deal_id
source_plugin
source_event
old_stage
new_stage
approval_type
buyer_id
customer_id
service_type
activity
context
```

Sensitive values are processed through the Automation Engine redaction layer.

## Trigger storage compatibility

Automation 2.0 used WordPress `sanitize_key()` for trigger persistence. Periods are removed from stored trigger keys.

Examples:

```text
deal.qualified         → dealqualified
underwriting.completed → underwritingcompleted
approval.requested     → approvalrequested
```

Automation 2.1 preserves those storage semantics to avoid a table/schema migration while displaying and carrying the dotted canonical event name.

## Idempotency

The existing job table provides a unique idempotency key and configurable duplicate-suppression window. Producers should still avoid intentionally emitting duplicate business events when nothing changed.

## Human authority

`approval.requested` is a mandatory escalation mechanism, not an approval shortcut.

Automation may not independently:

- release a binding offer;
- execute or amend a contract;
- accept negotiated terms;
- commit or move capital;
- release funds;
- make legal decisions;
- authorize closing; or
- represent a licensed professional.

Those actions remain with authorized humans and the authoritative domain plugin/workflow.