# /platform-interfaces Production Specification

## Purpose

The **Platform Interfaces** page is the unified public/authorized interface hub for the Algonquian Real Estate operating platform. It presents the real plugin-owned forms, dashboards, calculators, portals, document systems, buyer interfaces, automation tools, and transaction workspaces that support the canonical lifecycle:

**Lead → Intake → Qualification → Deal → Underwriting → Offer → Documents → Funding → Closing → Operations → Reporting**

The page is a presentation and routing layer only. It must not create a second authoritative copy of seller, deal, underwriting, offer, buyer, funding, document, or stewardship data.

## Recommended Page Title

Algonquian Real Estate Platform Interfaces

## Recommended URL

`/platform-interfaces/`

## Hero Copy

**Badge:** `ARE Technology • Platform Interfaces`

**H1:** `Algonquian Real Estate Platform Interfaces`

**Lead:** `One Operating Platform. Specialized Interfaces. Controlled Transaction Workflows.`

**Supporting copy:**

> Access the operational forms, dashboards, calculators, portals, document systems, buyer interfaces, automation tools, and transaction workspaces that support the Algonquian Real Estate lifecycle.

Primary CTAs:

- Submit a Property → `/submit-a-property/`
- Open Platform Overview → `#platform-overview`

## Required Operational Forms

The page must expose the actual authoritative plugin interfaces rather than placeholder descriptions.

```text
[algq_deal_intake_form]
[algq_buyer_registration]
[algq_document_request]
[algq_buyer_offer_form]
```

## Required Platform and Transaction Interfaces

```text
[algq_platform_overview]
[algq_plugin_suite]
[algq_pipeline_dashboard]
[algq_pipeline_board]
[algq_mao_calculator]
[algq_offer_generator]
[algq_funding_tracker]
[algq_buyer_login]
[algq_buyer_dashboard]
[algq_deal_marketplace]
[algq_stewardship_portal]
[algq_document_library]
[algq_automation_overview]
[algq_command_center]
[algq_property_stewardship]
```

## Required Sections

1. Hero and paired CTAs.
2. Four-card operating model: Intake, Analyze, Execute, Advance.
3. Operational Forms.
4. Platform Overview.
5. Plugin Suite.
6. Deal & Acquisition Interfaces.
7. Buyer, Marketplace & Client Interfaces.
8. Operational Interfaces.
9. Final Technology Division CTA.

## Authority Boundaries

- Platform Plugin → shared registry, capabilities, audit, mail, protected storage, health/status and integration contracts.
- Deal Intake → seller/property intake.
- Pipeline CRM → canonical Deal record.
- MAO Engine → underwriting.
- Offer Generator → offers/proposals.
- Document Library → controlled document records.
- PDF & Signature → rendering/signature workflows.
- Funding Tracker → funding/capital records.
- Buyer Portal → buyer registration/login/account access.
- Deal Marketplace → controlled opportunity distribution and buyer offer workflow.
- Automation Engine → workflow rules/execution.
- Command Center → cross-system oversight.
- Property Stewardship → property-service/client records.

The Platform Interfaces page must never bypass those authority boundaries.

## WPBakery Standard

Every embedded shortcode must use valid WPBakery syntax:

```text
[vc_column_text]
[algq_shortcode]
[/vc_column_text]
```

Never use `</vc_column_text>`.

## Design Standard

Use the current ARE public-page system:

- full-width `stretch_row_content` sections;
- hero image 6422 where available;
- deep navy overlay;
- gold badge/divider/actions;
- teal operational accents;
- white/light workspace surfaces;
- rounded cards;
- institutional typography;
- responsive layouts;
- restrained motion only;
- `prefers-reduced-motion` support.

## Production Acceptance Criteria

- No raw unresolved shortcode text.
- No placeholder form tokens.
- Every registered interface renders real UI, a meaningful empty state, a dependency state, or an authorization state.
- Protected interfaces remain capability/record-authorized.
- Public forms remain server-validated and plugin-owned.
- Page does not create duplicate business records.
- Mobile layout remains usable.
- All WPBakery shortcode pairs are valid.
