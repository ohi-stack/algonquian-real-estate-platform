# Algonquian Admin Command Center

Release Status: 1.0.0 Release Candidate

## Purpose
Algonquian Admin Command Center is the executive operations dashboard for the Algonquian Real Estate plugin suite. It centralizes KPIs, plugin health, reports, audit visibility, revenue snapshots, and system status.

## Core Features
- Executive KPI dashboard
- Plugin health monitor
- Audit log integration plan
- CSV export handler plan
- PDF report handler plan
- WooCommerce revenue dashboard plan
- Stripe subscription dashboard plan
- Role-based widget visibility plan
- Automatic page generation
- Shortcode rendering
- Admin dashboard and settings screens

## Automatic Pages
On activation, the plugin creates or verifies:

- `/dashboard` with `[algq_command_center]`
- `/plugin/command-center` with `[algq_command_center_overview]`
- `/plugin/command-center/start` with `[algq_command_center_start]`
- `/plugin/command-center/docs` with `[algq_command_center_docs]`

## Shortcodes
- `[algq_command_center]`
- `[algq_command_center_overview]`
- `[algq_command_center_start]`
- `[algq_command_center_docs]`
- `[algq_command_center_kpis]`
- `[algq_command_center_pipeline]`
- `[algq_command_center_activity]`

## Admin Screens
- Dashboard
- Deals
- Pipeline
- Funding
- Buyers
- Documents
- Automation
- Reports
- Plugins
- Settings
- System Health

## Security
The plugin uses WordPress capability checks, nonces, sanitization, and output escaping. Protected admin actions require the configured management capability.

## Branding
Uses Algonquian Real Estate navy, gold, black, white, and blue visual standards.

## Production Notes
This plugin is in production-hardening. Missing companion plugins must degrade gracefully and must not create fatal dependency chains.
