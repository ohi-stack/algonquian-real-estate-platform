# Changelog

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
