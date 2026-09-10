# Algonquian MAO Engine

**Version:** 2.1.0  
**Plugin slug:** `algq-mao-engine`  
**Authority:** Underwriting and financing-analysis scenarios only. Pipeline CRM remains authoritative for canonical deal records.

## Version 2.1.0 upgrade

The MAO Engine is the primary analytical owner for seller-financing acquisition structures. Seller financing is implemented as a first-class underwriting strategy, not as a separate plugin or duplicate deal record.

### Seller-financing inputs

- Purchase price
- Down payment
- Seller-financed principal
- Interest rate
- Amortization period
- Balloon / maturity period
- Optional monthly-payment override
- Annual gross and other income
- Annual operating expenses
- Refinance value assumption
- Refinance interest rate
- Refinance amortization
- Refinance LTV
- Conventional financing down payment
- Conventional interest rate
- Conventional amortization

### Seller-financing outputs

- Computed seller-financed principal
- Monthly principal-and-interest payment
- Annual debt service
- Balloon balance at maturity
- Total debt service through balloon payoff
- Net operating income (NOI)
- Annual cash flow after debt service
- Debt-service coverage ratio (DSCR)
- Refinance capacity
- Refinance loan amount
- Refinance funding gap
- Refinance payment estimate
- Conventional loan principal and payment
- Conventional annual debt service
- Conventional cash flow and DSCR
- Monthly payment savings versus conventional financing
- Upfront cash savings versus conventional financing
- Conservative, base, and optimistic refinance sensitivity cases
- Risk flags for low DSCR, negative cash flow, short balloon periods, refinance gaps, and seller debt service that exceeds conventional debt service

## Existing underwriting strategies

- Wholesale
- Fix and flip
- Rental
- Multifamily
- Seller financing

All strategies retain versioned formula and assumption snapshots, deterministic sensitivity cases, draft/approved states, granular capabilities, REST validation, audit events, and platform integrations.

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

The generator creates missing parent pages, does not overwrite existing content, and uses valid WPBakery syntax:

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

The plugin owns `wp_algq_underwriting`. It does not create, duplicate, or directly own `wp_algq_deals`. Deal-stage changes are requested through the platform bridge so Pipeline CRM can enforce its own workflow rules.

Seller-financing results are underwriting records. After approval, downstream systems may consume those results:

- Offer Generator: seller-financing proposal and term-sheet preparation
- Funding Tracker: financing-source and debt-obligation tracking
- Document Library: approved underwriting and financing package storage
- Automation Engine: maturity, review, and follow-up events

Those systems do not recalculate or become authoritative for the underwriting result.

## Approval control

Saving creates a draft. A user with `approve_algq_underwriting` may approve the scenario. Approved scenarios can be exposed to downstream offer-generation workflows.

## Important limitation

Outputs are analytical estimates based on supplied assumptions. They are not appraisals, loan commitments, tax advice, legal advice, guaranteed returns, or binding offers. Seller-financing notes, mortgages, servicing arrangements, disclosures, and closing documents require transaction-specific professional review.

## Validation required before production deployment

- Activate on a disposable WordPress 6.5+ / PHP 8.1+ environment.
- Run database migration from schema 2.0.0 to 2.1.0.
- Verify legacy underwriting records remain readable.
- Test all capabilities and approval transitions.
- Test public rate limiting and REST validation.
- Independently verify amortization, balloon-balance, DSCR, refinance-capacity, and conventional-comparison fixtures.
- Test 0% interest, full amortization, short balloon, no-income, refinance-gap, and negative-cash-flow cases.
- Verify Pipeline CRM, Offer Generator, Funding Tracker, and Command Center contract compatibility.
- Confirm generated pages preserve administrator content.
- Confirm no PHP notices with `WP_DEBUG` enabled.
