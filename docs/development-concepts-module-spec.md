# Algonquian Development Concepts Module

## Purpose

The Development Concepts module presents independent redevelopment studies for underutilized Connecticut properties while clearly distinguishing conceptual work from owned, listed, represented, or contract-controlled opportunities.

## Core Requirements

### Content Types

- `algq_development_concept`
- `algq_concept_category`
- `algq_concept_status`

### Standard Statuses

- Concept
- Preliminary Study
- Feasibility Review
- Owner Outreach Planned
- Initial Contact Made
- Discussions Active
- Opportunity Controlled
- Completed Project

### Required Fields

- Concept title
- Study location
- Municipality
- Property type
- Existing-condition summary
- Concept vision
- Potential uses
- Community benefits
- Known constraints
- Due-diligence items
- Owner-outreach status
- Relationship-to-property statement
- Featured image
- Existing-property gallery
- Concept renderings
- Rendering attribution
- Public-record source notes
- Last verified date

### Mandatory Public Disclosure

Each concept page must display:

> Independent Development Concept. Unless specifically stated otherwise, Algonquian Real Estate LLC does not own, control, represent, list, or possess contractual rights regarding this property. This concept was independently prepared from publicly available information and field observations to demonstrate redevelopment possibilities and facilitate future discussion.

### Rendering Notice

Each conceptual image must display or be accompanied by:

> CONCEPTUAL RENDERING — NOT AN APPROVED OR AUTHORIZED PROJECT.

### Access Levels

1. Public concept page
   - Address and public parcel facts
   - Existing photographs
   - High-level redevelopment ideas
   - Watermarked renderings
   - Transparency disclosures

2. Registered-interest layer
   - Extended feasibility summary
   - Estimated redevelopment program
   - Interest-registration form
   - Terms acceptance record

3. Restricted deal room
   - Available only after ownership discussions or contractual control
   - Internal underwriting
   - Seller communications
   - Negotiated terms
   - Confidential reports and documents

## Public Routes

- `/development-concepts`
- `/development-concepts/{concept-slug}`
- `/development-concepts/category/{category-slug}`
- `/development-concepts/status/{status-slug}`
- `/submit-a-property-concept`

## Shortcodes

- `[algq_development_concepts]`
- `[algq_development_concept id="123"]`
- `[algq_concept_categories]`
- `[algq_concept_status]`
- `[algq_submit_property_concept]`

## Administrative Functions

- Create and edit concept records
- Upload existing-property photographs
- Upload watermarked conceptual renderings
- Assign concept categories and statuses
- Record public-record sources
- Record owner-outreach activity
- Change relationship-to-property status
- Publish or withdraw a concept
- Convert a concept to a controlled opportunity
- Review disclosure-compliance status

## Pipeline CRM Integration

A concept may optionally create or link to a Pipeline CRM record.

Suggested relationship fields:

- `concept_id`
- `deal_id`
- `owner_outreach_status`
- `owner_contacted_at`
- `relationship_status`
- `control_type`
- `control_effective_date`
- `control_expiration_date`

Publishing a concept must not automatically classify the property as an available deal.

## Deal Intake Integration

Owner and stakeholder inquiries from concept pages should create an intake record with:

- Lead source: Development Concept
- Concept ID
- Property address
- Inquiry type
- Relationship to property
- Request type
- Consent record

## Document Library Integration

Concept records may link to:

- Public-record extracts
- Site photographs
- Preliminary zoning research
- Environmental-screening notes
- Concept boards
- Owner outreach correspondence
- Feasibility memoranda

Document access classifications must be enforced.

## Automation Events

- `development_concept.created`
- `development_concept.published`
- `development_concept.withdrawn`
- `development_concept.owner_outreach_planned`
- `development_concept.owner_contacted`
- `development_concept.discussions_active`
- `development_concept.control_obtained`
- `development_concept.inquiry_received`

## Security and Compliance

- Capability checks for all administrative actions
- Nonces for state-changing requests
- Escaped public output
- Sanitized source notes and addresses
- Private storage for nonpublic materials
- Audit logging for status and disclosure changes
- No owner phone number, private email, negotiated offer, or proprietary underwriting on public pages
- No representation that the property is for sale without authorization
- No representation that ARE owns or controls a property unless supported by current records

## Branding Standard

Use Algonquian Real Estate navy, gold, white, and blue branding. Concept boards should preserve the actual building massing and identifying architectural characteristics where source imagery is used. Renovation visuals should be labeled as conceptual and should not imply owner or municipal approval.

## WPBakery Standard

Generated pages must use correct shortcode syntax:

```text
[vc_column_text]
Content
[/vc_column_text]
```

Never use an HTML-style `</vc_column_text>` closing tag.
