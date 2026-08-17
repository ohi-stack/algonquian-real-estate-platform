# Pipeline CRM Technical Reference

## Authoritative tables

- `wp_algq_deals`
- `wp_algq_deal_stage_history`
- `wp_algq_deal_notes`
- `wp_algq_deal_tasks`
- `wp_algq_deal_activity`

## Events

- `algq_pipeline_loaded`
- `algq_pipeline_deal_created`
- `algq_pipeline_deal_updated`
- `algq_pipeline_stage_changed`
- `algq_pipeline_stage_change_failed`
- `algq_pipeline_stage_change_requested`

## Filters

- `algq_pipeline_stages`
- `algq_pipeline_allowed_transitions`
- `algq_pipeline_validate_transition`
- `algq_pipeline_deal_stage_payload`

## Compatibility contracts

Deal Intake should call `algq_pipeline_create_deal()` with `source_system` and `source_record_id` to obtain idempotent creation.

MAO Engine, Offer Generator, Document Library, Funding Tracker, Buyer Portal, Automation Engine, and Command Center should resolve records through `algq_get_deal()` and use the canonical numeric deal ID.

External stage requests should call `algq_pipeline_transition_deal()` or fire `algq_pipeline_stage_change_requested`. Direct table updates are not supported.
