# Automation Engine Architecture

## 2.1 operating flow

```text
Authoritative ARE plugin
  -> domain event / Pipeline stage transition
  -> Automation Event Bridge
  -> canonical business event
  -> registered trigger
  -> active rule lookup
  -> condition evaluation
  -> idempotent queue job
  -> locked execution
  -> controlled action
  -> completed, retry, or dead-letter state
  -> local and platform audit event
```

The Event Bridge translates business milestones. It does not create a second system of record.

## Canonical event layer

Automation 2.1 recognizes the following primary events:

```text
deal.qualified
underwriting.completed
approval.requested
offer.sent
funding.required
closing.scheduled
service.follow_up_due
buyer.activity
customer.follow_up_due
customer.service_activity
```

Supporting events include:

```text
deal.stage_changed
offer.saved
offer.document_generated
```

### Pipeline-derived events

Pipeline CRM owns the canonical Deal stage. The Automation Event Bridge listens to `algq_pipeline_stage_changed` and translates relevant transitions:

```text
* -> underwriting          => deal.qualified
* -> offer_sent            => offer.sent
* -> funding               => funding.required
* -> closing_scheduled     => closing.scheduled
```

The `deal.qualified` translation is restricted to transitions into underwriting from pre-underwriting stages.

### Underwriting

MAO Engine remains authoritative for underwriting. Its existing `mao.underwriting_approved` automation event is translated to:

```text
underwriting.completed
```

Automation may create a review task or alert, but the approved underwriting record remains in MAO Engine.

### Human approval

Consequential workflows emit:

```text
approval.requested
```

That event may notify or route the decision to a human. It must not be interpreted as approval. Offers, contracts, negotiated commitments, capital commitments, funds movement, legal decisions, and closing authority remain subject to human authorization and the owning plugin's controls.

## Platform Service Interface

Automation 2.1 registers the service:

```text
automation.workflows
```

Supported operations:

```text
dispatch_event
event_catalog
workflow_templates
```

The service is an internal orchestration boundary. It does not expose or assume ownership of domain records.

## Trigger storage compatibility

Automation 2.0 stored trigger keys after WordPress `sanitize_key()` processing, which removes periods. Automation 2.1 therefore distinguishes between:

```text
Canonical event: deal.qualified
Storage key:    dealqualified
```

The exact canonical event is retained in `payload.canonical_event`. Existing table structures and 2.0 rules remain compatible; schema version stays at 2.0.0.

## Data authority

The plugin owns:

- automation rules;
- automation jobs;
- event translation;
- retries and dead-letter status;
- local automation logs;
- automation-created task records; and
- workflow-template definitions.

The plugin does not own canonical deal, underwriting, offer, document, signature, buyer, funding, property-service, customer-relationship, or payment records.

## Draft workflow templates

Version 2.1 seeds production-safe templates as `draft` rules. They require authorized administrator review and activation.

Templates cover:

- qualified deal → underwriting review;
- underwriting completed → strategy review;
- approval requested → administrator alert;
- offer sent → seller follow-up;
- funding required → capital action;
- closing scheduled → readiness review;
- property-service follow-up;
- buyer activity follow-up; and
- customer relationship follow-up.

No template automatically approves a consequential action.

## Condition format

```json
[
  {
    "field": "payload.new_stage",
    "operator": "equals",
    "value": "closing_scheduled"
  }
]
```

Supported operators: `equals`, `not_equals`, `in`, `not_in`, `exists`, `empty`, `contains`, `gt`, `gte`, `lt`, and `lte`.

Canonical event name is available at:

```text
payload.canonical_event
```

## Action payload example

```json
{
  "title": "Prepare closing readiness review for deal {{object_id}}",
  "description": "Created by rule {{rule_id}} after {{event_key}}.",
  "priority": "high"
}
```

## Idempotency

Queue jobs retain the existing unique idempotency-key constraint and configurable duplicate-suppression window. Event adapters avoid creating authoritative domain records, and repeated event deliveries are expected to be safe.

## Failure handling

Failed jobs retry with exponential backoff. When attempts reach the rule limit, the job enters `dead` status. Authorized administrators can retry the job from the queue screen or REST API.

## Human-control invariant

```text
Automation may advance workflow administration.
Automation may not independently bind Algonquian Real Estate.
```

Where an action changes legal, financial, transaction, or closing authority, the owning plugin and authorized human decision remain controlling.