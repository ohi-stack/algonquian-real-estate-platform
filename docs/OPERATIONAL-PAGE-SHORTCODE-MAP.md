# Operational Page → Owning Plugin Shortcode Map

**Date:** September 13, 2026  
**Repository:** `ohi-stack/algonquian-real-estate-platform`  
**Machine-readable registry:** `config/operational-page-shortcodes.json`

## Purpose

This map connects ARE WordPress operational pages to the shortcode actually supplied by the authoritative plugin. WPBakery supplies the page shell and explanatory content; the owning plugin supplies application state, authorization, workflow actions, persistence, and record-level access.

The map distinguishes current canonical source from the last observed live versions. A shortcode existing on `main` does not by itself prove that the same version is already deployed.

## Governing rules

1. Runtime/source registrations are authoritative over earlier planning documents.
2. Use the owning plugin's shortcode instead of a generic Platform compatibility shortcode when both exist.
3. A published page does not make a protected application public. Native capabilities and record-level checks remain mandatory.
4. Pipeline CRM remains the canonical Deal owner.
5. Deal Intake owns submission-time seller/property/consent/source/artifact evidence.
6. MAO Engine owns underwriting; Offer Generator owns proposals/offers.
7. Document Library owns controlled document records; PDF & Signature owns rendered PDF/signature execution evidence.
8. Buyer Portal owns buyer identity/access; Deal Marketplace owns controlled deal distribution, Marketplace NDA evidence, and buyer offers.
9. Funding Tracker owns capital-source/funding records.
10. Property Stewardship owns stewardship service/visit/vendor records.
11. Platform Modules 13–20 coordinate cross-functional work but do not take specialized record ownership.
12. WPBakery syntax is `[vc_column_text]...[/vc_column_text]`. Never use the malformed HTML-style closing token.

## Corrected production-funnel mappings

### Deal Intake 2.1.0 — recovered production source

| Operational page | Route | Owning shortcode |
|---|---|---|
| Submit a Property | `/submit-a-property/` | `[algq_deal_intake_form]` |
| Sell Your Property | `/sell-your-property/` | `[algq_deal_intake_form]` |
| Request Property Review | `/request-property-review/` | `[algq_property_review]` |
| What Are My Options? | `/property-options/` | `[algq_homeowner_options]` |
| Seller Portal — deployed Deal Intake interface | `/seller-portal/` | `[algq_seller_portal]` |
| Internal Deal Intake | `/internal/deal-intake/` | `[deal_intake_form_internal]` |
| Quick Deal Capture | `/internal/deal-quick-capture/` | `[deal_quick_capture]` |

The recovered Deal Intake 2.1 funnel reconciler explicitly places the canonical intake form on both `/submit-a-property/` and `/sell-your-property/`. `[algq_property_submission]` remains an equivalent public interface, but `[algq_homeowner_options]` belongs on the options page rather than replacing the sale/intake form.

## Canonical Deal and acquisition operations

### Pipeline CRM

| Page | Site route | Shortcode | Plugin-generated route where different |
|---|---|---|---|
| Pipeline Dashboard | `/pipeline/` | `[algq_pipeline_dashboard]` | `/plugin/pipeline-crm/` |
| Pipeline Board | `/pipeline/board/` | `[algq_pipeline_board]` | `/plugin/pipeline-crm/board/` |
| Pipeline Activity | `/pipeline/activity/` | `[algq_pipeline_activity]` | — |

`[algq_pipeline_crm]` is a compatibility alias and is not preferred for new pages.

### MAO Engine

| Page | Route | Shortcode |
|---|---|---|
| MAO Calculator | `/plugin/mao-engine/calculator/` | `[algq_mao_calculator]` |
| MAO Overview | `/plugin/mao-engine/` | `[algq_mao_plugin_page]` |
| MAO Getting Started | `/plugin/mao-engine/start/` | `[algq_mao_plugin_page view="start"]` |
| MAO Documentation | `/plugin/mao-engine/docs/` | `[algq_mao_plugin_page view="docs"]` |

### Offer Generator

