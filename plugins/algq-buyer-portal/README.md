# Algonquian Buyer Portal

**Version:** 1.1.0  
**Status:** Production hardening release  
**Author:** Onegodian | Algonquian Real Estate Technology Division

Secure buyer-facing portal for controlled deal distribution, buyer registration, buyer-specific authorization, deal-scoped NDA acceptance, buyer interest, and protected package delivery.

## Shortcodes

- `[algq_buyer_registration]`
- `[algq_buyer_login]`
- `[algq_buyer_dashboard]`
- `[algq_buyer_deals]`

## Generated Pages

- `/buyers-register/`
- `/buyers-login/`
- `/buyer-dashboard/`
- `/buyer-deals/`

Generated WPBakery content uses:

```text
[vc_column_text]
[algq_buyer_dashboard]
[/vc_column_text]
```

## Authorization Model

The shared `algq_buyer` role receives granular buyer capabilities. A published deal is not visible merely because a user is logged in. Administrators must assign buyer user IDs to the deal metadata key:

```text
_algq_authorized_buyer_ids
```

The value must be stored as an array of WordPress user IDs.

## Protected Packages

Deal packages use a WordPress attachment ID stored at:

```text
_algq_package_attachment_id
```

The plugin validates buyer authorization and the current deal-scoped NDA before streaming the file through PHP. It does not redirect buyers to a public package URL.

## NDA Evidence

Each acceptance records:

- buyer user ID
- deal ID
- NDA version
- UTC acceptance timestamp
- hashed IP address
- hashed user agent
- immutable acceptance UUID

Set the active NDA version through the `algq_buyer_nda_version` option. Changing the version requires a new acceptance.

## Security Controls

- granular capabilities
- buyer-role enforcement
- buyer-specific deal authorization
- deal-scoped nonces
- deal-scoped NDA verification
- protected attachment delivery
- file-hash download logging
- sanitized registration and interest fields
- no public query of all published deals

## Required Validation Before Production

- activation and upgrade migration test
- role and capability reconciliation
- buyer registration and password-notification test
- assigned and unassigned buyer visibility test
- NDA version-change test
- protected download test against private storage
- interest submission authorization test
- multisite and PHP compatibility review, if applicable
