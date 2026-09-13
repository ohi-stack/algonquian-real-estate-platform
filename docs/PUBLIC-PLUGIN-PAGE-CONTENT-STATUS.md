# Public Plugin Page Content Status

Last audited: 2026-08-22  
Site: https://algonquianrealestate.com/  
Scope: canonical public overview page for each plugin in the platform manifest.

## Definition of page-ready

A public plugin page is page-ready only when it:

- resolves at its canonical URL without relying on an unrelated legacy path;
- contains substantive, branded page content rather than a title-only shell;
- renders every registered shortcode instead of exposing raw shortcode text;
- works at desktop, tablet, and mobile widths;
- provides intentional empty, loading, permission, and error states where the page is interactive;
- has one clear canonical version, with duplicates redirected or retired after content is reconciled.

## Audit summary

| State | Count |
| --- | ---: |
| Substantive at canonical route | 2 |
| Thin or empty at resolved route | 5 |
| Canonical route returns 404 | 10 |
| Raw shortcode observed in this route audit | 0 |

The site contains many published legacy copies. A 404 below does **not** mean no page exists; it means the repository-declared canonical public URL is absent. Content should be reconciled into one canonical page before redirects or duplicate cleanup are performed.

## Route-by-route status

| Plugin | Canonical public URL | Live state | Existing page evidence | Required content action |
| --- | --- | --- | --- | --- |
| Platform | `/algonquian-real-estate-platform/` | Substantive | Canonical page is populated | Preserve and responsive-QA the completed page |
| Pipeline CRM | `/algonquian-pipeline-crm/` | Thin; resolves to `/technology/algonquian-pipeline-crm/` | Also published at `/pipeline-crm/` and `/plugins-4/pipeline-crm/` | Select source content, complete canonical overview, redirect duplicates |
| Deal Intake | `/algonquian-deal-intake/` | Thin; resolves to `/technology/algonquian-deal-intake/` | Published copies under `/plugin/deal-intake/`, `/plugins-4/deal-intake/`, and `/deal-intake/` | Use PR #74 as implementation source; complete overview and consolidate routes |
| MAO Engine | `/algonquian-mao-engine/` | Thin; resolves to `/technology/algonquian-mao-engine/` | Published copies under `/plugin/mao-engine/`, `/plugins-4/mao-engine/`, and `/mao-engine/` | Reconcile content, finish canonical overview, redirect duplicates |
| Document Library | `/algonquian-document-library/` | 404 | Published at `/plugin/document-library/`, `/plugins-4/document-library/`, `/document-library/`, and `/document-library-2/` | Promote the best source to canonical and consolidate four copies |
| PDF & Signature Engine | `/algonquian-pdf-signature-engine/` | 404 | Published at `/plugins-4/pdf-engine/`, `/pdf-engine/`, and `/technology/pdf-and-signature-engine/` | Reconcile content and establish the canonical route |
| Offer Generator | `/algonquian-offer-generator/` | Thin; resolves to `/technology/algonquian-offer-generator/` | Published copies under `/plugins-4/offer-generator/`, `/offer-generator/`, and `/offer-generator-2/` | Reconcile content, finish canonical overview, redirect duplicates |
| Automation Engine | `/algonquian-automation-engine/` | 404 | Published at `/plugin/automation-engine/`, `/plugins-4/automation-engine/`, `/automation-engine/`, and `/automation-engine-2/` | Promote the best source to canonical and consolidate copies |
| Admin Command Center | `/algonquian-admin-command-center/` | 404 | Published at `/technology/admin-command-center/`, `/command-center/`, `/plugins-4/command-center/`, and a nested technology-v1 route | Use PR #69 as UI source, establish canonical overview, then redirect duplicates |
| Buyer Portal | `/algonquian-buyer-portal/` | 404 | Published at `/plugin-buyer-portal/`, `/plugins-4/buyer-portal/`, and `/buyer-portal/` | Use current buyer UI work as source, establish canonical overview, redirect duplicates |
| Deal Marketplace | `/algonquian-deal-marketplace/` | 404 | Published at `/plugin/deal-marketplace/`, `/plugins-4/deal-marketplace/`, `/deal-marketplace/`, and `/investors/deal-marketplace/` | Reconcile public overview versus authenticated marketplace, then canonicalize |
| Funding Tracker | `/algonquian-funding-tracker/` | 404 | Published at `/plugins-4/funding-tracker/`, `/funding-tracker/`, `/funding-tracker-2/`, and `/technology/funding-tracker/` | Promote completed source content to canonical and consolidate copies |
| Digital Products | `/algonquian-digital-products/` | 404 | Published at `/plugin/digital-products/`, `/digital-products/`, and two technology routes | Reconcile overview and catalog responsibilities, establish canonical route |
| Digital Store | `/algonquian-digital-store/` | 404 | Published at `/plugins-4/digital-store/`, `/digital-store/`, and `/technology/digital-store/` | Reconcile storefront versus plugin overview, establish canonical route |
| WooCommerce Bridge | `/algq-woocommerce-bridge/` | Substantive | Canonical page is populated; legacy copies also exist | Preserve canonical page, responsive-QA, redirect or retire duplicates |
| Property Stewardship Services | `/algonquian-property-stewardship-services/` | Empty after redirect to nested technology-v1 route | Additional service pages exist under `/property-services/`, `/property-owners/`, and `/services-3/` | Keep service marketing separate from plugin overview; render registered stewardship shortcode on canonical overview |
| Navigation | `/algonquian-navigation/` | 404 | No matching WordPress page found in exact-title search | Create canonical responsive navigation-plugin overview from PR #63 implementation |

## Recommended execution order

1. Freeze canonical URLs from the plugin metadata standard and stop creating new numbered or nested copies.
2. Complete Deal Intake from PR #74, because it already contains the newest route and responsive UI work.
3. Complete the four other thin/empty pages: Pipeline CRM, MAO Engine, Offer Generator, and Property Stewardship.
4. Promote and reconcile the best existing content for the nine 404 routes that already have legacy pages.
5. Create the Navigation overview page once its responsive plugin implementation is reviewable.
6. Test all 17 canonical pages as a logged-out visitor at desktop, tablet, and mobile sizes.
7. Only after verification, add redirects and retire duplicate pages with a documented rollback map.

## Change-control notes

- This audit was read-only. No WordPress page content, slug, redirect, or publication state was changed.
- Existing pages should be backed up before content consolidation.
- Live route changes should follow a reviewed pull request and a staged, reversible WordPress update.
- Deal Intake tracker references should use PR #74; it supersedes the older PR #57 implementation path.
