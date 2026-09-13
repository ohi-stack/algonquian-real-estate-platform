# Pipeline CRM 2.1.0 → 2.2.0 Migration Contract

## Status

This document defines the production migration implemented on the Pipeline CRM 2.2 schema line and shipped from the current 2.2.1 runtime patch level.

- Production starting point: Pipeline CRM 2.1.0 / schema 2.1.0
- Target schema: 2.2.0
- Current plugin runtime: 2.2.1
- Canonical Deal authority remains Pipeline CRM.
- Shared contacts, organizations, relationships, relationship activity, and non-Deal follow-up tasks are added without moving specialized records out of their authoritative plugins.

## Migration behavior

The upgrader does not treat `dbDelta()` as a semantic rename mechanism. When the installed schema is exactly 2.1.0 it performs this sequence:

1. Validate the expected 2.1 Deal columns.
2. Reject duplicate `(source_plugin, external_source_id)` identities before adding the new unique source key.
3. Reject lifecycle stage values not recognized by the current Pipeline stage registry.
4. Record Deal, activity, and legacy relationship counts.
5. Add the 2.2 columns and CRM tables without changing `algq_pipeline_schema_version`.
6. Copy 2.1 Deal semantics into the 2.2 names.
7. Copy 2.1 activity event/context semantics into the 2.2 event/metadata fields.
8. Create one idempotent shared seller/property-owner contact per Deal when contact evidence exists.
9. Link each migrated seller/property-owner contact back to its canonical Deal.
10. Verify Deal count, activity count, stage mapping, contact-name mapping, source identity mapping, contact migration, and seller relationship migration.
11. Advance `algq_pipeline_schema_version` to 2.2.0 only after verification succeeds.

If any preflight or verification check fails, Pipeline CRM records the migration error, displays an administrator error notice, emits an audit event, and does not boot the operational CRM services.

## 2.1 → 2.2 semantic mappings

| 2.1 field | 2.2 field | Policy |
| --- | --- | --- |
| `stage_key` | `stage` | Exact lifecycle value retained |
| `primary_contact_name` | `primary_contact` | Exact value retained |
| `acquisition_strategy` | `strategy` | Exact value retained |
| `source_plugin` | `source_system` | Exact source identity retained |
| `external_source_id` | `source_record_id` | Exact source identity retained |
| `event_key` | `event` | Exact activity event retained |
| `context` | `metadata_json` | Existing activity context retained |

## Operational fields retained from 2.1

The 2.2 Deal schema intentionally retains these 2.1 operational fields instead of dropping them during relationship-layer expansion:

- municipality
- state
- postal code
- primary contact email
- primary contact phone
- intake submission ID
- underwriting status
- offer status
- next action
- next-action due time
- last activity time
- archived state
- deleted/soft-delete state

This preserves the current acquisition operating model and prevents a relationship CRM upgrade from degrading Deal execution data.

## Legacy Deal relationships

`wp_algq_deal_relationships` is not automatically rewritten into the new shared CRM relationship table because the two models do not have identical semantics. The legacy table is retained as migration evidence and remains available for controlled reconciliation. Companion plugins should reassert authoritative external relationships using their own source identities.

The migration automatically creates only the relationship that can be established safely from the 2.1 Deal itself: the Deal's primary seller/property-owner contact linked back to that canonical Deal.

## Deal Intake 2.1 compatibility

The deployed Deal Intake 2.1 plugin uses the legacy nested handoff payload and then applies the `algq_pipeline_create_deal` filter. Pipeline CRM registers a compatibility adapter on that filter so the nested seller/property payload is normalized to the 2.2 Deal service, source identity remains idempotent, and the actual canonical Deal ID is returned to Deal Intake.

The compatibility bridge does not transfer Deal ownership to Deal Intake. Deal Intake remains authoritative for the submission record; Pipeline CRM remains authoritative for the accepted Deal.

## Acceptance gate

Before production deployment from 2.1.0, verify on a production-equivalent database:

- database backup completed;
- preflight baseline SQL captured;
- no duplicate source identities;
- upgrade completes without `algq_pipeline_migration_error`;
- Deal count unchanged;
- stage distribution unchanged;
- source identities retained;
- next actions and due dates retained;
- archived/deleted states retained;
- activity count and event keys retained;
- migrated seller contacts equal eligible Deal contacts;
- migrated seller relationships link to correct Deal IDs;
- legacy Deal relationships remain present;
- Deal Intake acceptance produces exactly one canonical Deal;
- repeated Deal Intake handoff is idempotent;
- Pipeline dashboard/board/activity interfaces render;
- capability and REST authorization tests pass;
- Platform service registry can resolve Pipeline CRM operations;
- uninstall-retention policy does not delete production records unless explicitly configured.

## Rollback boundary

The migration is intentionally additive and preserves legacy 2.1 columns/tables. However, once users create or update records through 2.2 code, a code rollback to 2.1 is not considered a complete data rollback. Restore the pre-upgrade database backup for a true rollback.
