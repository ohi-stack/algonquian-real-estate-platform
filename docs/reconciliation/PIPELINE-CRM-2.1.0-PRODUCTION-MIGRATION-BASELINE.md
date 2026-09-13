# Pipeline CRM 2.1.0 — Production Migration Baseline

**Recorded:** 2026-09-13  
**Plugin:** Algonquian Pipeline CRM  
**Production baseline:** 2.1.0  
**Baseline schema:** 2.1.0  
**Current canonical source:** 2.2.1  
**Current canonical schema:** 2.2.0  
**Status:** Required pre-upgrade baseline; 2.2.1 production deployment blocked until explicit migration is validated.

## Current state

The live WordPress inventory identifies Pipeline CRM **2.1.0** as the deployed production version. Canonical repository source has since advanced to **2.2.1** through the Platform 3.1.0 / Pipeline CRM 2.2.1 reconciliation.

This document establishes 2.1.0 as the production migration starting point. It does **not** downgrade current source and does not represent 2.2.1 as deployed.

## Baseline artifact

The supplied production package used to establish this baseline is:

- File: `algq-pipeline-crm-2.1.0-production(1).zip`
- SHA-256: `8244f27d55b21bcbf367f9dd4ceec36ba041c39202193d6b2e9f06454e97c81f`
- Extracted Git tree: `a4af4390fbd9e8b389d07b0e8aa3d0f475a31a97`
- Files: 34
- PHP files: 22
- PHP syntax validation: PASS

The package declares both plugin version and schema version as **2.1.0**.

## 2.1.0 authority boundary

Pipeline CRM 2.1.0 is authoritative for:

- canonical Deal identity;
- acquisition stage and stage history;
- assignment and priority;
- property/seller summary stored on the Deal;
- intake/source linkage;
- notes and Deal tasks;
- append-only Deal activity;
- Deal relationships;
- underwriting / offer / contract / buyer / funding / closing status summaries;
- next action and next-action due date;
- disposition / loss status.

MAO Engine remains authoritative for underwriting calculations. Offer Generator remains authoritative for offer/proposal records. Document Library and PDF & Signature retain document/signature authority. Deal Intake owns intake-time evidence before controlled handoff.

## Baseline tables

2.1.0 installs six Pipeline tables:

1. `wp_algq_deals`
2. `wp_algq_deal_stage_history`
3. `wp_algq_deal_notes`
4. `wp_algq_deal_tasks`
5. `wp_algq_deal_activity`
6. `wp_algq_deal_relationships`

The actual prefix is the active WordPress database prefix.

## Critical 2.1.0 Deal fields

The 2.1.0 Deal schema includes the following fields that must be preserved or explicitly transformed during upgrade:

`uuid`, `deal_number`, `title`, `property_address`, `municipality`, `state`, `postal_code`, `primary_contact_name`, `primary_contact_email`, `primary_contact_phone`, `assigned_user_id`, `stage_key`, `priority`, `acquisition_strategy`, `source`, `source_plugin`, `external_source_id`, `intake_submission_id`, `asking_price`, `offer_amount`, `underwriting_status`, `offer_status`, `contract_status`, `buyer_status`, `funding_status`, `closing_status`, `closing_date`, `disposition`, `loss_reason`, `next_action`, `next_action_due_at`, `created_by`, `updated_by`, `created_at`, `updated_at`, `last_activity_at`, `archived_at`, `deleted_at`, `record_version`.

## Migration blocker discovered

Current 2.2.1 source uses schema 2.2.0 and its Deal repository expects several different field names. This is not merely an additive schema change.

Required semantic mappings include at minimum:

| 2.1.0 field | 2.2.x target field |
| --- | --- |
| `stage_key` | `stage` |
| `primary_contact_name` | `primary_contact` |
| `acquisition_strategy` | `strategy` |
| `source_plugin` | `source_system` |
| `external_source_id` | `source_record_id` |
| `event_key` | `event` |
| `context` | `metadata_json` |

2.1.0 also contains fields with no direct equivalent in the current 2.2.1 Deal table definition, including seller email/phone, municipality/state/postal code, intake submission ID, underwriting status, offer status, next action, next-action due date, last activity timestamp, and soft-delete fields.

The 2.1.0 `algq_deal_relationships` table also does not map directly to the newer shared-CRM relationship tables.

**Decision:** `dbDelta()` by itself is not accepted as a safe production migration from 2.1.0 to 2.2.1. An explicit migration routine and verification procedure are required.

## Required 2.1.0 → 2.2.1 migration behavior

Before 2.2.1 can be deployed to production, the upgrade must:

1. Detect `algq_pipeline_schema_version = 2.1.0` before mutating schema state.
2. Take a verified database backup or transactionally equivalent rollback snapshot.
3. Record pre-migration row counts and key integrity metrics for all six 2.1.0 tables.
4. Create the 2.2.0 target tables/columns without discarding 2.1.0 source data.
5. Copy/transform renamed fields explicitly.
6. Preserve UUIDs, Deal numbers, IDs where contractually relied upon, source identity, stage state, assignments, timestamps and record versions.
7. Preserve next-action data or migrate it into an explicitly documented successor location.
8. Preserve Deal Intake linkage and idempotency keys.
9. Convert or retain 2.1.0 Deal relationships without silently dropping relationship evidence.
10. Preserve Deal activity with event/context semantics intact.
11. Verify every migrated Deal can be loaded through the 2.2.1 repository/service layer.
12. Verify stage counts, open tasks, notes, activity counts and closing/disposition state against the pre-migration baseline.
13. Verify Deal Intake creates or resolves exactly one canonical Deal after upgrade.
14. Verify no authoritative 2.1.0 data is silently orphaned or overwritten.
15. Only then update the stored schema version to 2.2.0.

## Pre-upgrade production capture

Immediately before a real production upgrade, capture:

- plugin version and schema option;
- database prefix;
- row counts for all six 2.1.0 tables;
- count of active vs archived/deleted Deals;
- count grouped by `stage_key`;
- count of Deals with `next_action` and overdue `next_action_due_at`;
- count with `intake_submission_id`;
- count with `external_source_id` / `source_plugin`;
- note/task/activity/relationship counts;
- minimum/maximum Deal IDs and creation timestamps;
- duplicate UUID/deal-number check;
- orphan relationship/task/note/activity check.

These values form the acceptance baseline for the migration test.

## Post-upgrade acceptance gate

A 2.1.0 → 2.2.1 migration passes only when:

- all canonical Deals remain addressable by expected identifiers;
- no duplicate Deals are created;
- stage distribution is preserved or intentionally mapped and documented;
- seller/property/source identity is preserved;
- intake handoff remains idempotent;
- open Deal tasks remain associated correctly;
- notes and activity remain accessible;
- next-action obligations remain visible and actionable;
- closing/disposition state is preserved;
- CRM contact/organization expansion does not take Deal authority away from Pipeline CRM;
- all migration queries are repeatable and safe against double execution;
- rollback has been tested on a copy of the production baseline.

## Production rule

**Pipeline CRM 2.1.0 is the required production migration baseline. Do not deploy Pipeline CRM 2.2.1 over production until an explicit 2.1.0 → 2.2.0 schema migration has passed the above checks.**

The current 2.2.1 source may remain canonical in GitHub; the live deployment state remains 2.1.0 until the migration is certified.
