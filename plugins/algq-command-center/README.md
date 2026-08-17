# Algonquian Admin Command Center

**Version:** 1.2.0  
**Author:** Onegodian  
**Division:** Algonquian Real Estate Technology Division

The Admin Command Center is the executive oversight layer for the Algonquian Real Estate Platform. It aggregates read-only operational intelligence from specialized plugins while leaving authoritative records in their source systems.

## 1.2.0 capabilities

- Executive KPI dashboard
- Pipeline and acquisition monitoring
- Buyer, funding, document, signature, and automation visibility
- Platform/plugin health monitoring
- Audit-provider visibility bridge
- Capability-protected CSV export
- Print/PDF-ready executive report
- Safe administrative commands
- Granular WordPress capabilities
- Idempotent generated pages
- WPBakery-compatible page content

## Shortcodes

- `[algq_command_center]`
- `[algq_admin_dashboard]`
- `[algq_command_center_kpis]`
- `[algq_command_center_pipeline]`
- `[algq_command_center_activity]`
- `[algq_command_center_health]`
- `[algq_command_center_overview]`
- `[algq_command_center_start]`
- `[algq_command_center_docs]`

## Generated routes

- `/command-center/`
- `/plugin-command-center/`
- `/plugin-command-center-start/`
- `/plugin-command-center-docs/`

Generated pages are created only when missing and do not overwrite administrator-edited pages.

## Security

Administrative operations use granular capabilities and nonces. Report exports require `export_algq_reports`; commands require `run_algq_system_commands`; audit visibility requires `view_algq_audit_logs`.

## Data authority

Command Center does not own canonical deals, underwriting, offers, funding, documents, signatures, buyers, or automation rules. Those remain with the specialized plugins.
