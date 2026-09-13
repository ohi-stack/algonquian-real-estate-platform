# Operational Page → Plugin Shortcode Map

**Date:** September 13, 2026  
**Repository:** `ohi-stack/algonquian-real-estate-platform`

## Purpose

This document connects WordPress operational pages to the actual owning plugin interface instead of leaving pages as generic WPBakery shells, duplicate application screens, or literal shortcode placeholders.

The machine-readable source is `config/operational-page-shortcodes.json`.

## Governing rules

1. The shortcode registry in plugin source is authoritative.
2. A published WordPress page does not make a protected application public; capability and record-level authorization remain plugin-owned.
3. Pipeline CRM remains the canonical Deal owner.
4. Deal Intake owns submission-time seller/property/consent evidence.
5. MAO Engine owns underwriting calculations and approval evidence.
6. Offer Generator owns proposals/offers.
7. Buyer Portal owns buyer identity/account access; Deal Marketplace owns controlled opportunity distribution and Marketplace NDA/offer workflows.
8. Document Library and PDF & Signature retain controlled-document boundaries.
9. Automation, Command Center and other internal operating pages must enforce their native capabilities.
10. WPBakery wrappers must use `[vc_column_text]...[/vc_column_text]` and must never use `</vc_column_text>`.

## Confirmed current-main registrations

### Pipeline CRM

| Page | Route | Shortcode |
|---|---|---|
| Pipeline Dashboard | `/pipeline/` | `[algq_pipeline_dashboard]` |
| Pipeline Board | `/pipeline/board/` | `[algq_pipeline_board]` |
| Pipeline Activity | `/pipeline/activity/` | `[algq_pipeline_activity]` |

These tags are registered directly by the current Pipeline CRM 2.2.1 source.

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
| Deal Marketplace | `/marketplace/` | `[algq_deal_marketplace]` |
| Buyer Marketplace Dashboard | `/buyer-marketplace-dashboard/` | `[algq_buyer_marketplace_dashboard]` |
| Marketplace NDA | `/marketplace/nda/` | `[algq_buyer_nda_gate]` |
| Submit Buyer Offer | `/marketplace/submit-offer/` | `[algq_buyer_offer_form]` |

The Marketplace may render a public discovery layer, but confidential deal details, downloads, NDA state and offer submission remain access-controlled.

## Preferred production contracts requiring source reconciliation

These mappings are already defined by the repository-level shortcode/UI contract, but their current source branch or live-production source still needs reconciliation before they are represented as newly certified production interfaces.

### Deal Intake

| Page | Route | Shortcode |
|---|---|---|
| Submit a Property | `/submit-a-property/` | `[algq_property_submission]` |
| Request Property Review | `/request-property-review/` | `[algq_property_review]` |
| Sell Your Property / Options | `/sell-your-property/` | `[algq_homeowner_options]` |
| Seller Portal | `/seller-portal/` | `[algq_seller_portal]` |

### MAO Engine

| Page | Route | Shortcode |
|---|---|---|
| MAO Calculator | `/underwriting/mao-calculator/` | `[algq_mao_calculator]` |
| MAO Engine Overview | `/technology/plugins/mao-engine/` | `[algq_mao_plugin_page]` |

### Offer Generator

| Page | Route | Shortcode |
|---|---|---|
| Offer Generator | `/offers/` | `[algq_offer_generator]` |
| Offer Builder | `/offers/builder/` | `[algq_offer_builder]` |
| Offer History | `/offers/history/` | `[algq_offer_history]` |

### Document Library

| Page | Route | Shortcode |
|---|---|---|
| Document Library | `/documents/` | `[algq_document_library]` |
| Document Request | `/documents/request/` | `[algq_document_request]` |
| Document Packages | `/documents/packages/` | `[algq_document_packages]` |

### PDF & Signature Engine

| Page | Route | Shortcode |
|---|---|---|
| PDF Engine | `/documents/pdf/` | `[algq_pdf_engine]` |
| Signature Archive | `/documents/signatures/` | `[algq_signature_archive]` |

### Automation Engine

| Page | Route | Shortcode |
|---|---|---|
| Automation Overview | `/automation/` | `[algq_automation_overview]` |
| Getting Started | `/automation/getting-started/` | `[algq_automation_getting_started]` |
| Documentation | `/automation/docs/` | `[algq_automation_docs]` |
| Automation Rules | `/automation-rules/` | `[algq_automation_rules]` |

`[algq_automation_engine]` remains a compatibility/general interface, not the preferred tag for new dedicated pages.

### Admin Command Center

| Page | Route | Shortcode |
|---|---|---|
| Admin Command Center | `/dashboard/` | `[algq_admin_dashboard]` |

Dedicated Command Center interfaces may use more specific tags where registered by the plugin. The page map should never bypass native capability checks.

## WXR/import integration

For the September 13 WXR page set, operational pages should not retain generic starter prose after the owning shortcode is available. The WordPress page should provide the institutional shell/intro and then render the plugin interface.

Example pattern:

```text
[vc_row full_width="stretch_row_content"]
[vc_column]
[vc_column_text]
<h1>Buyer Dashboard</h1>
<p>Secure access to authorized opportunities and buyer activity.</p>
[/vc_column_text]
[vc_column_text]
[algq_buyer_dashboard]
[/vc_column_text]
[/vc_column]
[/vc_row]
```

The shortcode must remain literal inside WordPress post content so WordPress executes it at render time.

## Do not create duplicate systems

Do not build a second buyer dashboard, pipeline board, underwriting calculator, offer builder, document vault, automation rules page, or marketplace workflow in WPBakery when the owning plugin already supplies the interface. WPBakery should provide page composition and public-facing context; the owning plugin supplies operational state and actions.

## Next reconciliation step

After this map is merged:

1. reconcile Deal Intake 2.1 source;
2. reconcile Automation 2.1 source;
3. confirm current source registrations for MAO, Offers, Documents, PDF/Signature, Funding, Stewardship and Command Center;
4. add any additional native tags to the JSON map;
5. update/import the WordPress page content to replace generic operational placeholders with the mapped shortcodes;
6. run role-based rendering checks for public, buyer, staff and administrator contexts.
