# Navigation Architecture — Mega Menu and Footer

## Purpose

This document defines the production navigation structure for AlgonquianRealEstate.com and the Algonquian Real Estate Platform plugin suite.

The site navigation must support:

- Public seller lead acquisition
- Buyer and investor access
- Deal marketplace access
- Document and lender workflows
- Education and digital products
- Internal operating dashboards

This architecture aligns with the plugin suite model, including Deal Intake, Pipeline CRM, MAO Engine, Offer Generator, Buyer Portal, Funding Tracker, Automation Engine, PDF & Signature Engine, Document Library, and Admin Command Center.

## Header Mega Menu Requirement

The primary header menu must contain **6 main links**.

Each main link may open a mega menu with **up to 6 columns**.

Recommended top-level navigation:

1. Sell
2. Buy / Invest
3. Deals
4. Tools
5. Resources
6. Company

---

## 1. Sell

Primary URL: `/sell-your-property`

Mega menu columns:

### Column 1 — Seller Options
- Sell Your Property
- Request Cash Offer
- Seller Financing Inquiry
- Subject-To Inquiry
- Distressed Property Intake
- Landlord Exit Form

### Column 2 — Property Types
- Single Family
- Multifamily
- Rental Property
- Vacant Land
- Distressed Property
- Estate Property

### Column 3 — Seller Tools
- Property Intake Form
- Repair Estimate Upload
- Mortgage Balance Form
- Timeline to Sell
- Upload Photos
- Schedule Call

### Column 4 — How It Works
- Step 1: Submit Property
- Step 2: Review
- Step 3: Offer
- Step 4: Close
- Frequently Asked Questions
- Contact Acquisitions

### Column 5 — Forms
- Property Intake Checklist
- Seller Information Form
- Due Diligence Checklist
- Inspection Checklist
- Title Review Checklist
- Contact Form

### Column 6 — Featured CTA
- Get an Offer
- Start Seller Intake
- Speak With Acquisitions

---

## 2. Buy / Invest

Primary URL: `/buyers/register`

Mega menu columns:

### Column 1 — Buyer Access
- Buyer Registration
- Buyer Login
- Buyer Dashboard
- Available Deals
- Saved Deals
- Deal Alerts

### Column 2 — Investor Types
- Cash Buyers
- Multifamily Buyers
- Land Buyers
- Institutional Buyers
- Private Investors
- Joint Venture Partners

### Column 3 — Deal Access
- Premium Deal Access
- Early Access Deals
- VIP Buyer Tier
- Proof of Funds Upload
- NDA Access
- Deal Package Downloads

### Column 4 — Investment Tools
- MAO Calculator
- Deal Analyzer
- Rental Calculator
- Cap Rate Calculator
- Cash Flow Worksheet
- Underwriting Dashboard

### Column 5 — Funding
- Funding Request
- Lender Directory
- Private Capital
- JV Capital
- Hard Money
- Capital Stack Review

### Column 6 — Featured CTA
- Join Buyer List
- View Deals
- Request Funding Match

---

## 3. Deals

Primary URL: `/deals`

Mega menu columns:

### Column 1 — Deal Pipeline
- All Deals
- New Deals
- Underwriting
- Offers Sent
- Under Contract
- Closed Deals

### Column 2 — Marketplace
- Deal Marketplace
- Featured Deals
- Hot Deals
- Buyer Offers
- Assignment Opportunities
- Deal Alerts

### Column 3 — Underwriting
- MAO Engine
- ARV Review
- Repair Estimates
- Rent Analysis
- Comparable Sales
- Risk Flags

### Column 4 — Offers
- Offer Generator
- Cash Offer
- Seller Financing Offer
- Subject-To Offer
- Letter of Intent
- Purchase Agreement

### Column 5 — Documents
- Deal Documents
- Lender Package
- Due Diligence Files
- Inspection Reports
- Rent Rolls
- Closing Checklist

### Column 6 — Featured CTA
- Submit a Deal
- Analyze a Deal
- Generate Offer

---

## 4. Tools

Primary URL: `/tools`

Mega menu columns:

### Column 1 — Calculators
- MAO Calculator
- Rental Analyzer
- Cap Rate Calculator
- Cash Flow Calculator
- Repair Estimator
- Offer Calculator

### Column 2 — Automation
- Automation Engine
- Lead Scoring
- Follow-Up Automation
- Buyer Alerts
- Document Triggers
- Workflow Rules

### Column 3 — Documents
- Document Library
- PDF Generator
- Signature Engine
- Template Store
- Lender Package Builder
- Compliance Forms

### Column 4 — Dashboards
- Admin Command Center
- Deal Dashboard
- Buyer Dashboard
- Funding Dashboard
- Document Dashboard
- KPI Reports

