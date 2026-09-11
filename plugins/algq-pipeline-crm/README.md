# Algonquian Pipeline CRM

**Version:** 2.2.0  
**Status:** Feature upgrade candidate  
**Authority:** Canonical Deal records, acquisition lifecycle, and shared ARE CRM relationship layer

Algonquian Pipeline CRM owns the master Deal record, controlled pipeline stages, assignments, priorities, notes, tasks, activity history, status transitions, closing status, archive state, and the shared relationship CRM layer used across Algonquian Real Estate operations.

The relationship layer provides common contacts, organizations, relationship classifications, activities, non-Deal follow-up tasks, next actions, and controlled links to canonical Deals or specialized plugin records.

It does **not** take authority away from companion systems. Buyer Portal remains authoritative for buyer profiles/criteria and protected access; Funding Tracker remains authoritative for capital-source criteria, commitments and deal funding; Deal Marketplace owns marketplace access/response records; Deal Intake owns submission-time records; and other specialized plugins retain their documented domains.

## Version 2.2.0 — relationship CRM foundation

- Added shared CRM contacts and organizations.
- Added relationship classifications for sellers, property owners, buyers, capital sources, lenders, equity/JV partners, professionals, vendors, referral sources and stewardship clients.
- Added contact ownership, status, priority, source, relationship strength, tags, last activity, next action and next-action date.
- Added controlled links from contacts/organizations to canonical Deals and authoritative external plugin records.
- Added relationship activity history for calls, emails, meetings, notes, appointments and outreach events.
- Added non-Deal relationship tasks while preserving the existing canonical Deal-task system for transaction-specific work.
- Added idempotent source identities for integrations such as Deal Intake, Buyer Portal, Funding Tracker and Property Stewardship.
- Added shared PHP service functions and integration hooks.
- Added platform and plugin CRM architecture documentation.
- Preserved the rule that final acquisition strategy, binding offers, capital commitments, funds movement and closing authority remain human-controlled.

## Version 2.0.0 production architecture

Version 2.0.0 replaced the 1.0 custom-post-type MVP with:

- Versioned custom tables for canonical Deal data.
- Stable Deal UUIDs and human-readable deal numbers.
- Idempotent Deal Intake imports using source identities.
- Granular WordPress capabilities.
- Controlled transition rules and prerequisite hooks.
- Optimistic locking through `record_version`.
- Append-only stage history and activity records.
- Standard `algq/v1` REST routes.
- Shared platform audit-service integration.
- Legacy `algq_deal` custom-post-type migration.
- Idempotent WPBakery-compatible page generation.
- Responsive Kanban, dashboard, list, create and settings interfaces.

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

## Canonical Deal service functions

```php
algq_get_deal( $id_or_uuid );
algq_pipeline_create_deal( $data );
algq_pipeline_transition_deal( $deal_id, $stage, $context );
```

## Shared CRM service functions

```php
algq_crm_get_contact( $id_or_uuid );
algq_crm_upsert_contact( $data );
algq_crm_create_organization( $data );
algq_crm_link_relationship( $data );
algq_crm_add_activity( $contact_id, $data );
algq_crm_create_task( $data );
```

Deal-specific tasks must continue to use the canonical Pipeline Deal task system. `algq_crm_create_task()` rejects Deal-specific work so the platform does not maintain two competing task records for the same transaction.

## REST API

Existing Deal routes remain:

- `GET /wp-json/algq/v1/deals`
- `POST /wp-json/algq/v1/deals`
- `GET /wp-json/algq/v1/deals/{id}`
- `PATCH /wp-json/algq/v1/deals/{id}`
- `POST /wp-json/algq/v1/deals/{id}/stage`

Every route has a capability-based permission callback. Stage writes require the current `record_version`.

The relationship layer is exposed first through PHP service functions/hooks so companion plugins can integrate without opening additional public REST surface before authorization and staging tests are completed.

## Documentation

- `docs/getting-started.md`
- `docs/technical-reference.md`
- `docs/crm-relationship-architecture.md`
- repository-wide `docs/CRM-ARCHITECTURE.md`

## Data retention

Deactivation preserves all records. Uninstall preserves data unless an authorized administrator explicitly enables complete cleanup in Pipeline Settings before uninstalling.
