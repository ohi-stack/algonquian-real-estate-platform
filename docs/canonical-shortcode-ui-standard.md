# Algonquian Real Estate — Canonical Shortcode UI & Content Standard

**Status:** Canonical repository standard
**Scope:** 16 production-manifest plugins

## Governing rule

A registered shortcode is not considered a complete production interface merely because the tag resolves. Every page-facing shortcode must render useful, branded, state-aware content for its plugin authority.

Each interface must provide, as applicable:

- Algonquian Real Estate shared navy / teal / gold / white UI.
- Clear plugin/interface title and purpose.
- Operational data or a useful workflow—not a blank shell.
- Loading, empty, success, validation, permission-denied, dependency-unavailable and error states.
- Primary next action and contextual navigation.
- Capability and nonce controls for state-changing actions.
- Responsive and accessible markup.
- Integration with the authoritative plugin record rather than duplicate data.
- Documentation/help entry points where appropriate.

## Canonical native shortcode registry

### Platform Plugin 2.0.0
`[algq_platform_overview]` · `[algq_plugin_suite]`

### Pipeline CRM 2.0.0
`[algq_pipeline_dashboard]` · `[algq_pipeline_board]` · `[algq_pipeline_activity]`

Pipeline interfaces must expose canonical deal records, stages, assignments, next actions, activity, bottlenecks and downstream summaries. The dashboard should answer: what deals need attention now?

### Deal Intake 2.0.0
Preferred: `[algq_deal_intake_form]` · `[algq_property_submission]` · `[algq_homeowner_options]` · `[algq_seller_portal]`

Supported legacy/current: `[deal_intake_form_public]` · `[deal_intake_form_internal]` · `[deal_quick_capture]`

Deal Intake UI must provide real seller/property intake, validation, consent, submission result, duplicate handling and handoff to Pipeline CRM. `[algq_deal_intake_about]` is not canonical.

### MAO Engine 2.0.0
`[algq_mao_calculator]` · `[algq_mao_plugin_page]`

Supported views: `[algq_mao_plugin_page view="start"]` · `[algq_mao_plugin_page view="docs"]`

### Document Library 2.0.0
`[algq_document_library]` · `[algq_document_request]` · `[algq_document_packages]`

### PDF & Signature Engine 2.0.0
`[algq_pdf_engine]` · `[algq_signature_archive]`

Supported views: `[algq_pdf_engine view="start"]` · `[algq_pdf_engine view="docs"]`

### Offer Generator 2.0.0
`[algq_offer_generator]` · `[algq_offer_builder]` · `[algq_offer_history]`

### Automation Engine 2.0.0
`[algq_automation_overview]` · `[algq_automation_getting_started]` · `[algq_automation_docs]` · `[algq_automation_rules]`

Automation UI must expose controlled rules, triggers, conditions, actions, execution history, failures/retries and human approval boundaries. Do not build new pages around `[algq_automation_engine]`; that name is a compatibility bridge.

### Admin Command Center 1.2.0
`[algq_command_center]` · `[algq_admin_dashboard]` · `[algq_command_center_kpis]` · `[algq_command_center_pipeline]` · `[algq_command_center_activity]` · `[algq_command_center_health]` · `[algq_command_center_overview]` · `[algq_command_center_start]` · `[algq_command_center_docs]`

The Command Center must prioritize approvals, deadlines, stalled deals, pipeline throughput, system health and audit visibility rather than generic cards.

### Buyer Portal 1.1.0
`[algq_buyer_registration]` · `[algq_buyer_login]` · `[algq_buyer_dashboard]` · `[algq_buyer_deals]`

### Deal Marketplace 2.0.0
`[algq_deal_marketplace]` · `[algq_buyer_marketplace_dashboard]` · `[algq_buyer_nda_gate]` · `[algq_buyer_offer_form]` · `[algq_deal_marketplace_plugin_card]`

### Funding Tracker 1.0.0
`[algq_funding_tracker]` · `[algq_funding_dashboard]` · `[algq_capital_sources]`

### Digital Products 1.0.0
`[algq_digital_products]` · `[algq_digital_product]`

Supported examples: `[algq_digital_products category="templates" limit="12" columns="3"]` · `[algq_digital_product id="123"]` · `[algq_digital_product slug="seller-financing-offer-pack"]`

### Digital Store 1.1.0
`[algq_digital_store]` · `[algq_product_vault]` · `[algq_store_checkout]`

Supported example: `[algq_digital_store limit="12" category="plugins"]`

### WooCommerce Bridge 2.0.0
`[algq_commerce_access]` · `[algq_purchased_products]` · `[algq_buyer_entitlements]`

### Property Stewardship Services 1.0.0
`[algq_property_stewardship]` · `[algq_stewardship_portal]`

The public interface must explain Property Watch, Active Stewardship and Transition Stewardship with owner authorization and service boundaries. The portal must expose only authorized client properties, visits, reports, photographs, vendors, documents and requests.

## Platform compatibility bridges

The Platform Plugin may conditionally register the following only when the authoritative companion shortcode does not exist:

`[algq_seller_intake]` · `[algq_mao_calculator]` · `[algq_buyer_registration]` · `[algq_pipeline_crm]` · `[algq_buyer_portal]` · `[algq_funding_tracker]` · `[algq_document_library]` · `[algq_automation_engine]` · `[algq_admin_dashboard]` · `[algq_digital_store]` · `[algq_product_vault]` · `[algq_store_checkout]`

Compatibility bridges must never supersede a companion plugin implementation and should route users toward the current canonical interface.

## Transaction-control UI

The Pipeline CRM, Automation Engine and Command Center should converge on a canonical transaction-control model:

`LEAD_NEW → ENRICHMENT → CONTACT_PENDING → CONTACTED → QUALIFICATION → UNDERWRITING → STRATEGY_REVIEW → APPROVAL_REQUIRED → OFFER_PREPARATION → OFFER_READY → OFFER_SENT → NEGOTIATION → UNDER_CONTRACT → DUE_DILIGENCE → BUYER_MATCH / CAPITAL_MATCH → CLOSING_PREP → CLOSING_READY → CLOSED → POST_CLOSE → ARCHIVED`

Alternative states include `NURTURE`, `DISQUALIFIED`, and `CANCELLED`.

Every active deal must have a current state, next action, responsible party, last meaningful activity, blocker/risk status and due date where applicable. Binding offers, contracts, capital commitments, funds movement and closing authority remain human-controlled.

## Website/page UI standard

Public ARE pages should use the established enterprise visual language: deep navy hero treatment, gold classification badge/divider, white primary typography, teal/gold accents, responsive operational cards, clear CTA hierarchy and useful explanatory content.

The website is a transaction and service interface. Primary conversion paths include property submission/review, homeowner options, investor/buyer registration, stewardship inquiry/portal, documents and operational dashboards.

## WPBakery rule

Always embed standalone plugin interfaces using valid WPBakery closing syntax:

```text
[vc_column_text]
[algq_shortcode]
[/vc_column_text]
```

Never use `</vc_column_text>`.

Placeholder strings such as `FORM_PLUGIN_SHORTCODE`, `[YOUR_FORM_PLUGIN_SHORTCODE_HERE]`, TODO-only blocks, lorem ipsum and empty shortcode shells are production defects and must not remain in generated or production pages.

## Acceptance rule

A shortcode/UI update is complete only when it is operational, documented and repeatable. Static source presence alone is insufficient; WordPress activation, permissions, page rendering and relevant end-to-end workflows must be tested before live-production certification.
