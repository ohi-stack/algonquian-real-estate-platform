# Algonquian Funding Tracker

**Version:** 1.0.0  
**Status:** Production foundation  
**Author:** Onegodian | Algonquian Real Estate

Algonquian Funding Tracker is the capital-relationship and deal-funding module for the Algonquian Real Estate Platform. It replaces the earlier `0.1.0` scaffold with a functional, permission-controlled system for documenting capital sources, funding requests, commitments, funded amounts, financing terms, and activity.

## Operational authority

The plugin owns:

- capital-source relationship records;
- lender, private-lender, CDFI, equity, joint-venture, seller-financing, grant, and internal-capital classifications;
- deal-level funding requests and commitments;
- requested, committed, and funded amounts;
- financing terms and conditions;
- funding activity records and KPI summaries.

The plugin does **not** transfer money, originate loans, replace accounting records, create securities offerings, or replace executed legal documents.

## Core features

- Versioned activation schema using `dbDelta()`.
- Three plugin-owned tables for capital sources, commitments, and append-only activity.
- Granular capabilities: `manage_algq_funding`, `view_algq_funding`, `edit_algq_funding`, and `export_algq_funding`.
- Admin dashboard with KPI cards, capital-source intake, funding-record intake, status and amount updates, tables, badges, progress indicators, and CSV export.
- REST routes under `algq/v1` with permission callbacks.
- Central audit-service integration through `algq_audit_event`.
- Idempotent WPBakery-compatible page generation.
- Non-blocking standalone compatibility mode when the Platform Plugin is unavailable.
- Data-preserving uninstall policy by default.

## Shortcodes

```text
[algq_funding_tracker]
[algq_funding_dashboard]
[algq_capital_sources]
```

WPBakery placement:

```text
[vc_column_text]
[algq_funding_dashboard]
[/vc_column_text]
```

## Generated pages

- `/funding-dashboard/`
- `/plugin/funding-tracker/`
- `/plugin/funding-tracker/start/`
- `/plugin/funding-tracker/docs/`

Generated pages are created only when absent and are never overwritten during ordinary activation.

## REST API

- `GET /wp-json/algq/v1/funding/summary`
- `GET|POST /wp-json/algq/v1/funding/sources`
- `GET|POST /wp-json/algq/v1/funding/commitments`

## Installation

1. Install and activate the Algonquian Real Estate Platform Plugin when available.
2. Upload the `algq-funding-tracker` directory or packaged ZIP to WordPress.
3. Activate the plugin.
4. Confirm Funding Tracker capabilities are assigned to authorized roles.
5. Add capital sources before creating deal-level funding records.
6. Test admin, shortcode, REST, audit, and generated-page behavior in staging.

## Production validation still required

- Clean-site activation and migration testing.
- Cross-plugin Deal ID validation against Pipeline CRM.
- Capability matrix testing for non-administrator roles.
- REST authentication and authorization tests.
- Multisite review if network activation will be supported.
- Export workflows and report reconciliation.
- End-to-end testing with Automation Engine and Admin Command Center.
