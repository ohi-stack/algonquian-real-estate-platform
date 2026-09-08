# Navigation Architecture — Public Website, Mega Menu, Portals and Footer

## Purpose

This document defines the current public navigation standard for AlgonquianRealEstate.com and its relationship to the broader Algonquian Real Estate platform architecture.

The WordPress WXR architecture contains **303 pages: 216 public and 87 protected/private**. The WXR is a route inventory and application architecture; it is not the public header menu.

The public website must keep customer-facing navigation simple while preserving access to seller, acquisition, investor/capital, service, technology, company and protected application workflows.

## Authoritative Header Standard

The primary header contains exactly **6 primary areas**:

1. **Property Owners**
2. **Acquisitions**
3. **Investors & Capital**
4. **Services**
5. **Technology**
6. **Company**

Each primary area may use a mega menu with up to 6 columns.

### Header Utilities

Utilities are separate from the six primary areas:

- Search
- Buyer Login
- Client Portal
- Submit a Property

Protected deal, funding, document, automation, plugin administration and housing-access application screens must not be exposed as ordinary public-header destinations.

---

# 1. Property Owners

Primary URL: `/property-owner-solutions/`

Recommended mega-menu groups:

### Sell / Transition
- Sell Your Property
- What Are My Options?
- Sell As-Is
- Seller Financing Options
- Property Transition Planning
- Submit a Property

### Life / Property Situations
- Inherited Property Guidance
- Estate Transition Assistance
- Empty Nest Transition Planning
- Senior Property Assistance
- Distressed Property Rescue
- Property Legacy Planning

### Stewardship
- Property Stewardship Services
- Essential Property Watch
- Active Stewardship
- Transition Support
- Vacant Property Monitoring
- Property Check-Ins

### Owner Support
- Vendor Coordination
- Storm Inspections
- Community Property Preservation
- Trusted Property Contact
- Request Stewardship Service

### Housing Partnerships
- Master-Lease Partnership
- Housing Access Landlord Information
- Property Qualification
- Owner Documentation
- Housing Assistance Cooperation

### Featured Actions
- Submit a Property
- Request Service
- Contact Property Team

---

# 2. Acquisitions

Primary URL: `/investment-opportunities/`

Recommended mega-menu groups:

### Opportunities
- Available Deals
- Off-Market Properties
- Multifamily
- Mixed-Use
- Commercial
- Value-Add

### Acquisition Criteria
- Acquisition Criteria
- Submit a Property
- Real Estate Acquisition
- Property Underwriting
- Deal Analysis

### Development
- Development Concepts
- Residential
- Multifamily
- Mixed-Use
- Adaptive Reuse
- Development Sites

### Deal Process
- Property Submission
- Qualification
- Underwriting
- Offer
- Due Diligence
- Closing

### Partner Paths
- Joint Ventures
- Private Lending
- Capital Partners
- Buyer Network

### Featured Actions
- Submit a Property
- View Opportunities
- Contact Acquisitions

---

# 3. Investors & Capital

Primary URL: `/investors/`

Recommended mega-menu groups:

### Investor Network
- Join Investor Network
- How It Works
- Investment Criteria
- Buyer Network
- Deal Packages
- Investor Resources

### Capital Partners
- Private Lenders
- Institutional Lenders
- Joint Venture Equity
- Funding Request
- Lender Package
- Contact Capital Team

### Lender Resources
- Company Overview
- Business Plan
- Acquisition Criteria
- Underwriting Standards
- Source & Use of Funds
- Risk Management

### Deal Access
- Available Deals
- Off-Market Properties
- Marketplace
- Buyer Login
- Buyer Dashboard

### Documents / Disclosures
- Document Request
- Lender Document Library
- Investor Disclosures
- NDA Access
- Frequently Asked Questions

### Featured Actions
- Join Investor Network
- Request Lender Package
- Buyer Login

---

# 4. Services

Primary URL: `/services/`

Recommended mega-menu groups:

### Real Estate Services
- Real Estate Acquisition
- Property Underwriting
- Deal Analysis
- Property Transition Support
- Document Preparation

### Property Stewardship
- Property Stewardship
- Essential Property Watch
- Active Stewardship
- Transition Support
- Property Check-Ins

### Housing Access & Lease Support
- Housing Access & Lease Support
- How Housing Access Works
- Apply for Housing Access
- Rental Assistance Coordination
- Housing Search Support
- Housing Access Disclosures

### Landlord / Housing Partnerships
- Landlord Partnership
- Master-Lease Housing
- Property Qualification
- Assistance-Program Coordination
- Housing Access FAQ

### Technology Services
- Technology Implementation
- Workflow Automation
- Custom Platform Setup
- Consulting

### Featured Actions
- Request a Service
- Apply for Housing Access
- Client Portal

Housing Access is a private ARE service/workflow. It is not a government program, housing authority or guaranteed housing benefit. See `docs/ARE-HOUSING-ACCESS-PILOT.md` for the operating architecture.

---

# 5. Technology

Primary URL: `/technology/`

Recommended mega-menu groups:

### ARE Technology Division
- Overview
- Platform
- Technology Infrastructure
- Platform Architecture
- Shared Services
- API & Integrations

### Automation / Agents
- Automation Infrastructure
- Agent Infrastructure
- Operational Event System
- Human Approval & Control Framework

### Platform Operations
- Data & Records Architecture
- Security & Access Controls
- Transaction Workflow
- System Health & Monitoring
- Platform Governance

