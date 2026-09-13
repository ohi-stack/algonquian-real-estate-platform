# September 13, 2026 Conversation Reconciliation

**Repository:** `ohi-stack/algonquian-real-estate-platform`  
**Date:** 2026-09-13  
**Scope:** Public website navigation, footer architecture, page/WXR import contract, and repository-state reconciliation.

## Current repository state checked before this update

The canonical repository is already ahead of several older project notes.

- Platform 3.1.0 and Pipeline CRM 2.2.1 were promoted to `main` through PR #100.
- Pipeline CRM 2.1.0 remains the documented live migration baseline; PR #101 records the baseline and PR #102 adds an explicit fail-closed 2.1.0 -> 2.2.0 schema migration path.
- The shared ARE admin UI is already merged through PR #91.
- The canonical Navigation source remains 0.2.0 while the live production manifest records Navigation 0.3.0. Do **not** package or deploy the 0.2.0 source over production until the exact 0.3.0 source is recovered or a superseding release is built from proven production parity.
- Automation Engine 2.1.0, Agent Engine registration, Property Stewardship 1.2.0 reconciliation, Funding Track, and several public-page/documentation changes remain in open/draft PRs and should not be represented as merged production state.

## Approved public header model

The public header uses exactly six primary navigation areas:

1. Property Owners
2. Acquisitions
3. Investors & Capital
4. Services
5. Technology
6. Company

Header utilities remain separate from the six main areas:

- Search
- Buyer Login
- Client Portal
- Contact
- Submit a Property

`Submit a Property` is the primary gold conversion action.

## 6 x 6 mega-menu contract

Each primary area may contain up to six columns, with up to six primary links per column. Public navigation should be customer-facing and must not expose internal operational/admin routes as if they were public website pages.

### Property Owners

Columns:

1. Explore Your Options
2. Sell a Property
3. Planning and Transitions
4. Inherited and Estate Property
5. Senior Property Assistance
6. Stewardship and Property Care

Conversation-specific correction: the Stewardship and Property Care column includes **Vendor Coordination** as a public service link. Community Property Preservation remains a valid ARE service/resource, but it is not the replacement for Vendor Coordination in the final September 13 menu specification.

### Acquisitions

Columns:

1. Acquisition Overview
2. Submit Opportunities
3. Property Types
4. Opportunity Types
5. Transaction Structures
6. Underwriting and Due Diligence

The acquisition model continues to prioritize Connecticut 1-4 unit/small multifamily, three-family, small mixed-use, vacant/distressed, under-managed and seller-direct opportunities, with seller financing as a key structure. Pipeline CRM remains the canonical Deal owner; MAO Engine owns underwriting; Offer Generator owns seller proposals/offers.

### Investors & Capital

Columns:

1. Investor Network
2. Buyers
3. Private Capital
4. Deal Access
5. Investor Resources
6. Lenders

Buyer Dashboard and Deal Marketplace remain protected/access-controlled operational interfaces. Public navigation may link to their entry routes, but authorization is enforced by the owning plugins, not by menu visibility.

### Services

Columns:

1. Property Services
2. Property Stewardship
3. Property Monitoring
4. Maintenance Coordination
5. Property Transition Support
6. Specialized Assistance

Property Stewardship, Trusted Property Contact, Senior Property Assistance, Property Rescue, Legacy Planning and Legacy Conversation must remain property-focused. ARE does not present these services as legal, fiduciary, medical, guardianship, conservatorship or financial-advisory services.

### Technology

Columns:

1. Technology Division
2. Platform
3. Acquisition Systems
4. Operations Systems
5. Commerce and Products
6. Guides and Documentation

ARE Tech remains an internal division of Algonquian Real Estate LLC. The technology platform supports the real-estate operating business; it does not replace it.

### Company

Columns:

1. About Algonquian
2. Leadership
3. Company Information
4. Resources
5. Work With Us
6. Legal and Compliance

Entity statements must stay grounded in the actual Connecticut LLC record. Do not infer ownership percentages or authority beyond current legal/internal documentation.

## Footer contract

The footer uses four link columns:

1. Company
2. Property Owners
3. Investors and Platform
4. Resources and Legal

The footer bottom bar should contain the company copyright, a concise operating descriptor, and direct Privacy / Terms / Accessibility / Contact links.

## Mobile behavior

- The hamburger opens the navigation immediately on first tap.
- The six main areas appear as accordion sections.
- Column headings expand independently.
- Do not dump all 36 links at once on a small screen.
- Keep Submit a Property persistently available as the primary action.
- Buyer Login and Client Portal belong in an Account/utility area rather than inside the six public sections.
- Preserve focus containment, Escape/backdrop close, body scroll lock, `aria-hidden`/inert behavior, and `prefers-reduced-motion` support from the Navigation plugin standard.

## WXR import contract produced from this conversation

A WordPress WXR 1.2 import package was generated from the approved 6 x 6 architecture with:

- 212 published pages
- six main parent sections
- hierarchical child pages
- a Primary Mega Menu
- a Header Utilities menu
- four separate footer menus
- WPBakery-compatible starter page content using `[vc_column_text]...[/vc_column_text]`
- deduplication of repeated labels where practical

The WXR is an **import artifact**, not proof that those pages are already live, reviewed, indexed, or connected to their owning plugins. Import must be followed by route reconciliation, duplicate-page review, shortcode/portal mapping, menu-location assignment, and staging QA.

## WPBakery rule

Generated or hand-authored WPBakery content must use:

```text
[vc_column_text]
...
[/vc_column_text]
```

Never use `</vc_column_text>`.

## Safe repository action from this conversation

This reconciliation intentionally does **not** modify the canonical Navigation 0.2.0 runtime package because production is recorded at 0.3.0 and the exact 0.3.0 source is not yet proven in the repository. The safe update is to preserve the September 13 navigation/WXR contract as authoritative documentation/configuration until source parity is recovered.

## Next implementation gate

Before changing the runtime Navigation package:

1. Recover the exact deployed Navigation 0.3.0 source or release artifact.
2. Compare it to `plugins/algonquian-navigation` on `main`.
3. Apply the September 13 menu delta without removing newer 0.3.0 behavior.
4. Build a version greater than or equal to the live production floor.
5. Validate desktop widths 1440/1366/1280, tablet 1024/768, and mobile 430/390 including iPhone Safari.
6. Validate menu routes against imported/live pages and owning plugin shortcodes.
7. Verify four-column footer and all utility actions.
8. Run accessibility and reduced-motion checks.
9. Only then promote the navigation package and update the manifest/release state.
