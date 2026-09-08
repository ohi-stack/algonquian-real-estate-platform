# ARE Housing Access Pilot — Operating Architecture

## Status

Pilot operating specification for Algonquian Real Estate LLC (ARE). This document defines the rental/housing-access workflow and controlled-document architecture. It does not itself create a lease, master lease, government program, brokerage relationship, funding commitment, or guarantee of housing.

## Operating Position

The ARE Housing Access Pilot is a private housing-access and lease-support initiative for financially qualified households facing rental-access or move-in barriers. Public eligibility and screening must use legally appropriate, neutral standards. Outreach may address mothers, parents, families, or other households facing hardship, but ARE must not represent the pilot as a government housing program or housing authority.

ARE may use three transaction tracks:

1. **ARE-Owned / ARE-Controlled Housing** — ARE leases directly to the resident when ARE has lawful control of the property.
2. **ARE Master Lease** — ARE becomes the property owner's contractual tenant only where the owner expressly authorizes the intended residential occupancy/sublease structure and the transaction passes ARE approval controls.
3. **Owner-Direct Lease** — the owner leases directly to the resident and ARE performs only legally permissible, documented coordination services.

## Hard Transaction Rule

ARE must not execute a financially binding master lease until the household, property, transaction economics, assistance structure, contractual authority, insurance, required funding, and final human approval are complete.

Pending assistance is not committed funding. A provider's household approval does not automatically establish property approval, payment authorization, or compatibility with an Owner → ARE → Resident structure.

## Controlled Document Sequence

| ID | Document | Control Purpose |
|---|---|---|
| ARE-HAP-001 | Program Overview & Disclosure | Public/private program definition and disclosures |
| ARE-HAP-002 | Housing Access Intake Application | Household intake and authorization |
| ARE-HAP-003 | Household Qualification & Affordability Worksheet | Affordability, residual income, rent ceiling and risk review |
| ARE-HAP-004 | Income & Employment Verification Checklist | Income evidence, continuity and qualification income |
| ARE-HAP-005 | Rental Assistance Verification Form | Assistance amount, status, property rules, payee and structure compatibility |
| ARE-HAP-006 | Housing Search Criteria Worksheet | Approved property-search mandate |
| ARE-HAP-007 | Property Qualification & Housing Match Worksheet | Property, owner, condition, household and transaction fit |
| ARE-HAP-008 | Landlord / Property Owner Partnership & Master-Lease Proposal | Nonbinding owner-facing proposal and preliminary business terms |
| ARE-HAP-009 | Master Lease Term Sheet & Deal Approval | Final economics, risk, reserve and human approval gate before signing |

Future controls begin with ARE-HAP-010 for master-lease execution/closing and pre-occupancy GO/NO-GO review.

## Workflow

```text
Household Intake
→ Income / Employment Verification
→ Assistance Verification
→ Affordability Approval
→ Housing Search Authorization
→ Property Identification
→ Property Qualification
→ Owner / Structure Negotiation
→ Assistance / Inspection Approval
→ Master Lease Term Sheet & Deal Approval
→ Final Legal Documents
→ Execution / Closing Checklist
→ Resident Move-In
→ Active Housing Administration
→ Renewal / Transition / Close
```

## Pipeline Stages

The Housing Access workflow should use a specialized pipeline view without creating a second authoritative deal record system:

1. New Applicant
2. Documents Pending
3. Qualification
4. Assistance Verification
5. Housing Search
6. Property Identified
7. Owner Negotiation
8. Program Approval
9. Lease Preparation
10. Approved
11. Move-In
12. Active Housing
13. Renewal / Transition
14. Closed

## Plugin / System Ownership

Housing Access is a cross-platform workflow, not a new canonical plugin owner.

