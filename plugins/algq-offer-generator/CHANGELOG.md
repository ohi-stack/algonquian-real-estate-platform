# Changelog

## 2.1.0 — Seller Financing Proposal Upgrade

### Added

- Seller Financing Proposal, Term Sheet, Letter of Intent, and Offer document types.
- Dedicated `ARE Offers → Seller Financing` proposal workspace.
- Approved-MAO import through the `algq_offer_generator_deal_payload` platform contract.
- Locked underwriting references including MAO record ID, UUID, formula version, and approval timestamp.
- Seller-financing principal, balloon balance, annual debt service, total debt service, DSCR, cash flow, refinance capacity/gap, and conventional-payment comparison metadata.
- Structured seller-facing transaction summary, payment, maturity, servicing, escrow, security-document, and professional-review language.
- `POST /algq/v1/offers/seller-financing/from-underwriting` REST workflow.
- `algq_seller_financing_proposal_created` and `algq_offer_approved` integration events.
- Latest proposal type, strategy, and status summary for Pipeline CRM payloads.

### Changed

- Offer Generator is explicitly the proposal/offer authority while MAO Engine remains the analytical authority.
- Seller-financing economics imported from approved MAO underwriting are locked against silent edits in Offer Generator.
- Seller-financing offers cannot reach approved status without an approved linked MAO underwriting record.
- Document rendering now identifies proposal type and includes the modeled seller-financing terms without representing the output as a promissory note or mortgage.
- Plugin version and database/release version advanced to 2.1.0.

### Fixed

- Corrected Pipeline CRM linkage to read the canonical `_algq_offer_deal_id` metadata key written by Offer Generator instead of the mismatched legacy `_algq_deal_id` key.
- Pipeline latest-offer metadata now reflects Offer Generator workflow status rather than relying only on WordPress post status.

### Validation required

- WordPress activation and upgrade testing from 2.0.0.
- Compatibility testing with MAO Engine 2.1.0 seller-financing payloads.
- Proposal, term-sheet, LOI, and offer rendering fixtures.
- Locked-underwriting mutation tests.
- Approval, capability, REST, Document Library, PDF/Signature, Pipeline CRM, Automation, and audit tests.
- PHP syntax/static validation.

## 2.0.0 — Production Candidate

### Added

- Central offer service with validation, normalized metadata, offer numbering, creation, update, approval, and version snapshots.
- Five supported offer strategies.
- Granular capabilities and repaired role upgrades.
- Protected dashboard, builder, and history shortcodes.
- Functional builder submission and draft creation.
- `algq/v1` REST endpoints for offer workflow operations.
- Document metadata, SHA-256 hashes, and Document Library integration hook.
- PDF & Signature Engine delegation with explicit failure when no actual PDF provider responds.
- Idempotent generated-page metadata and default offer templates.
- Conservative uninstall controls.

### Changed

- Replaced generic `edit_posts` authorization with Offer Generator capabilities.
- Replaced hard-coded front-end URLs with resolved WordPress page permalinks.
- Standardized purchase-price metadata while preserving the legacy `_algq_offer_price` key.
- Updated plugin metadata, minimum WordPress and PHP versions, version display, and documentation.
- Moved REST routes from `algq-offers/v1` to the platform-standard `algq/v1` namespace.

### Fixed

- Builder forms now create persistent offer records.
- Offer History no longer exposes records to unauthenticated visitors.
- Offer document rendering reads the same metadata keys written by the builder.
- The former “PDF” response no longer downloads HTML with a misleading file label.
- Existing Offer Manager roles receive newly required capabilities during upgrade.

## 1.0.0

- Added initial plugin bootstrap, post types, shortcodes, styling, and integration scaffolds.