### Column 5 — Digital Products
- Contract Templates
- MAO Spreadsheet
- Acquisition Checklists
- SOPs
- Investor Toolkits
- Education Downloads

### Column 6 — Featured CTA
- Open Tools
- Download Templates
- View Dashboard

---

## 5. Resources

Primary URL: `/resources`

Mega menu columns:

### Column 1 — Learn
- Education Portal
- Real Estate Training
- Deal Analysis Lessons
- Seller Financing
- Subject-To Basics
- Connecticut Investing

### Column 2 — Forms & Documents
- Entity Documents
- Lender Documents
- Acquisition Forms
- Financial Controls
- Risk Management
- Property Management Forms

### Column 3 — Guides
- First Deal Guide
- Seller Financing Guide
- Wholesale Assignment Guide
- Buyer Guide
- Funding Guide
- Due Diligence Guide

### Column 4 — Blog / Updates
- Market Updates
- Platform Updates
- Investor Notes
- Case Studies
- Development Logs
- Release Notes

### Column 5 — Support
- Documentation
- Getting Started
- FAQs
- Contact Support
- Book Consultation
- Report Issue

### Column 6 — Featured CTA
- View Resources
- Download Checklist
- Start Learning

---

## 6. Company

Primary URL: `/about`

Mega menu columns:

### Column 1 — Company
- About
- Contact
- Mission
- Acquisition Criteria
- Service Area
- Waterbury, Connecticut

### Column 2 — Algonquian Real Estate
- Company Overview
- Entity Information
- ARE Technology Division
- Internal Documentation
- Platform Overview
- Investor Relations

### Column 3 — Legal / Policies
- Terms of Use
- Privacy Policy
- Disclaimer
- Earnings Disclaimer
- Education Disclaimer
- Accessibility

### Column 4 — Technology
- Plugin Suite
- SaaS Roadmap
- Automation Systems
- Product Catalog
- Version Roadmap
- Licensing

### Column 5 — Contact Paths
- Seller Contact
- Buyer Contact
- Lender Contact
- Partner Contact
- Support Contact
- General Inquiry

### Column 6 — Featured CTA
- Contact Algonquian Real Estate
- View Platform
- Partner With Us

---

# Footer Requirement

The footer must contain **4 columns of links**.

## Footer Column 1 — Company
- About
- Contact
- Sell Your Property
- Acquisition Criteria
- Service Area
- Algonquian Real Estate LLC

## Footer Column 2 — Platform
- Deal Intake
- Pipeline CRM
- MAO Engine
- Buyer Portal
- Document Library
- Admin Command Center

## Footer Column 3 — Investors / Buyers
- Buyer Registration
- Buyer Login
- Available Deals
- Funding Request
- Investor Resources
- Deal Alerts

## Footer Column 4 — Legal / Resources
- Forms & Documents
- Terms of Use
- Privacy Policy
- Disclaimer
- Education Disclaimer
- Contact Support

---

# WordPress Implementation Notes

## Navigation Menus

Register these WordPress menus:

```php
register_nav_menus(
    array(
        'algq_primary_menu' => __( 'Algonquian Primary Mega Menu', 'algq-real-estate-platform' ),
        'algq_footer_company' => __( 'Footer Company Links', 'algq-real-estate-platform' ),
        'algq_footer_platform' => __( 'Footer Platform Links', 'algq-real-estate-platform' ),
        'algq_footer_investors' => __( 'Footer Investor Links', 'algq-real-estate-platform' ),
        'algq_footer_legal' => __( 'Footer Legal & Resources Links', 'algq-real-estate-platform' ),
    )
);
```

## Shortcodes

Recommended shortcodes:

```text
[algq_mega_menu]
[algq_footer_links]
```

## WPBakery Placement

Use:

```text
[vc_column_text]
[algq_mega_menu]
[/vc_column_text]
```

Never use HTML closing syntax such as `</vc_column_text>` because it breaks WPBakery layouts.

---

# Styling Requirements

Use Algonquian institutional branding:

- Navy background
- Gold accent borders
- White text
- Charcoal secondary text
- Clean institutional spacing
- Responsive mobile fallback

Mega menu layout:

- Desktop: up to 6 columns
- Tablet: 2–3 columns
- Mobile: accordion or stacked columns

Footer layout:

- Desktop: 4 columns
- Tablet: 2 columns
- Mobile: 1 column

---

# Production Acceptance Criteria

- Header contains exactly 6 primary links.
- Each primary link can support up to 6 columns.
- Footer contains exactly 4 structured link columns.
- Links match the platform page architecture.
- Public, buyer, lender, document, and internal system paths are separated clearly.
- Mobile navigation remains usable.
- WPBakery shortcode syntax is valid.
- Styling is scoped to avoid breaking the active WordPress theme.
