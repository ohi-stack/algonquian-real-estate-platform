# Changelog

## 2.1.0 - 2026-08-18

### Seller Financing

- Added seller financing as a first-class underwriting strategy owned by the MAO Engine.
- Added purchase price, down payment, seller-financed principal, interest rate, amortization, balloon term, and optional payment override inputs.
- Added monthly payment, annual debt service, balloon balance, and total debt service calculations.
- Added NOI, annual cash flow, and DSCR analysis for seller-financed income-property scenarios.
- Added refinance value, interest-rate, amortization, and LTV assumptions.
- Added refinance capacity, refinance loan amount, refinance payment, and refinance-gap analysis.
- Added side-by-side conventional financing assumptions and payment/cash-flow/DSCR comparison.
- Added upfront-cash and monthly-payment savings metrics.
- Added seller-financing sensitivity cases and machine-readable risk flags for cash flow, DSCR, balloon, refinance, and payment-comparison risk.

### Persistence and API

- Added seller-financing summary columns to the underwriting table while retaining complete input/result snapshots.
- Added seller-financing metrics to authorized scenario reads.
- Extended the public calculation REST schema for seller-financing and refinance inputs.
- Added seller-financing scenario count to the MAO dashboard.

### Interface and Documentation

- Added Seller Financing to the calculator strategy selector.
- Added conditional financing, refinance, income, and conventional-comparison fields.
- Added dedicated seller-financing result and sensitivity rendering.
- Updated plugin overview, Getting Started content, README, and production validation requirements.

## 2.0.0 - 2026-07-31

### Security

- Removed unauthenticated underwriting persistence.
- Added granular capabilities for viewing, managing, approving, and configuring underwriting.
- Added REST argument validation and public calculation rate limiting.
- Added approval controls before offer-generation exposure.
- Added structured audit events without logging confidential payload bodies.

### Architecture

- Removed MAO Engine ownership of the shared deals table.
- Made Pipeline CRM authoritative for deal records and stage transitions.
- Loaded one consolidated platform bridge from the plugin bootstrap.
- Retired the duplicate Offer Generator bridge.
- Added plugin registration and a health-check callback for the Platform Plugin.

### Underwriting

- Added distinct wholesale, fix-and-flip, rental, and multifamily formulas.
- Added NOI, income-value, cap-rate, and DSCR outputs for income strategies.
- Added formula version and assumption version snapshots.
- Added conservative, base, and optimistic sensitivity cases.
- Added machine-readable risk reasons.
- Added draft and approved scenario states.

### Operations

- Added controlled schema migrations.
- Added nested, idempotent WPBakery page generation.
- Added conditional asset loading.
- Added an authorized scenario REST read endpoint.
- Updated the admin dashboard, scenario list, calculator, settings, documentation, and release files.

## 1.0.0 - 2026-05-31

- Initial calculator, database table, admin pages, shortcodes, REST calculation route, and early integration bridge contracts.
