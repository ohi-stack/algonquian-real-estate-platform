# Plugin Guide Page Implementation

## Objective

Extend the protected platform page generator so activation or an authorized rebuild creates a branded **How to Use** page for each foundation plugin.

## Data contract

Each guide-page definition should declare:

```php
array(
    'title'         => 'How to Use Algonquian Deal Intake',
    'slug'          => 'plugin/deal-intake/how-to-use',
    'page_type'     => 'plugin_guide',
    'plugin_key'    => 'deal-intake',
    'eyebrow'       => 'Protected Foundation • Lead Capture',
    'heading'       => 'How to Use Algonquian Deal Intake',
    'intro'         => 'Capture seller inquiries, normalize property information, generate deal IDs, and create the first record in the acquisition workflow.',
    'primary_url'   => '/sell-your-property/',
    'primary_cta'   => 'Open Seller Intake',
    'secondary_url' => '/plugin/deal-intake/docs/',
    'secondary_cta' => 'View Documentation',
    'cards'         => array(),
    'steps'         => array(),
    'guides'        => array(),
)
```

## Generation behavior

1. Resolve the full hierarchical path, including parent pages.
2. Create missing parent pages without changing existing administrator content.
3. Create the guide page if it does not exist.
4. Store `_algq_generated_page`, `_algq_page_type`, and `_algq_plugin_key` metadata.
5. Preserve edited guide pages unless the generated marker is missing.
6. Add an explicit **Rebuild Generated Pages** command to the Command Center.
7. Return generated page IDs through the platform registry.

## Rendering behavior

The guide renderer should use:

- media attachment `6422` as the default hero image, filterable per page;
- media attachments `1535`, `2036`, and `2049` as default alternating content images;
- the approved dark navy overlay;
- four equal-width operational cards;
- accessible headings and link labels;
- valid nested `vc_row_inner` and `vc_column_inner` blocks;
- valid `[vc_column_text]...[/vc_column_text]` blocks;
- `la_btn` controls for operational and documentation actions.

## Required guide libraries

### Platform Plugin

- Initial Platform Setup and Configuration
- Managing Platform Roles and Permissions
- Configuring the Algonquian Mail Gateway
- Managing Platform Navigation and Generated Pages
- Using the Platform Health Monitor
- Reviewing Platform Audit Logs

### Deal Intake

- Creating and Publishing the Seller Intake Form
- How to Submit a Property Lead
- Reviewing New Seller Submissions
- Understanding Deal ID Generation
- Managing Lead Sources and Campaign Tracking
- Reviewing Potential Duplicate Leads

### Pipeline CRM

- Creating and Managing Deal Records
- Understanding the Acquisition Pipeline
- Using the Deal Kanban Board
- Moving Deals Through Pipeline Stages
- Adding Deal Notes, Tasks, and Activities
- Closing, Losing, Withdrawing, and Archiving Deals

### MAO Engine

- Creating a New Underwriting Scenario
- Calculating a Maximum Allowable Offer
- Understanding ARV, Repairs, Costs, and Profit Assumptions
- How to Underwrite a Multifamily Property
- How to Analyze Seller-Financing Terms
- Approving and Locking an Underwriting Scenario

### Offer Generator

- Creating an Offer From a Deal Record
- Selecting the Correct Offer Template
- Generating a Cash Offer
- Generating a Seller-Financing Proposal
- Using Offer Merge Fields
- Reviewing, Approving, Revising, and Versioning an Offer

### Document Library

- Uploading and Organizing Company Documents
- Managing Document Categories and Subcategories
- Understanding Document Versions
- Linking Documents to Deals and Properties
- Assembling a Lender or Transaction Package
- Managing Document Access, Retention, and Legal Holds

### PDF & Signature Engine

- Generating a PDF From an Approved Document
- Managing PDF Versions and File Hashes
- Creating a Signature Request
- Adding and Ordering Signers
- Tracking Signature Status
- Archiving Completed Documents and Reviewing Audit Evidence

### Automation Engine

- Understanding Triggers, Conditions, and Actions
- Creating Your First Automation Rule
- Automating Deal Stage Changes and Follow-Up
- Testing an Automation Rule
- Reviewing the Automation Queue
- Retrying Failed Jobs and Managing the Dead-Letter Queue

### Admin Command Center

- Understanding the Executive Dashboard
- Reviewing Deal and Pipeline KPIs
- Monitoring Offers, Contracts, and Closings
- Monitoring Email and Automation Failures
- Running a Platform Health Check
- Exporting Reports and Reviewing the Central Audit Log

## Validation

Before release, confirm:

- all nine routes are generated once;
- existing pages are not overwritten;
- every shortcode pair is balanced;
- all buttons resolve to an existing or intentionally future route;
- pages render correctly on desktop, tablet, and mobile;
- restricted operational links require the correct capability;
- page rebuilds are audited;
- deactivation does not delete generated pages;
- uninstall behavior is documented.