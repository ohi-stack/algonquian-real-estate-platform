# Seller Financing Proposal Workflow

Offer Generator 2.1.0 is the proposal owner for seller-financing transactions.

## Authority boundary

- Pipeline CRM owns the canonical deal.
- MAO Engine owns underwriting mathematics and approved financing analysis.
- Offer Generator owns seller-facing proposal/term-sheet/LOI/offer records and versions.
- Document Library owns controlled document records.
- PDF & Signature Engine owns final rendering/signature workflow.
- Funding Tracker owns actual financing/debt records after terms become operative.

## Workflow

1. Create or identify the canonical Pipeline CRM deal.
2. Build and approve a seller-financing scenario in MAO Engine.
3. Open **ARE Offers → Seller Financing**.
4. Enter the Deal ID and select Proposal, Term Sheet, LOI, or Offer.
5. Offer Generator imports the approved MAO economics and locks them.
6. Add proposed closing date, contingencies, servicing concepts, and other non-underwriting business terms.
7. Review the versioned proposal.
8. A user with `approve_algq_offers` approves the offer record when appropriate.
9. Generate the controlled document and hand off to Document Library / PDF & Signature.
10. Record finalized financing obligations in Funding Tracker and proceed through closing controls.

## Locked MAO economics

Offer Generator does not silently recalculate or override approved MAO economics. Imported fields include purchase price, down payment, seller principal, rate, amortization, balloon, payment, debt service, DSCR, cash flow, refinance analysis, and conventional financing comparison where available.

## Legal-document boundary

Offer Generator composes proposed business terms. Final promissory notes, mortgages/security instruments, deeds, title instruments, and other legally operative closing documents should be prepared or reviewed for the specific transaction by qualified professionals.
