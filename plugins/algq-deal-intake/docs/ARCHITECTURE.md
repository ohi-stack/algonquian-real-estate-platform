# Deal Intake Architecture

## Data flow

```text
Public or internal form
  -> validation and consent evidence
  -> seller and property records
  -> submission record
  -> duplicate detection
  -> review and resolution
  -> controlled Pipeline CRM handoff
  -> canonical Pipeline CRM deal ID
```

## Ownership

Deal Intake owns submission-time data. Pipeline CRM owns the deal lifecycle after acceptance. Other plugins own underwriting, offers, documents, signatures, funding, and automation rules.

## Events

- `algq_deal_intake_submission_created`
- `algq_deal_intake_pipeline_handoff_requested`
- `algq_deal_intake_deal_created`
- `algq_audit_event`

## REST namespace

`/wp-json/algq/v1`

REST creation is restricted to authenticated users with the review capability. Public lead creation uses the hardened browser form handler rather than an unrestricted anonymous REST route.
