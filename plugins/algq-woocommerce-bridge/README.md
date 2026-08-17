# Algonquian WooCommerce Bridge

**Version:** 2.0.0  
**Division:** Algonquian Real Estate Technology Division

The Bridge converts confirmed WooCommerce commerce events into controlled Algonquian platform entitlements. WooCommerce remains authoritative for products, orders, refunds, subscriptions, taxes, and payment state.

## Production 2.0 controls

- Idempotent entitlement records with stable UUIDs and a unique order-item/access identity.
- HPOS compatibility declaration.
- Grant on payment, processing, or completion.
- Revoke on cancellation, failed orders, refunds, and subscription cancellation, expiration, or on-hold status.
- Partial refund item revocation when WooCommerce provides the refunded item identity.
- Optional access expiration by product.
- Product-level entitlement enablement and access keys.
- Legacy `algq_wcb_access_log` migration.
- Diagnostics for WooCommerce, HPOS, subscriptions, schema, and entitlement table status.
- Granular capabilities and nonce-protected administration.
- Valid WPBakery generated page syntax.
- Conservative uninstall behavior.

## Shortcodes

- `[algq_commerce_access]`
- `[algq_purchased_products]`
- `[algq_buyer_entitlements]`

## Production acceptance

Canonical source is not live-production certified until activation, migration, HPOS, refund, subscription, permissions, and end-to-end checkout/entitlement tests pass in the target WordPress/WooCommerce environment.
