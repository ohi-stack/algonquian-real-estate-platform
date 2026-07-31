# Algonquian Offer Generator

**Version:** 2.0.0  
**Author:** Onegodian | Algonquian Real Estate  
**Status:** Production candidate; WordPress acceptance testing required

## Authority

The Offer Generator is the authoritative owner of offer records, approved offer terms, templates, merge-field values, offer versions, review and approval evidence, and delivery status. It does not own canonical deals, underwriting scenarios, document-library records, PDF files, or signature requests.

## Version 2.0 capabilities

- Real offer creation from the builder shortcode.
- Deal linkage and normalized offer metadata.
- Cash, seller-financing, subject-to, letter-of-intent, and purchase-proposal strategies.
- Granular WordPress capabilities and protected history views.
- Immutable version snapshots for material edits and approvals.
- Offer approval workflow.
- Consistent document HTML, SHA-256 document hashes, and Document Library handoff.
- Honest PDF delegation to the PDF & Signature Engine; HTML is never mislabeled as PDF.
- Protected `algq/v1` REST endpoints for list, create, read, update, approve, and document actions.
- Idempotent page generation that does not overwrite existing administrator content.
- Conservative uninstall behavior that preserves offer and audit records unless explicit deletion is enabled.

## Shortcodes

- `[algq_offer_generator]`
- `[algq_offer_builder]`
- `[algq_offer_history]`

All operational shortcodes require authentication and an applicable Offer Generator capability.

## Generated pages

- `/offer-generator/`
- `/generate-offer/`
- `/offer-history/`

WPBakery content uses `[vc_column_text]...[/vc_column_text]` and never the malformed `</vc_column_text>` closing form.

## Capabilities

- `manage_algq_offers`
- `create_algq_offers`
- `approve_algq_offers`
- `send_algq_offers`
- `generate_algq_offer_documents`
- `view_algq_offer_history`
- `manage_algq_offer_templates`
- WordPress mapped offer-record capabilities

## Integrations

- Algonquian Real Estate Platform Plugin
- Pipeline CRM
- MAO Engine
- Document Library
- PDF & Signature Engine
- Automation Engine
- Admin Command Center

Missing integrations place the plugin in limited mode and do not generate a public fatal error.

## REST API

Base namespace: `algq/v1`

- `GET /offers`
- `POST /offers`
- `GET /offers/{id}`
- `PATCH /offers/{id}`
- `POST /offers/{id}/approve`
- `POST /offers/{id}/document`

Every endpoint has a permission callback and returns only authorized offer data.

## Production acceptance

Before release, test installation, activation, upgrade from 1.0.0, page preservation, role capabilities, builder submission, version increments, approval authorization, REST authorization, document hashes, Document Library handoff, PDF delegation, deactivation, and conservative uninstall in a disposable WordPress environment.
