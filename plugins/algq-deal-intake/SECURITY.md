# Security Policy

## Supported version

Security fixes are maintained for the current `2.x` release line.

## Sensitive data

Deal Intake may hold seller contact information, property information, consent evidence, and intake notes. Access must be limited to authorized personnel. Logs must not contain message bodies, full documents, authentication tokens, financial account numbers, or other secrets.

## Required deployment controls

- HTTPS must be enabled.
- WordPress salts must be unique.
- Administrator accounts must use strong authentication and least privilege.
- The Platform Plugin must control shared mail, audit, and private-file services.
- Database backups and retention must be documented.
- Any future file-upload implementation must use a private storage controller. An ordinary public uploads URL is not an acceptable authorization boundary.
- Public submission endpoints should be protected by a production CAPTCHA or equivalent edge control in addition to the included honeypot and rate limit.
- Consent language and retention periods should be reviewed by qualified counsel for the deployed use case.

## Reporting

Report suspected vulnerabilities privately to the repository owner. Do not disclose seller or property data in a public issue.