### Products
- Plugin Suite
- Software Licensing
- Digital Products
- Custom Implementations
- Automation Services

### Standards / Documentation
- ARE Technology Standards
- Platform Release & Production Standards
- Documentation
- Development Roadmap
- Security

### Featured Actions
- View Platform
- View Plugin Suite
- Contact ARE Technology

---

# 6. Company

Primary URL: `/about/`

Recommended mega-menu groups:

### Company
- About
- Company
- Leadership
- Company History
- Mission and Vision
- Operating Principles

### Contact
- General Inquiry
- Property Inquiry
- Investor Inquiry
- Lender Inquiry
- Vendor Inquiry
- Media Inquiry

### Resources
- Resources
- Guides
- Checklists
- Templates
- Connecticut Real Estate
- FAQs

### Help
- Help Center
- Getting Started
- Troubleshooting
- Technical Reference
- Contact Support

### Legal / Compliance
- Privacy Policy
- Terms of Use
- Accessibility
- Fair Housing
- Data Security
- Record Retention

### Featured Actions
- Contact ARE
- Client Portal
- Learn About ARE

---

# Protected Application Architecture

The following are application surfaces rather than public-header destinations:

- Deal Operating System (`/dashboard/`, `/deals/`, `/pipeline/`, `/underwriting/`, `/offers/`)
- Documents
- Funding
- Automation
- Buyer Dashboard / Marketplace account
- Property Stewardship Portal
- Housing Access Portal
- Account / authentication screens
- Administrative Platform
- Protected plugin interfaces

Access must be controlled by authentication plus appropriate WordPress/application capabilities. A WordPress page being marked private is not, by itself, the complete authorization model for sensitive records.

---

# Housing Access Route Placement

Recommended public service routes:

- `/services/housing-access/`
- `/services/housing-access/how-it-works/`
- `/services/housing-access/apply/`
- `/services/housing-access/rental-assistance/`
- `/services/housing-access/housing-search/`
- `/services/housing-access/landlords/`
- `/services/housing-access/master-lease/`
- `/services/housing-access/frequently-asked-questions/`
- `/services/housing-access/disclosures/`

Recommended contextual Property Owner route:

- `/property-owner-solutions/master-lease-partnership/`

Recommended protected routes:

- `/housing-access-portal/`
- `/housing-access-portal/application/`
- `/housing-access-portal/documents/`
- `/housing-access-portal/housing-search/`
- `/housing-access-portal/property-matches/`
- `/housing-access-portal/assistance/`
- `/housing-access-portal/messages/`
- `/housing-access-portal/lease/`

These routes are architecture targets until implemented and tested.

---

# Footer Requirement

The footer contains exactly **4 link columns**.

## Column 1 — Company
- About
- Leadership
- Contact
- Mission and Vision
- Operating Principles
- Company Information

## Column 2 — Property & Services
- Property Owner Solutions
- Sell Your Property
- Property Stewardship
- Housing Access & Lease Support
- Acquisition Criteria
- Submit a Property

## Column 3 — Investors & Technology
- Investor Network
- Capital Partners
- Buyer Login
- ARE Technology Division
- Plugin Suite
- Documentation

## Column 4 — Support & Legal
- Help Center
- Resources
- Client Portal
- Privacy Policy
- Terms of Use
- Fair Housing

---

# WordPress Implementation

Register the public menu and four footer locations:

```php
register_nav_menus(
    array(
        'algq_primary_menu' => __( 'Algonquian Primary Mega Menu', 'algq-real-estate-platform' ),
        'algq_footer_company' => __( 'Footer Company Links', 'algq-real-estate-platform' ),
        'algq_footer_services' => __( 'Footer Property & Services Links', 'algq-real-estate-platform' ),
        'algq_footer_investors_technology' => __( 'Footer Investors & Technology Links', 'algq-real-estate-platform' ),
        'algq_footer_support_legal' => __( 'Footer Support & Legal Links', 'algq-real-estate-platform' ),
    )
);
```

Recommended shortcodes:

```text
[algq_mega_menu]
[algq_footer_links]
```

WPBakery placement must use valid shortcode syntax:

```text
[vc_column_text]
[algq_mega_menu]
[/vc_column_text]
```

Never use `</vc_column_text>`.

---

# Presentation Standard

Use the ARE institutional interface system:

- Deep navy foundation
- ARE blue
- Gold accents
- Teal operational/status accents
- White/light surfaces where appropriate
- Responsive cards and navigation
- Accessible focus states
- `prefers-reduced-motion` support

Mega menu:

- Desktop: up to 6 columns
- Tablet: 2–3 columns
- Mobile: immediate hamburger opening; submenus expand on click

Footer:

- Desktop: 4 columns
- Tablet: 2 columns
- Mobile: 1 column

---

# Production Acceptance Criteria

- Exactly 6 public primary areas: Property Owners, Acquisitions, Investors & Capital, Services, Technology, Company.
- Search, Buyer Login, Client Portal and Submit a Property remain utilities.
- Housing Access appears under Services, with owner/master-lease contextual links under Property Owners.
- Protected application routes do not appear as ordinary public marketing destinations.
- WXR route inventory remains broader than header navigation.
- Mega menus support no more than 6 columns.
- Footer contains exactly 4 structured columns.
- Mobile hamburger opens immediately and nested submenus expand on click.
- Public and protected paths are clearly separated.
- WPBakery shortcode syntax is valid.
- Styling is scoped and responsive.
- Authorization is capability-based for sensitive application records.
