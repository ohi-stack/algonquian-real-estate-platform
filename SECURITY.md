# Security Policy

## Supported Releases

Only releases explicitly identified as supported in the repository release notes receive security fixes. Development, alpha, beta, and release-candidate builds must not be deployed as production systems without documented acceptance testing.

## Reporting a Vulnerability

Do not publish suspected vulnerabilities, credentials, private seller information, buyer information, lender information, signatures, transaction documents, or direct-download URLs in a public issue.

Report security concerns privately to the repository owner or the designated Algonquian Real Estate security contact. Include:

- Affected plugin and version.
- WordPress and PHP versions.
- Reproduction steps.
- Required role or authentication state.
- Expected and actual behavior.
- Potential data or operational impact.
- A minimal proof of concept that does not expose real personal or transaction data.

## Security Baseline

All plugins must implement, where applicable:

- Direct-access protection.
- Granular capability checks.
- Nonce validation for state-changing browser requests.
- REST permission callbacks.
- Server-side validation and sanitization.
- Context-appropriate output escaping.
- Prepared SQL statements.
- Private file authorization.
- MIME and size validation for uploads.
- Rate limiting and anti-spam controls for public forms.
- Secret masking and secure credential storage.
- Append-only audit events through ordinary interfaces.
- Data minimization and retention controls.
- Record-level authorization for external portals.
- Idempotency protection for webhooks and queued actions.

## Prohibited Data Practices

The platform must not intentionally place the following in ordinary logs:

- SMTP passwords or API secrets.
- Full authentication tokens.
- Full bank or financial account numbers.
- Signature images or authentication evidence beyond operational necessity.
- Complete confidential email bodies.
- Private document contents.

## Dependency Failures

A missing or incompatible dependency must disable the affected functionality safely and show an authorized administrative notice. It must not produce a fatal public-site error.

## Private Files

Private documents, property photographs, signature files, lender packages, buyer packages, and transaction records must not rely on obscured URLs as access control. Every preview and download must enforce authorization.

## Release Gate

Security testing is part of the mandatory WordPress installation-readiness process documented in `docs/wordpress-installation-readiness.md`. A version number alone is not evidence that a package is safe for production use.
