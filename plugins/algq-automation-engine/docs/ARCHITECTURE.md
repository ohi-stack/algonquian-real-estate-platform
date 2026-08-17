# Automation Engine Architecture

## Flow

```text
Platform event
  -> registered trigger
  -> active rule lookup
  -> condition evaluation
  -> idempotent queue job
  -> locked execution
  -> controlled action
  -> completed, retry, or dead-letter state
  -> local and platform audit event
```

## Data authority

The plugin owns:

- automation rules
- automation jobs
- retries and dead-letter status
- local automation logs
- automation-created task records

The plugin does not own canonical deal, offer, document, signature, buyer, funding, or payment records.

## Condition format

```json
[
  {
    "field": "payload.new_status",
    "operator": "equals",
    "value": "closed"
  }
]
```

Supported operators: `equals`, `not_equals`, `in`, `not_in`, `exists`, `empty`, `contains`, `gt`, `gte`, `lt`, and `lte`.

## Action payload example

```json
{
  "title": "Prepare closing report for deal {{object_id}}",
  "description": "Created by rule {{rule_id}} after {{event_key}}.",
  "priority": "high"
}
```

## Failure handling

Failed jobs retry with exponential backoff. When attempts reach the rule limit, the job enters `dead` status. Authorized administrators can retry the job from the queue screen or REST API.