| Page | Route | Shortcode |
|---|---|---|
| Offer Generator | `/offer-generator/` | `[algq_offer_generator]` |
| Offer Builder | `/generate-offer/` | `[algq_offer_builder]` |
| Offer History | `/offer-history/` | `[algq_offer_history]` |

All three Offer Generator interfaces are protected operational interfaces.

## Buyer and marketplace operations

### Buyer Portal

| Page | Route | Shortcode |
|---|---|---|
| Buyer Registration | `/buyers-register/` | `[algq_buyer_registration]` |
| Buyer Login | `/buyer-login/` | `[algq_buyer_login]` |
| Buyer Dashboard | `/buyer-dashboard/` | `[algq_buyer_dashboard]` |
| Authorized Buyer Deals | `/buyer-deals/` | `[algq_buyer_deals]` |

### Deal Marketplace

| Page | Route | Shortcode |
|---|---|---|
| Marketplace | `/marketplace/` | `[algq_deal_marketplace]` |
| Buyer Marketplace Dashboard | `/buyer-dashboard/marketplace/` | `[algq_buyer_marketplace_dashboard]` |
| Marketplace NDA | `/buyer-dashboard/nda/` | `[algq_buyer_nda_gate]` |
| Submit Marketplace Offer | `/buyer-dashboard/submit-offer/` | `[algq_buyer_offer_form]` |

The Marketplace activation code nests the dashboard/NDA/offer pages under `/buyer-dashboard/` when that parent exists. Standalone fallback slugs are created only when the buyer-dashboard parent is absent.

## Documents and signatures

### Document Library

| Page | Route | Shortcode |
|---|---|---|
| Document Library | `/documents/` | `[algq_document_library]` |
| Document Packages | `/documents/packages/` | `[algq_document_packages]` |
| Request Document Access | `/documents/request-access/` | `[algq_document_request]` |

A document-access request is not automatic entitlement.

### PDF & Signature

| Page | Route | Shortcode |
|---|---|---|
| Signature Archive | `/documents/signatures/` | `[algq_signature_archive]` |
| PDF & Signature Engine | `/plugin/pdf-signature-engine/` | `[algq_pdf_engine]` |
| Getting Started | `/plugin/pdf-signature-engine/start/` | `[algq_pdf_engine view="start"]` |
| Documentation | `/plugin/pdf-signature-engine/docs/` | `[algq_pdf_engine view="docs"]` |

## Funding, automation, and oversight

### Funding Tracker

| Page | Route | Shortcode |
|---|---|---|
| Funding overview | `/funding/` | `[algq_funding_tracker]` |
| Funding Dashboard | `/funding-dashboard/` | `[algq_funding_dashboard]` |
| Capital Sources | `/capital-sources/` | `[algq_capital_sources]` |

`/capital-sources/` is a valid ARE page alias for the native shortcode; the current plugin activation routine only guarantees `/funding-dashboard/` plus its plugin documentation pages.

### Automation Engine

| Page | Route | Shortcode |
|---|---|---|
| Automation Overview | `/plugin/automation-engine/` | `[algq_automation_overview]` |
| Getting Started | `/plugin/automation-engine/start/` | `[algq_automation_getting_started]` |
| Documentation | `/plugin/automation-engine/docs/` | `[algq_automation_docs]` |
| Automation Rules | `/automation-rules/` | `[algq_automation_rules]` |

Do not use `[algq_automation_engine]` for new dedicated pages when the native page-specific interface exists.

### Admin Command Center

Current canonical source is Command Center 1.2.0; the last supplied live inventory showed 1.1.0. Therefore these mappings are canonical-source targets until 1.2.0 is deployed and accepted.

| Page | Route | Shortcode |
|---|---|---|
| Command Center | `/command-center/` | `[algq_command_center]` |
| KPIs | `/command-center/kpis/` | `[algq_command_center_kpis]` |
| Pipeline View | `/command-center/pipeline/` | `[algq_command_center_pipeline]` |
| Activity | `/command-center/activity/` | `[algq_command_center_activity]` |
| Health | `/command-center/health/` | `[algq_command_center_health]` |
| Overview | `/plugin-command-center/` | `[algq_command_center_overview]` |
| Getting Started | `/plugin-command-center-start/` | `[algq_command_center_start]` |
| Documentation | `/plugin-command-center-docs/` | `[algq_command_center_docs]` |

