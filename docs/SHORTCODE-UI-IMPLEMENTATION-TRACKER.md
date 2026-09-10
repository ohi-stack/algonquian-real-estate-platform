# Shortcode UI Implementation Tracker

This tracker operationalizes `docs/SHORTCODE-UI-CONTRACT.md` across the canonical Algonquian Real Estate plugin suite.

## Release rule

A page-facing shortcode is not complete merely because it is registered. It must render substantive current content, intentional empty and authorization states, the current ARE UI, and required assets without exposing raw shortcode text, placeholder/scaffold copy, obsolete routes, or documentation-only shells.

## Current implementation work

- Deal Intake: PR #74 — full property-review UI, public/internal/quick-capture workspaces, homeowner options, seller portal states, seller funnel reconciliation, current ARE styling.
- Admin Command Center: PR #69 — transaction-control dashboard and operational shortcodes.
- Buyer Marketplace Dashboard: PR #71 — production Buyer Marketplace Dashboard UI.
- Platform navigation: PR #63 — 6×6 enterprise navigation, four-column footer, mobile first-tap behavior, responsive utilities.
- Investors/Buyer flow: PR #58 — Investors page, buyer registration/login/Marketplace route and capability reconciliation.
- Protected systems: PR #59 — route and shortcode capability gates for internal operational interfaces.
- MAO Engine: PR #61 — seller-financing underwriting UI/data model.
- Offer Generator: PR #62 — seller-financing proposal/term-sheet/LOI/offer workflow.

## Remaining canonical plugin audit

The live route and duplicate-page inventory is maintained in `docs/PUBLIC-PLUGIN-PAGE-CONTENT-STATUS.md`.

Audit and update, as needed:

- Algonquian Real Estate Platform Plugin
- Algonquian Pipeline CRM
- Algonquian Document Library
- Algonquian PDF & Signature Engine
- Algonquian Automation Engine
- Algonquian Buyer Portal
- Algonquian Funding Tracker
- Algonquian Digital Products
- Algonquian Digital Store
- Algonquian WooCommerce Bridge
- Algonquian Property Stewardship Services

For each registered page-facing tag verify:

1. `do_shortcode()` returns meaningful rendered HTML.
2. Literal shortcode text is never echoed to the visitor.
3. Empty data produces an intentional branded empty state.
4. Logged-out/unauthorized access produces an intentional access state.
5. Dependency failures produce a controlled state, not a PHP warning or blank page.
6. Forms retain nonce, validation, authorization, audit and anti-abuse controls.
7. Current navy/gold/teal/white ARE styling and responsive behavior are applied.
8. Public assets load only when the shortcode is present.
9. WPBakery generated pages use `[vc_column_text]...[/vc_column_text]`.
10. Placeholder/scaffold/TODO content and obsolete page routes are removed or migrated.

## Current content decisions to preserve

- Deal Intake is the authoritative submission-time system; Pipeline CRM owns the canonical deal after acceptance.
- Pipeline CRM page interfaces use `[algq_pipeline_dashboard]`, `[algq_pipeline_board]`, and `[algq_pipeline_activity]` rather than the Platform fallback alias.
- Seller financing flows MAO underwriting → Offer Generator proposal → Document Library/PDF & Signature → Funding Tracker/closing.
- Buyer Portal owns buyer account interfaces; Deal Marketplace owns opportunity distribution, NDA/offer/package gates and Marketplace dashboards.
- Property Stewardship public and portal interfaces must keep legal/fiduciary/caregiving boundaries and owner-scoped records.
- Protected operational systems must never become public solely because a shortcode is embedded in a published page.

## Production boundary

Repository source completion is separate from live WordPress certification. Replacement ZIPs should not be classified as production-certified until PHP/static validation, WordPress activation/migration, shortcode rendering, permissions, responsive UI, form/PDF/mail paths, and representative end-to-end workflows pass in a production-equivalent environment.
