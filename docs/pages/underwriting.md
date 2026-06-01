# /underwriting Production Specification

## Purpose

The /underwriting page is the acquisition analysis and maximum allowable offer hub for Algonquian Real Estate. It should connect deal records to MAO calculations, repair estimates, ARV assumptions, acquisition strategy, risk flags, offer recommendations, lender package data, and saved underwriting scenarios.

## Recommended Page Title

Underwriting

## Recommended URL

/underwriting

## Recommended Shortcode

```text
[algq_underwriting]
```

For MAO-only calculator pages, use:

```text
[algq_mao_calculator]
```

If the underwriting shortcode is not yet implemented, the page should temporarily render the MAO calculator and a placeholder list of saved underwriting scenarios.

## WPBakery Implementation

Use:

```text
[vc_column_text]
[algq_underwriting]
[/vc_column_text]
```

For calculator-only placement:

```text
[vc_column_text]
[algq_mao_calculator]
[/vc_column_text]
```

Never use:

```html
</vc_column_text>
```

## Access Rule

The /underwriting page must be restricted to authorized internal users. The default production capability should be `manage_options` until a custom acquisition, analyst, or underwriting role is implemented.

## Required Underwriting Inputs

- Deal ID
- Property address
- Acquisition strategy
- ARV
- Asking price
- Estimated repairs
- Holding costs
- Closing costs
- Assignment fee target
- Financing assumptions
- Rent estimate
- Expense assumptions
- Vacancy assumption
- Cap rate assumption
- Risk notes

## Required Underwriting Outputs

- Maximum Allowable Offer
- Estimated spread
- Estimated gross profit
- Estimated net profit
- Loan-to-value estimate
- Cash needed to close
- Risk flag
- Recommended next action
- Saved scenario version
- Calculation timestamp

## Strategy Modes

1. Wholesale
2. Flip
3. Rental
4. Seller financing
5. Subject-to
6. Buy-and-hold
7. Joint venture

## Related Pages

- /underwriting/{deal-id}
- /deals/{id}
- /deals/{id}/documents
- /funding/{deal-id}
- /plugin/mao-engine/calculator
- /plugin/offer-generator/templates

## Underwriting Workflow

1. Deal is created from seller intake or internal entry.
2. Internal user opens underwriting record.
3. User enters ARV, repair estimate, costs, and strategy mode.
4. System calculates MAO and projected spread.
5. User reviews risk flags and assumptions.
6. Scenario is saved to the deal record.
7. Offer Generator may use the saved underwriting output.
8. Funding Tracker may use the underwriting output for lender review.
9. Document Library may generate lender or acquisition package documents.

## Production Requirements

- Page must be generated on plugin activation if it does not already exist.
- Page must not duplicate if the slug already exists.
- Page must use valid shortcode syntax.
- Numeric inputs must be sanitized, normalized, and validated before calculation.
- Currency output must be escaped and formatted consistently.
- Saved underwriting scenarios must be linked to deal records.
- Underwriting changes should be logged.
- Offer recommendations must be treated as internal analysis, not binding offers.
- Lender-facing underwriting exports should include assumptions and timestamps.
- The page should support future PDF underwriting reports, CSV export, saved formulas, scenario comparisons, lender package generation, and role-based visibility.
