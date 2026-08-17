# Admin Command Center Architecture

The Command Center is a read-only executive aggregation layer plus a limited safe-command surface.

## Authoritative systems

- Deal Intake: submissions and intake evidence
- Pipeline CRM: canonical deals and stages
- MAO Engine: underwriting
- Offer Generator: offers
- Funding Tracker: funding records
- Buyer Portal / Marketplace: buyer authorization and deal access
- Document Library: controlled documents
- PDF & Signature Engine: rendered files and signature workflows
- Automation Engine: rules and executions
- Platform Plugin: shared registry, permissions, audit, mail, storage, and other shared services

## Extension points

- `algq_command_center_metrics`
- `algq_command_center_activity`
- `algq_command_center_pipeline_stages`
- `algq_command_center_funding_summary`
- `algq_command_center_pipeline_value`
- `algq_command_center_health_checks`
- `algq_command_center_plugin_registry`
- `algq_command_center_widget_registry`
- `algq_command_center_audit_events`
- `algq_command_center_command_executed`
- `algq_command_center_audit_event`
