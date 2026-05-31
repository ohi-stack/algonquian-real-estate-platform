# Algonquian MAO Engine

**Version:** 1.0.0  
**Author:** Onegodian | Algonquian Real Estate  
**Plugin Slug:** `algq-mao-engine`

## Purpose

The Algonquian MAO Engine calculates Maximum Allowable Offer and underwriting outputs for acquisition decisions. It supports wholesale, flip, and rental underwriting modes and is designed to connect Deal Intake, Pipeline CRM, Offer Generator, PDF Engine, Buyer Portal, and Command Center workflows.

## Core Functions

- ARV input
- Repair estimate input
- Holding cost input
- Fee and closing-cost assumptions
- Wholesale / Flip / Rental strategy mode
- MAO result
- Estimated spread
- Risk flag
- Saved underwriting records
- Admin dashboard
- Automatic page generation

## Shortcodes

```text
[algq_mao_calculator]
[algq_mao_plugin_page]
[algq_mao_plugin_page view="start"]
[algq_mao_plugin_page view="docs"]
```

## Automatic Pages

On activation, the plugin creates:

```text
/plugin/mao-engine
/plugin/mao-engine/start
/plugin/mao-engine/docs
/plugin/mao-engine/calculator
```

Each page is created with the appropriate shortcode already inserted.

## Admin Dashboard

The plugin registers a WordPress admin menu:

```text
MAO Engine
├── Dashboard
├── Underwriting
└── Settings
```

Dashboard metrics include:

- Underwritten deals
- Average MAO
- High-risk underwriting count

## Default Formula

```text
MAO = (ARV × ARV Multiplier) - Repairs - Holding Costs - Closing Costs - Desired Profit
```

For wholesale mode:

```text
MAO = MAO - Assignment Fee
```

## Default Assumptions

```text
ARV Multiplier: 0.70
Closing Cost Rate: 0.03
Default Holding Costs: 0
Default Desired Profit: 20000
Default Assignment Fee: 10000
Auto Move to Underwriting: Enabled
```

## Risk Flags

The engine returns one of:

- Acceptable
- Review
- High Risk

High Risk is triggered when MAO is non-positive or repairs exceed 35% of ARV. Review is triggered when estimated spread is below desired profit.

## Workflow

```text
Seller Intake
→ Deal Created
→ MAO Calculated
→ Underwriting Record Saved
→ Pipeline Status Updated
→ Offer Generator Receives Offer Range
→ Command Center Metrics Update
```

## REST API

```text
POST /wp-json/algq/v1/mao/calculate
```

Request example:

```json
{
  "arv": 250000,
  "repairs": 40000,
  "holding_costs": 3000,
  "desired_profit": 25000,
  "assignment_fee": 10000,
  "strategy": "wholesale"
}
```

Response example:

```json
{
  "arv": 250000,
  "repairs": 40000,
  "holding_costs": 3000,
  "closing_costs": 7500,
  "desired_profit": 25000,
  "assignment_fee": 10000,
  "strategy": "wholesale",
  "mao": 89500,
  "estimated_spread": 120500,
  "risk_flag": "Acceptable"
}
```

## Installation

1. Upload `algq-mao-engine.zip` in WordPress.
2. Activate the plugin.
3. Confirm the MAO pages were created.
4. Open **MAO Engine → Settings**.
5. Confirm formula assumptions.
6. Open `/plugin/mao-engine/calculator`.
7. Run a test underwriting calculation.

## Production Checklist

- Plugin activates without fatal error.
- Admin dashboard loads.
- Calculator shortcode renders.
- Auto-created pages publish correctly.
- Underwriting record saves.
- Database tables exist.
- No PHP notices with `WP_DEBUG=true`.
- Shortcodes return buffered HTML.
- Inputs are sanitized.
- Outputs are escaped.

## Data Tables

```text
wp_algq_deals
wp_algq_underwriting
```

The plugin preserves data on deactivation. Destructive uninstall must be opt-in only.
