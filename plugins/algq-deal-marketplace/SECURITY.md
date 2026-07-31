# Security Policy

## Supported version

Security maintenance applies to Algonquian Deal Marketplace 2.x.

## Security boundaries

- Marketplace provides controlled access; it does not make a public Media Library URL private.
- Confidential package files must be stored through the Platform private-file service, Document Library, or another implementation of `algq_dm_package_file_path`.
- The Platform Plugin owns shared authentication, Stripe, mail, audit, and file-service infrastructure.
- Marketplace does not store Stripe secrets, payment card data, full message bodies, raw IP addresses, or raw user-agent values in audit records.

## Required controls

- HTTPS across public, buyer, and administrative routes.
- Current WordPress and PHP security releases.
- Least-privilege capability assignment.
- Buyer Portal registration and identity review before premium access.
- Current NDA version and document hash.
- Private package storage and protected delivery.
- Backups and tested restoration.
- Audit-log retention appropriate to transaction and confidentiality needs.

## Vulnerability reporting

Do not publish suspected vulnerabilities or confidential transaction data in a public issue. Report privately to the authorized Algonquian Real Estate technology administrator with the affected version, reproduction steps, impact, and proposed mitigation.
