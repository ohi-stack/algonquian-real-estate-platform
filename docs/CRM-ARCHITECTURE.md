# Algonquian Real Estate CRM Architecture

**Owner:** Algonquian Real Estate LLC  
**Platform:** Algonquian Real Estate Platform  
**Canonical CRM engine:** Algonquian Pipeline CRM  
**Architecture status:** Platform standard

## Purpose

ARE uses one relationship ecosystem around the canonical Pipeline CRM rather than separate, competing CRM databases.

Pipeline CRM owns the canonical Deal record and the shared relationship layer used to identify people, organizations, activities, next actions, and links to deals. Specialized plugins retain authority over their own operational records.

## CRM domains

1. **Deal CRM — Pipeline CRM**
   - Canonical Deal ID and deal record.
   - Acquisition stage, assignment, priority, notes, tasks, activity, closing status, loss/disposition state.

2. **Seller / Property Owner CRM — shared relationship layer**
   - Property owners, tired landlords, absentee owners, inherited-property contacts, seller-financing prospects, prior/future sellers, and referral sources.
   - Relationship history, source, owner, status, priority, last activity, next action, and linked Deal IDs.

3. **Investor & Capital CRM — shared relationship layer + Funding Tracker**
   - Pipeline CRM stores the shared contact/organization relationship and activity history.
   - Funding Tracker remains authoritative for capital-source criteria, funding commitments, deal-level funding status, and administrative funding activity.
   - Interest, soft interest, commitment, and funded status must not be conflated.

4. **Buyer CRM — shared relationship layer + Buyer Portal / Deal Marketplace**
   - Pipeline CRM stores the shared contact/organization relationship and activity history.
   - Buyer Portal remains authoritative for buyer registration/profile/criteria and protected buyer access.
   - Deal Marketplace remains authoritative for opportunity publication, buyer visibility, access rules, NDAs, marketplace responses, and offer submissions.

5. **Professional / Referral Network CRM — shared relationship layer**
   - Attorneys, brokers, agents, contractors, inspectors, insurance professionals, accountants, property managers, architects, engineers, municipal contacts, vendors, and referral partners.
   - Tracks role, relationship status, source, ownership, last activity, next action, and deal associations.

## Canonical objects

The shared CRM layer recognizes:

- Contact
- Organization
- Relationship
- Activity
- Relationship Task / Next Action
- Deal Link
- External Record Link

Specialized operational objects remain in their authoritative plugins, including underwriting scenarios, offers, documents, funding commitments, buyer access grants, marketplace records, signatures, and automation rules.

## Required common fields

Relationship records should support, where applicable:

- record owner / responsible user
- status
- priority
- source
- relationship type
- relationship strength
- tags
- last activity
- next action
- next action date
- linked Deal IDs
- linked authoritative external record IDs
- created/updated timestamps
- created/updated user IDs
- audit events

## Authority boundaries

The CRM layer MUST NOT duplicate authoritative data owned elsewhere.

| Business object | Authority |
|---|---|
| Canonical Deal | Pipeline CRM |
| Intake submission / consent | Deal Intake |
| Underwriting | MAO Engine |
| Offer / proposal | Offer Generator |
| Document record | Document Library |
| PDF / signature workflow | PDF & Signature Engine |
| Capital criteria / funding commitment | Funding Tracker |
| Buyer profile / buyer criteria | Buyer Portal |
| Marketplace visibility / NDA / response | Deal Marketplace |
| Automation rule / execution | Automation Engine |
| Executive aggregation | Admin Command Center |

The shared CRM may link to and display authorized summaries from those systems, but the authoritative plugin persists the source-of-truth record.

## Operating rule

Every qualified opportunity or active relationship should have:

1. a responsible human or authorized agent;
2. a current status;
3. a next action;
4. a next-action date when applicable; and
5. a durable activity trail.

For active Deals, the canonical Pipeline Deal ID is the transaction reference used by underwriting, offers, funding, documents, buyers, automation, and reporting.

## First-deal operating views

### Acquisition Manager

- new leads
- contacts due today
- follow-ups overdue
- seller appointments
- deals awaiting underwriting
- offers awaiting response
- next actions by priority

### Founder / Managing Member

- qualified opportunities
- underwriting approvals
- offers requiring approval
- negotiations
- contracts / due diligence
- funding gaps
- closing readiness

### Capital / Investor Relations

- qualified capital relationships
- follow-ups due
- deals presented
- interest vs commitment
- Funding Tracker links

### Professional Network

- active professionals/vendors
- geographic/service coverage
- deal associations
- last contact / next contact

## Integration model

```text
Deal Intake
    |
    v
Pipeline CRM ---- Shared Contacts / Organizations / Relationships
    |                         |
    |                         +---- Buyer Portal / Marketplace
    |                         +---- Funding Tracker
    |                         +---- Property Stewardship
    |                         +---- Professional / Referral Network
    |
    +---- MAO Engine
    +---- Offer Generator
    +---- Document Library / PDF & Signature
    +---- Automation Engine
    +---- Admin Command Center
```

## Human-control boundary

CRM automation and agents may research, enrich, classify, prioritize, schedule, draft, monitor, and perform approved administrative actions. Final acquisition strategy, binding offers, contracts, capital commitments, funds movement, and closing authority remain human-controlled.
