# Algonquian Buyer Portal

**Version:** 1.2.0  
**Status:** Production buyer-access release  
**Author:** Onegodian | Algonquian Real Estate Technology Division

Secure buyer-facing portal for controlled deal distribution, buyer registration, buyer-specific authorization, Marketplace access, deal-scoped NDA acceptance, buyer interest, and protected package delivery.

## Buyer Access Flow

The intended production flow is:

```text
/investors/
    ↓
Buyer Registration
    ↓
WordPress account creation
    ↓
Buyer credential setup notice
    ↓
/buyers-login/
    ↓
Authenticated algq_buyer account
    ↓
/marketplace/
    ↓
Registered-tier deals or authorized private/premium deals
```

The shared `algq_buyer` role is reconciled at runtime so Buyer Portal and Deal Marketplace base capabilities do not depend on plugin activation order.

## Shortcodes

- `[algq_investors_page]`
- `[algq_buyer_registration]`
- `[algq_buyer_login]`
- `[algq_buyer_dashboard]`
- `[algq_buyer_deals]`

## Managed / Generated Pages

- `/investors/` — production Investors & Capital page
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

## Marketplace Authorization Model

A registered `algq_buyer` account receives base Marketplace capabilities:

- `view_algq_marketplace`
- `view_algq_marketplace_deals`
- `accept_algq_marketplace_nda`
- `submit_algq_marketplace_offer`
- `download_algq_marketplace_packages`

This grants access only to opportunities that the Deal Marketplace itself permits.

For Marketplace records:

- `registered` tier deals may be visible to authenticated buyers with base access.
- deals with explicit allowed-buyer restrictions remain restricted.
- `private` and `premium` tier deals require an active Marketplace access grant.
- expired deals remain unavailable except to authorized administrators.
- package download and offer actions remain subject to NDA and record-level checks.

Buyer Portal-owned deal records continue to use buyer-specific authorization. A Buyer Portal deal is not visible merely because a user is logged in. Administrators must assign buyer user IDs to the deal metadata key:

```text
_algq_authorized_buyer_ids
```

The value must be stored as an array of WordPress user IDs.

## Protected Packages

Buyer Portal deal packages use a WordPress attachment ID stored at:

```text
_algq_package_attachment_id
```

The plugin validates buyer authorization and the current deal-scoped NDA before streaming the file through PHP. It does not redirect buyers to a public package URL.

## NDA Evidence

Each Buyer Portal NDA acceptance records:

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
- runtime shared-role reconciliation
- buyer-specific deal authorization
- Marketplace record-level authorization
- deal-scoped nonces
- deal-scoped NDA verification
- protected attachment delivery
- file-hash download logging
- sanitized registration and interest fields
- no public query of all Buyer Portal deal records

## Required Validation Before Production Deployment

- activate or update Buyer Portal 1.2.0 and confirm `/investors/` is replaced
- buyer registration and WordPress account-creation test
- buyer credential-notification and password-setup test
- Buyer Login test
- login redirect to `/marketplace/` test
- registered-tier Marketplace visibility test
- private/premium denial without an access grant
- private/premium visibility after an access grant
- NDA acceptance and version-change test
- protected download test against private storage
- offer and interest authorization tests
- multisite and PHP compatibility review, if applicable