`[algq_admin_dashboard]` remains an alias, but new operational mapping should prefer the dedicated Command Center interface.

## Property Stewardship

| Page | Site route | Shortcode | Plugin-generated route |
|---|---|---|---|
| Property Stewardship Services | `/property-stewardship-services/` | `[algq_property_stewardship]` | same |
| Client Portal | `/client-portal/` | `[algq_stewardship_portal]` | `/property-stewardship-portal/` |

The current canonical source that can be inspected is 1.0.0 while production was reported at 1.2.0. The shortcodes above exist in canonical source, but exact 1.2.0 source parity is still unresolved. Keep the page protected by the plugin's account/record checks.

## Commerce pages

### Digital Products

| Page | Route | Shortcode |
|---|---|---|
| Digital Products Catalog | `/digital-products/` | `[algq_digital_products]` |

Single-product pages may use `[algq_digital_product id="…"]` or `[algq_digital_product slug="…"]`.

### Digital Store

| Page | Route | Shortcode |
|---|---|---|
| Store | `/store/` | `[algq_digital_store]` |
| Product Vault | `/product-vault/` | `[algq_product_vault]` |
| Checkout Bridge | `/store/checkout/` | `[algq_store_checkout]` |

WooCommerce remains authoritative for product/order/payment state.

## Platform 3.1 operational modules

These shortcodes are registered in current Platform 3.1 source but the last supplied live inventory showed Platform 2.3.0. Treat them as canonical-source deployment targets until Platform 3.1 is installed and accepted.

| Module | Route | Shortcode |
|---|---|---|
| Seller Portal | `/seller-portal/` | `[algq_seller_portal_v3]` |
| Title & Closing Engine | `/platform/title-closing/` | `[algq_title_closing_engine]` |
| Disposition Engine | `/platform/disposition/` | `[algq_disposition_engine]` |
| Investor Portal | `/investor-portal/` | `[algq_investor_portal]` |
| Reporting & Analytics | `/platform/reporting/` | `[algq_reporting_analytics]` |
| Document Vault | `/platform/document-vault/` | `[algq_document_vault]` |
| Task / Project Management | `/platform/tasks/` | `[algq_task_project_management]` |
| Communications Hub | `/platform/communications/` | `[algq_communications_hub]` |

The Platform Seller Portal and Deal Intake Seller Portal currently target the same route at different release states. Do not replace the deployed Deal Intake 2.1 seller interface merely because Platform 3.1 source registers Module 13. Resolve that route during Platform 3.1 deployment.

## No operational page mapping

- **WooCommerce Bridge:** integration/synchronization layer; no dedicated operational shortcode is required.
- **Navigation:** public presentation/navigation layer; not an operational record interface.
- **ARE Agent Engine:** the live WordPress adapter 0.1.0 shortcode surface remains unmapped until its exact PHP adapter source is recovered. Do not invent a shortcode from the separate TypeScript runtime/control-plane repository.

## WPBakery embedding contract

Use:

```text
[vc_row]
[vc_column]
[vc_column_text]
[algq_pipeline_dashboard]
[/vc_column_text]
[/vc_column]
[/vc_row]
```

The WordPress page may contain an ARE hero, instructions, disclosures, and contextual links around this block. It should not duplicate the owning plugin's forms, tables, authorization decisions, or authoritative records.

## Implementation gate

The registry is now suitable as the source for a WXR/page-reconciliation pass. The next deployment operation should:

1. compare the actual live page slug/content against this registry;
2. replace literal/unresolved placeholders with the mapped shortcode;
3. preserve administrator-authored public copy around the operational interface;
4. avoid inserting duplicate copies of an already-present equivalent shortcode;
5. keep protected pages protected;
6. test public, buyer/client, staff, and administrator roles;
7. record any route aliases/redirects rather than silently creating a second operational page.
