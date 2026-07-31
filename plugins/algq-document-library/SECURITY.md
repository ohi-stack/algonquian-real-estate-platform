# Security Policy

## Protected Information

The Document Library may contain entity records, lender materials, transaction documents, personal information, financial information, signatures, and other confidential business records. Treat every non-public file as restricted unless its authoritative record states otherwise.

## Security Controls

- Direct file access is blocked in the private storage directory using multiple server-specific controls.
- File names are randomized and sanitized.
- MIME type and extension are verified before storage.
- Upload size is limited and filterable.
- File integrity is recorded with SHA-256.
- Download requests require a valid nonce and a document-level authorization decision.
- Raw storage paths are not exposed in the public interface or REST API.
- Access requests are rate-limited and store privacy-preserving network metadata hashes.
- Material events are emitted to the platform audit service when available.
- Uninstall preserves operational records and files by default.

## Deployment Requirements

Private-directory blocking must be tested against the production server, reverse proxy, CDN, object storage, and backup configuration. A successful WordPress capability check does not compensate for a publicly reachable storage path.

## Reporting

Report suspected vulnerabilities privately to the authorized Algonquian Real Estate Technology Division administrator. Do not place document samples, private paths, credentials, personal information, or exploit details in public issues.
