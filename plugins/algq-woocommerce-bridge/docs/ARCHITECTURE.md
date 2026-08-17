# Architecture

**WooCommerce owns:** products, customers, orders, refunds, taxes, subscriptions, and payment state.

**Algonquian WooCommerce Bridge owns:** derived Algonquian entitlement records and entitlement status.

The Bridge does not create a second order ledger. Entitlements are derived from confirmed WooCommerce events and are idempotent on `(user_id, order_id, order_item_id, access_key)`.

Integration events include `algq_wcb_access_granted`, `algq_entitlement_changed`, and `algq_audit_event`.