- **Deal Intake:** initial housing-access application/intake and consent records.
- **Pipeline CRM:** canonical workflow/deal record, stage, tasks, assignments, next actions and activity history.
- **Document Library:** restricted HAP documents, versions, access classification and retention.
- **PDF & Signature Engine:** rendering/signature routing for approved controlled documents and legally operative documents where configured.
- **Automation Engine:** missing-document reminders, follow-up tasks, approval triggers, inspection reminders and renewal alerts.
- **Admin Command Center:** pilot KPIs, exception alerts, exposure and approval visibility.
- **Property Stewardship:** authorized property visits, condition documentation and operational property check-ins where separately permitted.
- **MAO Engine:** remains authoritative for acquisition underwriting. Housing-access affordability/master-lease operating review must not overwrite acquisition MAO calculations.
- **Offer Generator:** remains authoritative for acquisition/seller proposals. Housing-access owner proposals must be separately typed and must not masquerade as purchase offers.

## Data Domains

Minimum Housing Access records should distinguish:

- household/application record;
- household members;
- employment/income verification;
- affordability decision and approved rent ceiling;
- assistance-provider verification;
- housing-search criteria;
- candidate property match;
- owner/landlord relationship;
- proposed transaction structure;
- master-lease economics and reserve exposure;
- inspections/condition;
- approvals and conditions precedent;
- resident payment schedule;
- owner payment schedule;
- active occupancy status;
- renewal/transition outcome.

Sensitive household records require restricted access and should not be exposed through ordinary public WordPress pages or public REST responses.

## Public Website Placement

Housing Access belongs primarily under **Services**, with contextual links from relevant Property Owner and Company/Resources pages. It must not become a seventh primary header area.

Recommended public routes:

- `/services/housing-access/` — Housing Access & Lease Support
- `/services/housing-access/how-it-works/`
- `/services/housing-access/apply/`
- `/services/housing-access/rental-assistance/`
- `/services/housing-access/housing-search/`
- `/services/housing-access/landlords/`
- `/services/housing-access/master-lease/`
- `/services/housing-access/frequently-asked-questions/`
- `/services/housing-access/disclosures/`

Recommended owner contextual route:

- `/property-owner-solutions/master-lease-partnership/`

Protected application routes may include:

- `/housing-access-portal/`
- `/housing-access-portal/application/`
- `/housing-access-portal/documents/`
- `/housing-access-portal/housing-search/`
- `/housing-access-portal/property-matches/`
- `/housing-access-portal/assistance/`
- `/housing-access-portal/messages/`
- `/housing-access-portal/lease/`

Protected administrative routes may include:

- `/admin/housing-access/`
- `/admin/housing-access/applications/`
- `/admin/housing-access/properties/`
- `/admin/housing-access/approvals/`
- `/admin/housing-access/exposure/`
- `/admin/housing-access/reports/`

These are architecture targets; they are not considered operational until implemented, permissioned, tested and documented.

## Public Navigation Rule

The approved ARE header remains exactly six primary business areas:

1. Property Owners
2. Acquisitions
3. Investors & Capital
4. Services
5. Technology
6. Company

Utilities remain Search, Buyer Login, Client Portal and Submit a Property.

Housing Access is placed inside **Services**. Landlord/master-lease partnership information may also be contextually linked from **Property Owners**. Protected housing-access screens belong in Client Portal/application navigation rather than the public header.

## Approval Boundary

Agents and automations may collect, classify, calculate, draft, remind, route and monitor. Human ARE leadership retains authority for:

- final household exceptions;
- rent-ceiling exceptions;
- owner negotiation;
- master-rent approval;
- reserve allocation;
- legal/insurance exception acceptance;
- binding master lease execution;
- resident lease execution where applicable;
- funds movement; and
- final occupancy authorization.

## Pilot KPI

Initial operating target:

```text
2 pilot households
→ 2 qualified files
→ 2 verified assistance structures
→ 2 suitable property matches
→ 2 compliant lease arrangements
→ 2 successful move-ins
→ sustained rent performance
```

The KPI is an operating target, not a guarantee.
