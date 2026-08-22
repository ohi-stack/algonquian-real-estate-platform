# Algonquian Real Estate Shortcode & UI Contract

## Purpose

This document is the repository-level contract for page-facing plugin interfaces. It prevents a shortcode from being considered complete merely because WordPress recognizes its tag.

A production shortcode must render a useful interface, use the shared Algonquian Real Estate visual system, enforce its access boundary, and provide an intentional empty or unavailable state.

## Current design language

Public and portal interfaces must remain consistent with the established ARE visual system:

- deep navy foundations (`#071522`, `#0b1f33`, `#0b3a63` family);
- gold primary accent (`#d1a54a` / `#c7a44a` family);
- teal secondary accent where appropriate;
- white and muted-white typography on dark surfaces;
- light neutral backgrounds for interior content where appropriate;
- restrained borders, rounded panels, operational KPI cards and clear hierarchy;
- responsive layouts without horizontal overflow;
- accessible labels, focus states and semantic headings.

For WPBakery-generated pages, use `[vc_column_text]...[/vc_column_text]`. Never emit `</vc_column_text>`.

## Runtime contract

Every page-facing shortcode must satisfy all applicable requirements below.

1. **Render content** — return a complete interface, not a shortcode echo, placeholder, scaffold message, TODO, empty wrapper or documentation-only stub.
2. **Useful empty state** — when no records exist, explain the state and provide the appropriate next action rather than returning a blank panel.
3. **Authorization** — enforce capabilities, authentication and record-level access before protected data is rendered.
4. **Shared UI** — use the ARE component vocabulary and enqueue only the assets required for the rendered interface.
5. **Responsive behavior** — remain usable on desktop, tablet and mobile.
6. **Escaping** — escape dynamic output in the correct context.
7. **Forms** — state-changing forms require nonce protection, server-side validation/sanitization, explicit errors and appropriate abuse controls.
8. **Canonical records** — interfaces must read/write the authoritative plugin records rather than silently maintaining duplicate business objects.
9. **No production placeholders** — strings such as `YOUR_FORM_PLUGIN_SHORTCODE_HERE`, `FORM_PLUGIN_SHORTCODE`, `Enterprise page scaffold`, `coming soon`, `TODO` or equivalent unresolved placeholders are release blockers when returned by a production interface.
10. **Human-control boundaries** — underwriting approval, final offer terms, legally operative documents, funds movement and closing authority remain subject to the applicable human approval workflow.

## Canonical shortcode families

The native runtime registry remains authoritative in plugin source. The following families are the preferred page-facing interfaces established by the current platform architecture.

### Platform

- `[algq_platform_overview]`
- `[algq_plugin_suite]`

Enterprise navigation interfaces, when supplied by the Platform layer:

- `[algq_mega_menu]`
- `[algq_footer_links]`

### Pipeline CRM

- `[algq_pipeline_dashboard]`
- `[algq_pipeline_board]`
- `[algq_pipeline_activity]`

`[algq_pipeline_crm]` is a compatibility/general bridge and is not the preferred interface for new pages.

### Deal Intake

Preferred:

- `[algq_deal_intake_form]`
- `[algq_property_submission]`
- `[algq_homeowner_options]`
- `[algq_seller_portal]`

Maintained legacy/secondary interfaces may include:

- `[deal_intake_form_public]`
- `[deal_intake_form_internal]`
- `[deal_quick_capture]`

The principal public seller/property conversion pages must use the production Deal Intake renderer; raw or placeholder shortcode text is prohibited.

### MAO Engine

- `[algq_mao_calculator]`
- `[algq_mao_plugin_page]`

Seller financing is a first-class underwriting strategy in the MAO domain. MAO owns calculations and approved underwriting evidence; it does not own seller-facing proposal documents.

### Offer Generator

- `[algq_offer_generator]`
- `[algq_offer_builder]`
- `[algq_offer_history]`

Offer Generator owns seller-facing proposal generation, including approved seller-financing proposal, term-sheet and LOI workflows derived from approved MAO underwriting. It must not silently recalculate MAO values.

### Document Library

- `[algq_document_library]`
- `[algq_document_request]`
- `[algq_document_packages]`

Private document authorization applies to every preview and download.

### PDF & Signature Engine

- `[algq_pdf_engine]`
- `[algq_signature_archive]`

Generated PDFs remain protected records, are indexed according to the platform archive workflow, and must preserve document/audit provenance.

### Automation Engine

Preferred dedicated interfaces:

- `[algq_automation_overview]`
- `[algq_automation_getting_started]`
- `[algq_automation_docs]`
- `[algq_automation_rules]`

`[algq_automation_engine]` is a compatibility/general interface and should not replace the richer native pages.

### Command Center

The Command Center must render operational KPIs, pipeline/reporting information, health state and authorized administrative functions through its native shortcode family. `[algq_admin_dashboard]` remains a valid page-facing interface, while dedicated Command Center views should use the more specific native tags registered by the plugin.

### Buyer Portal and Marketplace

Buyer Portal owns registration, login, buyer account/dashboard and protected buyer-deal access. Deal Marketplace owns controlled opportunity publication, marketplace presentation, NDA/access gates and buyer responses/offers.

The intended access path is:

`Investors & Capital → Buyer Registration → Account → Buyer Login → Buyer Dashboard → Authorized Marketplace Opportunity`

Buyer Login pages must render the live `[algq_buyer_login]` interface within the ARE page design, not a generic scaffold.

### Funding Tracker

Funding interfaces track capital sources, financing gaps, commitments, seller notes, maturities/balloons and funding status. Seller-financing debt becomes a Funding Tracker record after terms become operational; Funding Tracker does not own underwriting calculations.

### Digital Products / Digital Store / WooCommerce Bridge

Commerce interfaces must preserve the boundary between Algonquian product/access metadata and WooCommerce's authoritative order/payment records. Do not duplicate WooCommerce transactions.

### Property Stewardship Services

Stewardship interfaces must reflect owner-authorized property services, visits, vendor coordination, reporting and client access. They must not imply governmental, fiduciary or property-management authority beyond the actual service agreement.

## Page styling contract

For public ARE pages, preserve the established institutional composition unless a page has an intentionally different approved design:

- full-width hero;
- parallax image 2036 or the approved page-family image (including 6422 where that variant is already used);
- deep navy overlay;
- gold outlined classification badge;
- large white institutional H1;
- gold divider;
- white lead copy and muted supporting copy;
- paired CTA actions where useful;
- four operational summary cards where the page calls for a summary layer;
- blue/teal/gold/white accent system;
- generous spacing and responsive stacking.

Plugin-rendered workspaces should use the same design tokens without unnecessarily reproducing the entire public hero inside an application panel.

## Navigation behavior

Desktop navigation must not overflow the viewport. Utility actions should compact before primary navigation becomes unreadable.

Mobile behavior must be one-stage:

`Hamburger → full primary menu visible → section expands → page selected`

Do not require a second "Menu" control merely to reveal the primary navigation after the drawer opens.

## Release verification

Before a shortcode change is promoted:

- verify the tag is registered at runtime;
- render it through `do_shortcode()` in an appropriate role context;
- assert that the returned markup does not contain the literal shortcode tag;
- assert that it contains meaningful non-placeholder content;
- test empty-state output;
- test unauthorized output;
- test mobile and desktop layout;
- verify required assets load once;
- verify forms and state-changing actions pass the platform production-form gate;
- verify no malformed WPBakery closing tags are introduced.

The repository source, not historical conversation notes, is the final authority on whether a shortcode is actually registered. Conversation-approved functionality and UI changes must be synchronized into source before they are described as deployed or production-ready.
