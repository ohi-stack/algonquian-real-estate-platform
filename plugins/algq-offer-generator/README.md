# Algonquian Offer Generator

**Version:** 2.1.0  
**Author:** Onegodian | Algonquian Real Estate Technology Division  
**Status:** Production candidate; WordPress acceptance testing required

## Authority

The Offer Generator is the authoritative owner of proposal and offer records, seller-facing business terms, templates, merge-field values, versions, review and approval evidence, document composition, and offer workflow status.

It does **not** own:

- canonical deal records — Pipeline CRM owns them;
- underwriting calculations — MAO Engine owns them;
- funding/debt ledgers — Funding Tracker owns them;
- controlled document-library records — Document Library owns them;
- PDF files/signature requests — PDF & Signature Engine owns execution workflows.

## Version 2.1 seller-financing proposal system

Seller financing is now a first-class proposal workflow rather than only a strategy label.

Offer Generator supports four seller-facing document types:

- Seller Financing Proposal
- Seller Financing Term Sheet
- Letter of Intent — Seller Financing
- Seller Financing Offer

The preferred workflow is:

`Pipeline CRM deal → approved MAO seller-financing scenario → Offer Generator proposal → human review/approval → Document Library / PDF & Signature → Funding Tracker / closing`

### Approved-underwriting import

The Seller Financing workspace accepts a canonical Pipeline CRM Deal ID and retrieves the latest **approved** MAO underwriting payload through the documented `algq_offer_generator_deal_payload` contract.

The following economics are imported and locked to the approved underwriting record:

- purchase price
- down payment
- seller-financed principal
- interest rate
- amortization term
- balloon term
- monthly payment
- modeled balloon balance
- annual debt service
- total debt service
- DSCR
- cash flow
- refinance capacity and refinance gap
- conventional-financing payment comparison
- MAO underwriting ID, UUID, formula version, and approval timestamp

Offer Generator does not recalculate those values. Narrative business terms, contingencies, proposed closing date, servicing language, escrow concepts, and other seller-facing provisions may be added without silently changing approved MAO economics.

A seller-financing record cannot be moved to Offer Generator's approved state unless it is linked to an approved MAO underwriting scenario.

## Proposal language controls

The seller-financing document composer provides structured sections for:

- transaction summary
- payment terms
- maturity / balloon terms
- third-party servicing concept
- tax and insurance escrow concept
- security-document concept
- transaction-specific professional-review disclosure

The generated proposal is a business-terms document. It does not represent that Offer Generator itself creates the final promissory note, mortgage, deed, legal opinion, tax advice, or closing instrument.

## General capabilities

- Cash, seller-financing, subject-to, LOI, and purchase-proposal strategies.
- Granular WordPress capabilities and protected history views.
- Version snapshots for material edits and approvals.
- Human approval workflow.
- Consistent document HTML and SHA-256 document hashes.
- Document Library handoff.
- PDF & Signature Engine delegation.
- Protected `algq/v1` REST endpoints.
- Pipeline CRM latest-offer summary integration.
- Automation hooks and audit events.
- Idempotent page generation and conservative uninstall behavior.

## Seller Financing admin workspace

WordPress Admin:

`ARE Offers → Seller Financing`

The workspace requires an approved MAO seller-financing scenario and creates a draft proposal record for review.

## REST API

Base namespace: `algq/v1`

- `GET /offers`
- `POST /offers`
- `POST /offers/seller-financing/from-underwriting`
- `GET /offers/{id}`
- `PATCH /offers/{id}`
- `POST /offers/{id}/approve`
- `POST /offers/{id}/document`

Example seller-financing request body:

```json
{
  "deal_id": 123,
  "proposal_type": "term_sheet",
  "closing_date": "2026-09-30",
  "contingencies": "Subject to satisfactory title, inspection, insurance, and attorney review.",
  "terms": "Payments to be administered through an agreed third-party servicing arrangement."
}
```

## Shortcodes

- `[algq_offer_generator]`
- `[algq_offer_builder]`
- `[algq_offer_history]`

All operational shortcodes require authentication and an applicable Offer Generator capability.

## Generated pages

- `/offer-generator/`
- `/generate-offer/`
- `/offer-history/`

WPBakery content must use:

```text
[vc_column_text]
...
[/vc_column_text]
```

## Capabilities

- `manage_algq_offers`
- `create_algq_offers`
- `approve_algq_offers`
- `send_algq_offers`
- `generate_algq_offer_documents`
- `view_algq_offer_history`
- `manage_algq_offer_templates`
- WordPress mapped offer-record capabilities

## Production acceptance

Before promotion from draft:

- test upgrade from Offer Generator 2.0.0;
- test against MAO Engine 2.1.0 seller-financing output;
- verify approved-underwriting-only imports;
- verify locked economics cannot be silently changed in Offer Generator;
- verify proposal, term-sheet, LOI, and offer rendering;
- verify Pipeline CRM deal linkage and latest-offer metadata;
- verify approval authorization and version snapshots;
- verify Document Library and PDF & Signature handoffs;
- exercise REST permissions and error states;
- run PHP syntax/static checks and WordPress activation tests;
- verify conservative uninstall behavior.
