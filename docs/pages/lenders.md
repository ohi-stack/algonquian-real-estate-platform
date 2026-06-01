# /lenders Production Specification

## Purpose

The /lenders page is the capital-source and financing relationship hub for Algonquian Real Estate. It should support lender records, private money contacts, JV capital sources, loan terms, funding conversations, document requests, and deal-level capital matching.

## Recommended Page Title

Lenders

## Recommended URL

/lenders

## Recommended Shortcode

```text
[algq_lenders]
```

If the shortcode is not yet implemented, the page should temporarily render an internal placeholder with links to /funding, /documents/lender, and /deals.

## WPBakery Implementation

Use:

```text
[vc_column_text]
[algq_lenders]
[/vc_column_text]
```

Never use:

```html
</vc_column_text>
```

## Access Rule

The /lenders page must be restricted to authorized internal users. The default production capability should be `manage_options` until custom finance, acquisition, and capital roles are implemented.

## Required Lender Fields

- Lender ID
- Lender or capital source name
- Contact name
- Email
- Phone
- Company
- Capital type
- Target loan amount
- Loan-to-value preference
- Interest rate range
- Points or fees
- Term length
- Recourse preference
- Documentation requested
- Active deals linked
- Commitment amount
- Status
- Notes
- Created date
- Updated date

## Capital Source Types

1. Bank lender
2. Credit union
3. Private lender
4. Hard money lender
5. Joint venture partner
6. Seller-financing source
7. Internal capital source
8. Other capital source

## Related Pages

- /lenders/new
- /lenders/{id}
- /funding
- /funding/{deal-id}
- /documents/lender
- /deals/{id}

## Lender Workflow

1. Internal user creates or imports lender record.
2. Lender is categorized by capital type.
3. Lender requirements and terms are recorded.
4. Lender is matched to one or more deal records.
5. Financing request package is prepared.
6. Lender documents are requested or generated.
7. Funding status is tracked from /funding and /deals.
8. Capital relationship history is preserved for future transactions.

## Required Lender Document Categories

The /lenders workflow should connect to the institutional document library and support lender-facing packages including:

- Financing Request Memorandum
- Loan Request Summary Sheet
- Underwriting Standards Overview
- Acquisition Criteria Sheet
- Source & Use of Funds Template
- Rent Roll Template
- T12 Financial Summary Template
- Schedule of Real Estate Owned, if applicable
- Personal Financial Statement, if applicable
- Global Cash Flow Worksheet, if applicable

## Production Requirements

- Page must be generated on plugin activation if it does not already exist.
- Page must not duplicate if the slug already exists.
- Page must use valid shortcode syntax.
- Lender inputs must be sanitized before storage.
- Lender data must be escaped before output.
- Internal lender-management actions must require capability checks and nonces.
- Lender records must support linking to deal records and funding records.
- Funding commitments and status changes should be logged.
- Lender document package access should be permission-controlled.
- The page should support future CSV export, PDF lender summaries, lender scoring, document package generation, and funding dashboard synchronization.
