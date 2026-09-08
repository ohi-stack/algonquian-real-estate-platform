# Pipeline CRM Technical Reference

## Authority

Pipeline CRM owns:

- canonical Deal records and Deal IDs;
- acquisition stages and lifecycle state;
- Deal assignments, notes, tasks, activity and closing status; and
- the shared ARE CRM relationship layer for contacts, organizations, relationship classifications, relationship activity, non-Deal follow-up tasks and controlled links to authoritative external records.

Pipeline CRM does not take authority from specialized companion plugins. Buyer criteria, capital commitments, underwriting, offers, documents, marketplace access, signatures and automation rules remain in their authoritative systems.

## Authoritative tables

### Canonical Deal tables

- `wp_algq_deals`
- `wp_algq_deal_stage_history`
- `wp_algq_deal_notes`
- `wp_algq_deal_tasks`
- `wp_algq_deal_activity`

### Shared CRM relationship tables

- `wp_algq_crm_contacts`
- `wp_algq_crm_organizations`
- `wp_algq_crm_relationships`
- `wp_algq_crm_activity`
- `wp_algq_crm_tasks`

The `wp_` prefix above is illustrative. Runtime tables use the active WordPress database prefix.

## Canonical Deal service functions

- `algq_get_deal( $id_or_uuid_or_deal_number )`
- `algq_pipeline_create_deal( $data )`
- `algq_pipeline_transition_deal( $deal_id, $stage, $context )`

Deal Intake should call `algq_pipeline_create_deal()` with `source_system` and `source_record_id` to obtain idempotent creation.

MAO Engine, Offer Generator, Document Library, Funding Tracker, Buyer Portal, Automation Engine, and Command Center should resolve records through `algq_get_deal()` and use the canonical numeric Deal ID.

External stage requests should call `algq_pipeline_transition_deal()` or fire `algq_pipeline_stage_change_requested`. Direct table updates are not supported.

## Shared CRM service functions

- `algq_crm_get_contact( $id_or_uuid )`
- `algq_crm_upsert_contact( $data )`
- `algq_crm_create_organization( $data )`
- `algq_crm_link_relationship( $data )`
- `algq_crm_add_activity( $contact_id, $data )`
- `algq_crm_create_task( $data )`

The CRM functions are internal PHP integration contracts. This release intentionally does not expose new public CRM REST endpoints; companion plugins should integrate through the service functions and hooks until the relationship authorization model completes staging validation.

`algq_crm_create_task()` is reserved for relationship follow-up that is not transaction-specific. It rejects Deal-specific tasks so one transaction cannot have competing task authorities.

## Relationship classifications

Built-in values:

- `seller`
- `property_owner`
- `buyer`
- `capital`
- `lender`
- `equity_partner`
- `joint_venture_partner`
- `professional`
- `vendor`
- `referral_source`
- `stewardship_client`
- `other`

Additional values may be registered through `algq_pipeline_crm_relationship_types` without changing record authority.

## Events

### Deal lifecycle

- `algq_pipeline_loaded`
- `algq_pipeline_deal_created`
- `algq_pipeline_deal_updated`
- `algq_pipeline_stage_changed`
- `algq_pipeline_stage_change_failed`
- `algq_pipeline_stage_change_requested`

### Relationship CRM

- `algq_pipeline_crm_relationship_layer_loaded`
- `algq_pipeline_crm_contact_created`
- `algq_pipeline_crm_contact_updated`
- `algq_pipeline_crm_organization_created`
- `algq_pipeline_crm_relationship_created`
- `algq_pipeline_crm_activity_created`
- `algq_pipeline_crm_task_created`

## Filters

### Deal lifecycle

- `algq_pipeline_stages`
- `algq_pipeline_allowed_transitions`
- `algq_pipeline_validate_transition`
- `algq_pipeline_deal_stage_payload`

### Relationship CRM

- `algq_pipeline_crm_relationship_types`

## Integration contracts

### Deal Intake

May create or link seller/property-owner CRM contacts during accepted intake. The canonical Deal is still created through `algq_pipeline_create_deal()`.

### Buyer Portal

May link a Buyer Portal profile to a shared CRM contact. Buyer Portal remains authoritative for buyer registration, acquisition criteria, protected access and buyer-profile records.

### Funding Tracker

May link capital sources, lenders, equity partners and JV partners to shared contacts/organizations. Funding Tracker remains authoritative for capital criteria, commitments and deal-level funding status.

### Deal Marketplace

May read authorized buyer/deal relationships. Marketplace visibility, NDA, access, response and offer-submission records remain under Deal Marketplace authority.

### Property Stewardship

May link owner-authorized clients and relevant organizations. Property Stewardship retains its service-record authority.

### Automation Engine

May create approved relationship follow-ups or respond to CRM hooks. Automation rules and execution history remain under Automation Engine authority.

### Command Center

May aggregate CRM KPIs, due follow-ups and relationship activity. Command Center must not persist competing CRM, Deal, funding or buyer records.

## Security and audit

UI, REST or automation callers must enforce the applicable WordPress capability before mutating CRM data. The PHP service layer sanitizes and validates inputs and emits audit/integration events but does not replace caller-level authorization.

Where the shared Platform audit service is available, CRM create/update/link operations emit centralized audit events. Otherwise the plugin emits the `algq_audit_event` compatibility hook.

## Data retention

Deactivation preserves all Deal and relationship records. Full cleanup is performed only when an authorized administrator explicitly enables `delete_data_on_uninstall` before uninstalling Pipeline CRM.
