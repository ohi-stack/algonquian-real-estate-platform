# Algonquian Plugin Guide Page Manifest

This manifest defines the **How to Use** pages for the nine protected foundation plugins.

## Page standard

Every page must use the Algonquian enterprise WPBakery scaffold:

- full-width `stretch_row_content` hero
- parallax background image
- dark navy gradient overlay
- centered classification badge, heading, and introduction
- four operational summary cards
- primary and secondary `la_btn` actions
- alternating image/content sections
- light-gray instructional section
- guide-library links
- final dark parallax call to action

WPBakery content blocks must always close with `[/vc_column_text]`. Never use `</vc_column_text>`.

## Required pages

| Plugin | Page title | Suggested route | Primary action |
|---|---|---|---|
| Algonquian Real Estate Platform Plugin | How to Use the Algonquian Real Estate Platform Plugin | `/plugin/platform-plugin/how-to-use/` | Open Platform Dashboard |
| Algonquian Deal Intake | How to Use Algonquian Deal Intake | `/plugin/deal-intake/how-to-use/` | Open Seller Intake |
| Algonquian Pipeline CRM | How to Use the Algonquian Pipeline CRM | `/plugin/pipeline-crm/how-to-use/` | Open Pipeline Board |
| Algonquian MAO Engine | How to Use the Algonquian MAO Engine | `/plugin/mao-engine/how-to-use/` | Open Underwriting |
| Algonquian Offer Generator | How to Use the Algonquian Offer Generator | `/plugin/offer-generator/how-to-use/` | Open Offer Generator |
| Algonquian Document Library | How to Use the Algonquian Document Library | `/plugin/document-library/how-to-use/` | Open Document Library |
| Algonquian PDF & Signature Engine | How to Use the Algonquian PDF & Signature Engine | `/plugin/pdf-engine/how-to-use/` | Open Signature Center |
| Algonquian Automation Engine | How to Use the Algonquian Automation Engine | `/plugin/automation-engine/how-to-use/` | Open Automation Rules |
| Algonquian Admin Command Center | How to Use the Algonquian Admin Command Center | `/plugin/command-center/how-to-use/` | Open Command Center |

## Required content sections

Each page must contain:

1. Branded hero and plugin classification.
2. Four plugin-specific operational cards.
3. About This Plugin.
4. How to Use It, with five ordered steps.
5. Operational Standard.
6. Guide Library.
7. Primary operational action.
8. Documentation action.
9. Return to Plugin Library action.

## Plugin-specific card labels

### Platform Plugin

- Platform Registry
- Mail Gateway
- Permissions
- System Health

### Deal Intake

- Seller Intake
- Deal Creation
- Duplicate Review
- Lead Sources

### Pipeline CRM

- Deal Records
- Kanban Board
- Tasks & Notes
- Activity History

### MAO Engine

- Valuation Inputs
- Cost Analysis
- Offer Range
- Risk Review

### Offer Generator

- Templates
- Merge Fields
- Approval
- Offer Status

### Document Library

- Document Library
- Version Control
- Access Control
- Package Assembly

### PDF & Signature Engine

- PDF Rendering
- Signature Requests
- Execution Status
- Audit Evidence

### Automation Engine

- Triggers
- Conditions
- Actions
- Queue Control

### Admin Command Center

- Executive KPIs
- Operational Alerts
- Platform Health
- Reports & Exports

## Page-generation requirements

The platform page generator must:

- create these pages idempotently;
- store generated page IDs;
- avoid duplicate pages;
- preserve administrator-edited content;
- repair only a missing required marker or shortcode;
- apply `_algq_generated_page` metadata;
- apply a page-type marker such as `_algq_page_type = plugin_guide`;
- expose the generated pages to the plugin library cards;
- validate that all `[vc_column_text]` blocks close correctly.

## Protected foundation relationship

The guide pages describe the operational authority of each plugin. They must not imply that one plugin owns records assigned to another protected plugin. The Platform Plugin supplies shared infrastructure; Pipeline CRM owns the canonical deal lifecycle; MAO Engine owns underwriting; Offer Generator owns offer versions; Document Library owns document metadata; PDF & Signature owns rendering and execution; Automation Engine owns rules and queues; and the Command Center presents cross-platform reporting.