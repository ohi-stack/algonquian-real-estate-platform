# Algonquian Pipeline CRM

**Version:** 2.0.0  
**Status:** Production upgrade candidate  
**Authority:** Canonical deal records and acquisition lifecycle

Algonquian Pipeline CRM owns the master deal record, controlled pipeline stages, assignments, priorities, notes, tasks, activity history, status transitions, closing status, and archive state for the Algonquian Real Estate platform.

## Production upgrade

Version 2.0.0 replaces the 1.0 custom-post-type MVP with:

- Versioned custom tables for canonical deal data.
- Stable deal UUIDs and human-readable deal numbers.
- Idempotent Deal Intake imports using source identities.
- Granular WordPress capabilities.
- Controlled transition rules and prerequisite hooks.
- Optimistic locking through `record_version`.
- Append-only stage history and activity records.
- Standard `algq/v1` REST routes.
- Shared platform audit-service integration.
- Legacy `algq_deal` custom-post-type migration.
- Idempotent WPBakery-compatible page generation.
- Responsive Kanban, dashboard, list, create, and settings interfaces.

## Shortcodes

- `[algq_pipeline_dashboard]`
- `[algq_pipeline_board]`
- `[algq_pipeline_activity]`

## Generated pages

- `/plugin/pipeline-crm/`
- `/plugin/pipeline-crm/start/`
- `/plugin/pipeline-crm/docs/`
- `/plugin/pipeline-crm/board/`

Generated pages are created once and are never overwritten after an administrator edits them.

## Canonical service functions

```php
algq_get_deal( $id_or_uuid );
algq_pipeline_create_deal( $data );
algq_pipeline_transition_deal( $deal_id, $stage, $context );
```

## REST API

- `GET /wp-json/algq/v1/deals`
- `POST /wp-json/algq/v1/deals`
- `GET /wp-json/algq/v1/deals/{id}`
- `PATCH /wp-json/algq/v1/deals/{id}`
- `POST /wp-json/algq/v1/deals/{id}/stage`

Every route has a capability-based permission callback. Stage writes require the current `record_version`.

## Data retention

Deactivation preserves all records. Uninstall preserves data unless an authorized administrator explicitly enables complete cleanup in Pipeline Settings before uninstalling.
