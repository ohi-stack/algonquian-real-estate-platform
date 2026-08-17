# Algonquian MAO Engine

**Version:** 2.0.0  
**Plugin slug:** `algq-mao-engine`  
**Authority:** Underwriting scenarios only. Pipeline CRM remains authoritative for canonical deal records.

## Production upgrade

Version 2.0.0 replaces the original single-form calculator with a controlled underwriting subsystem:

- Wholesale, fix-and-flip, rental, and multifamily formulas.
- Versioned formula and assumption snapshots.
- Deterministic conservative, base, and optimistic sensitivity cases.
- Draft and approved scenario states with approval evidence.
- Granular WordPress capabilities.
- Public calculation without public persistence.
- REST validation and an IP-scoped calculation rate limit.
- Conditional asset loading.
- Idempotent nested page generation using valid WPBakery closing shortcodes.
- Platform health registration and audit events.
- Pipeline CRM, Offer Generator, Automation Engine, and Command Center bridge contracts.
- Approved underwriting only is exposed to offer-generation workflows.

## Capabilities

- `view_algq_underwriting`
- `manage_algq_underwriting`
- `approve_algq_underwriting`
- `manage_algq_mao_settings`

## Shortcodes

```text
[algq_mao_calculator]
[algq_mao_plugin_page]
[algq_mao_plugin_page view="start"]
[algq_mao_plugin_page view="docs"]
```

## Generated pages

```text
/plugin/mao-engine/
/plugin/mao-engine/start/
/plugin/mao-engine/docs/
/plugin/mao-engine/calculator/
```

The generator creates missing parent pages, does not overwrite existing content, and uses:

```text
[vc_column_text]
[algq_mao_calculator]
[/vc_column_text]
```

## REST API

Public, non-persistent calculation:

```text
POST /wp-json/algq/v1/mao/calculate
```

Authorized scenario read:

```text
GET /wp-json/algq/v1/mao/scenarios/{id}
```

## Data ownership

The plugin owns `wp_algq_underwriting`. It does not create, duplicate, or directly own `wp_algq_deals`. Deal-stage changes are requested through `algq_pipeline_stage_change_requested` so Pipeline CRM can enforce its own workflow rules.

## Approval control

Saving creates a draft. A user with `approve_algq_underwriting` may approve the scenario. Only approved scenarios are added to Offer Generator payloads or emitted as `algq_mao_offer_ready`.

## Important limitation

Outputs are analytical estimates based on supplied assumptions. They are not appraisals, lending decisions, legal advice, guaranteed returns, or binding offers. Each transaction requires independent due diligence and appropriate professional review.

## Validation required before production deployment

- Activate on a disposable WordPress 6.5+ / PHP 8.1+ environment.
- Run database migration from version 1.0.0.
- Verify legacy records remain readable.
- Test all capabilities and approval transitions.
- Test public rate limiting and REST validation.
- Test each strategy against independently calculated fixtures.
- Verify Pipeline CRM and Offer Generator contract compatibility.
- Confirm generated pages are nested and existing administrator content is preserved.
- Confirm no PHP notices with `WP_DEBUG` enabled.
