# Security Policy

## Scope

This policy applies to the Algonquian MAO Engine WordPress plugin.

## Security Controls

The plugin must maintain the following controls:

- No direct file access.
- Capability checks on all admin pages and privileged actions.
- Nonce validation on all form submissions.
- Sanitized input for all request, shortcode, option, and REST data.
- Escaped output for all admin and public rendering.
- Prepared SQL statements for dynamic database queries.
- REST permission callbacks on every route.
- No plaintext storage of payment credentials, API secrets, or private keys.
- Data preservation by default on deactivation and uninstall.

## Operational Hardening

Before production deployment:

1. Install on staging first.
2. Activate with `WP_DEBUG=true`.
3. Confirm no PHP notices or warnings.
4. Submit a test underwriting calculation.
5. Confirm the underwriting record saves.
6. Confirm auto-generated pages load.
7. Confirm the admin dashboard loads.
8. Confirm only authorized users can access admin screens.

## Reporting Issues

Security issues should be reviewed internally by Algonquian Real Estate before public disclosure or deployment changes.
