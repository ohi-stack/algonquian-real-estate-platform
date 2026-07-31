# Security Policy

## Supported version

Security maintenance applies to the current `2.x` release line.

## Controls

- Direct file access is blocked.
- Public users may calculate but cannot persist underwriting.
- Save and approval actions require separate granular capabilities and WordPress nonces.
- Every REST route has an explicit permission callback.
- Public calculation is rate limited and does not return saved records.
- REST inputs are validated and constrained to non-negative numeric values and approved strategies.
- Dynamic SQL uses prepared statements; static table-name queries use plugin-controlled identifiers.
- Saved scenarios retain formula, assumption, input, result, creator, and approval evidence.
- Existing records are preserved on deactivation and ordinary uninstall.
- Only approved scenarios are supplied to offer-generation workflows.
- The plugin does not store credentials, payment data, signatures, or full seller records.

## Reporting

Report suspected vulnerabilities privately to the Algonquian Real Estate Technology Division. Do not include live seller, buyer, lender, property, or transaction data in a report.

## Deployment gate

Before production deployment, verify capability isolation, nonce enforcement, REST authorization, rate limiting, database migration, approval immutability, audit events, output escaping, and cross-plugin record-level authorization.
