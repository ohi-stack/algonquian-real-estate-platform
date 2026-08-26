# ARE Platform Service Interface

## Purpose

`ARE_Platform_Service_Interface` is the shared in-process contract for authoritative operations across the Algonquian Real Estate WordPress plugin ecosystem.

The Platform owns only the contract, provider discovery, safe dispatch, and service-level diagnostics. It does **not** become the owner of domain records.

Authoritative ownership remains unchanged:

- Deal Intake owns intake/submission records.
- Pipeline CRM owns the canonical Deal and controlled deal state.
- MAO Engine owns underwriting calculations.
- Offer Generator owns offer/proposal records.
- Document Library owns controlled document records.
- PDF & Signature owns protected PDF/signature workflow.
- Funding Tracker owns funding/capital records.
- Automation Engine owns scheduled/event execution.
- Command Center reads/report results but does not replace domain authorities.
- ARE Agent Engine will orchestrate these services but must not create a competing deal database.

## Core contract

Every provider implements:

```php
interface ARE_Platform_Service_Interface {
    public function id(): string;
    public function version(): string;
    public function operations(): array;
    public function call( string $operation, array $payload = array(), array $context = array() );
    public function health(): array;
}
```

Shared helper functions:

```php
algq_platform_register_service( $provider );
algq_platform_service( 'pipeline.deals' );
algq_platform_service_call( 'pipeline.deals', 'get', array( 'deal_id' => 123 ) );
algq_platform_services( true );
```

Unknown services and unsupported operations return `WP_Error`; they do not fail with a fatal error.

A different provider cannot silently replace an already registered service ID. That prevents two plugins from claiming the same authoritative domain.

## Canonical service IDs

Initial live service:

- `pipeline.deals` — authoritative Pipeline CRM deal service.

Reserved future service families should follow the same namespaced pattern, for example:

- `intake.submissions`
- `mao.underwriting`
- `offers.proposals`
- `documents.library`
- `pdf.signatures`
- `automation.workflows`
- `buyers.portal`
- `marketplace.deals`
- `funding.capital`
- `command.reporting`

Do not register speculative services until the owning plugin exposes a real, documented, repeatable operation.

## `pipeline.deals` operations

The Pipeline CRM adapter currently exposes:

- `get`
- `create`
- `update`
- `transition`
- `query`
- `activity`

### Get

```php
$result = algq_platform_service_call(
    'pipeline.deals',
    'get',
    array( 'deal_id' => 123 ),
    array( 'caller_plugin' => 'algq-agent-engine' )
);
```

The provider may resolve a deal by canonical numeric `deal_id`; current CRM generations may additionally support UUID or deal number through `identifier`.

### Create

Stable cross-plugin fields include:

- `title`
- `property_address`
- `municipality`
- `state`
- `postal_code`
- `primary_contact_name`
- `primary_contact_email`
- `primary_contact_phone`
- `assigned_user_id`
- `stage`
- `priority`
- `strategy`
- `source`
- `source_system`
- `source_record_id`
- `intake_submission_id`
- `asking_price`
- `offer_amount`

The Pipeline provider normalizes these fields into the currently installed Pipeline CRM schema. Repeated `source_system + source_record_id` create requests must resolve to the same canonical Deal rather than create a duplicate.

The `create` operation returns the canonical deal record when successful. Callers that persist a link must store the returned record's `id` as `deal_id`.

### Update

```php
algq_platform_service_call(
    'pipeline.deals',
    'update',
    array(
        'deal_id' => 123,
        'expected_version' => 7,
        'changes' => array(
            'priority' => 'high',
            'next_action' => 'Call seller',
        ),
    )
);
```

Where supported by the CRM generation, `expected_version` participates in optimistic concurrency control. Callers should pass the last observed record version for material updates.

### Transition

```php
algq_platform_service_call(
    'pipeline.deals',
    'transition',
    array(
        'deal_id' => 123,
        'target' => 'underwriting',
        'expected_version' => 7,
        'reason' => 'Qualification complete',
    ),
    array( 'caller_plugin' => 'algq-agent-engine' )
);
```

The provider delegates to Pipeline CRM's own transition rules. The service interface does not bypass approval gates or domain validation.

## Deal Intake handoff

Accepted Deal Intake submissions now call `pipeline.deals:create` first. The handoff uses:

- `source_system = algq-deal-intake`
- `source_record_id = submission_id`
- `intake_submission_id = submission_id`

Deal Intake explicitly extracts the returned canonical `id` instead of casting the full CRM return value to an integer.

If the shared service is unavailable, Deal Intake may use the prior direct Pipeline helper as a compatibility fallback. If Pipeline returns a real domain error, the submission remains `awaiting_pipeline`; the error is not silently converted into an invalid Deal ID.

## ARE Agent Engine rule

The future `algq-agent-engine` should consume this contract through tool/service adapters.

The governing pattern is:

`Agent Engine decides what needs to happen → owning plugin service performs the authoritative operation → Pipeline CRM records canonical transaction state → Automation Engine executes scheduled/event work → Command Center reports the result.`

Agent Engine must never write directly to another plugin's private tables when an authoritative Platform service exists.

## Release status

This document describes the source-level service contract. A merge is **not** live-production certification. Production certification still requires WordPress runtime testing of:

1. Platform loads and initializes the service registry.
2. Pipeline registers exactly one `pipeline.deals` provider.
3. Deal Intake acceptance creates one canonical deal.
4. Repeated acceptance/source replay remains idempotent.
5. The returned `deal_id` matches the actual CRM row.
6. Invalid transitions return `WP_Error` and do not bypass CRM rules.
7. Optimistic-lock conflicts are surfaced.
8. Audit/service events are emitted without logging sensitive payloads.
