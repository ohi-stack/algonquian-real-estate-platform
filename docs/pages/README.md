# Algonquian Real Estate Public Page Specifications

This directory contains source-level production specifications for high-value public and operational pages on AlgonquianRealEstate.com.

These documents are reference/source records for WordPress/WPBakery implementation. They do not themselves certify that the corresponding page has been deployed to the live WordPress installation.

## Current Page Specifications

| Page | Canonical route | Primary role |
|---|---|---|
| Platform Interfaces | `/platform-interfaces/` | Unified access point for plugin-owned forms, dashboards, portals and transaction interfaces |
| Automation Engine | `/algonquian-automation-engine/` | Workflow automation overview, rules, documentation and human-control boundaries |
| Founder and Managing Member | `/about/founder-and-managing-member/` | Gregory Jones leadership profile with documented governance disclosure |
| Documents | See `documents.md` | Document access and document-system page requirements |
| Lenders | See `lenders.md` | Lender/capital page requirements |
| Underwriting | `/underwriting/` | Acquisition analysis and underwriting workspace requirements |

## Governing Page Rules

- Use the current ARE public design system: deep navy, ARE blue, gold, teal, white/light surfaces, institutional typography, rounded cards and restrained motion.
- Use full-width WPBakery layouts where specified.
- Use real registered plugin shortcodes for operational interfaces rather than placeholders.
- Preserve one authoritative operational owner per business domain.
- Keep protected interfaces capability- and record-authorized.
- Do not convert assumptions, plans, projections, prospective partnerships, financing or future production state into facts.
- Preserve human approval for offers, contracts, legal decisions, capital commitments, funds movement and closing authority.

## WPBakery Syntax

Always use:

```text
[vc_column_text]
Content or shortcode
[/vc_column_text]
```

Never use:

```text
</vc_column_text>
```

## Deployment Status

A page specification being present in this repository means its content, architecture and acceptance criteria are documented. Live deployment remains a separate WordPress implementation and validation step.
