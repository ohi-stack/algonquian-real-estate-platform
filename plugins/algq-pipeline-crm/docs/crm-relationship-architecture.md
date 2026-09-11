# Pipeline CRM Relationship Layer

## Scope

Pipeline CRM is both:

1. the authoritative system for canonical Deal records; and
2. the shared CRM relationship engine for contacts and organizations used across ARE operations.

The relationship layer does not take authority away from specialized plugins. It gives the platform one reusable identity, relationship, activity, task, and linking model so that the same seller, buyer, lender, attorney, contractor, or referral source does not need to be recreated independently in every module.

## Relationship types

Supported relationship classifications include:

- `seller`
- `property_owner`
- `buyer`
- `capital`
- `lender`
- `equity_partner`
- `joint_venture_partner`
- `professional`
- `vendor`
- `referral_source`
- `stewardship_client`
- `other`

Plugins may register additional relationship types through documented filters without changing record authority.

## Contact record

A contact stores shared identity and CRM execution fields only:

- UUID
- name
- email
- phone
- preferred contact method
- responsible WordPress user
- status
- priority
- source
- relationship strength
- tags
- last activity timestamp
- next action
- next-action timestamp
- source-system identity for idempotent imports
- created/updated timestamps and users

Buyer acquisition criteria, capital terms, funding commitments, NDA records, document permissions, and other specialized information remain in the authoritative companion plugin.

## Organization record

Organizations support companies, ownership entities, lenders, vendors, law firms, brokerages, contractor companies, investor entities, and similar relationships.

Shared fields include identity, organization type, contact information, responsible user, source, tags, last activity, and next action.

## Relationship/link record

A relationship connects a contact and/or organization to:

- a relationship type;
- a canonical Pipeline Deal;
- an authoritative external plugin record; or
- another contextual source identity.

Examples:

- Contact 42 -> `seller` -> Deal ARE-2026-000021
- Organization 11 -> `capital` -> Funding Tracker capital-source record 8
- Contact 77 -> `buyer` -> Buyer Portal profile 214
- Organization 19 -> `professional` -> Deal ARE-2026-000021

The link does not copy the authoritative external record.

## Activity

Relationship activities include calls, emails, meetings, notes, appointments, outreach attempts, referrals, and system events. Deal-specific lifecycle events continue to use the existing canonical Deal activity table.

## Relationship tasks

Relationship tasks are for follow-up that is not yet an active Deal task. Once work becomes transaction-specific, the canonical Deal task should be used.

## Required operating rule

An active qualified relationship should not be left without ownership and a next action unless it is intentionally placed into a closed, inactive, do-not-contact, or archived state.

## Cross-plugin boundary

- Deal Intake may create or link seller/property-owner contacts during accepted intake.
- Buyer Portal may link buyer profiles to shared CRM contacts.
- Funding Tracker may link capital sources to contacts/organizations.
- Property Stewardship may link owner-authorized clients.
- Deal Marketplace reads buyer/deal relationships but retains marketplace access authority.
- Automation Engine may schedule approved CRM follow-ups.
- Command Center may aggregate CRM KPIs without persisting competing records.

## Data retention

Relationship data follows the same conservative retention posture as Pipeline CRM. Deactivation does not delete records. Full uninstall cleanup remains explicit and administrator-controlled.
