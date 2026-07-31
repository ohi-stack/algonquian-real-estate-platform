# Security Policy — Algonquian Offer Generator

## Protected data

Offer records may contain seller identity, property addresses, pricing, financing terms, contingencies, deal references, and approval evidence. These records must not be exposed through public post queries, unauthenticated shortcodes, direct file URLs, or permissive REST endpoints.

## Required controls

- Granular capabilities for creation, editing, approval, document generation, sending, template management, and history access.
- Nonce validation on browser and AJAX state changes.
- Permission callbacks on every REST endpoint.
- Server-side validation and type-specific sanitization.
- Output escaping.
- Record-level authorization for individual offers.
- Document hashes and controlled handoff to Document Library and PDF & Signature Engine.
- No HTML file may be represented as a PDF.
- No private offer body, signature evidence, API secret, or SMTP credential may be written to ordinary logs.

## Reporting

Report suspected vulnerabilities privately to the repository owner. Do not include real seller, buyer, lender, property, banking, signature, or transaction data in a public issue.

## Production classification

Version 2.0.0 is a production candidate until installation, upgrade, capability, REST authorization, document, PDF, and end-to-end tests pass in a disposable WordPress environment.
