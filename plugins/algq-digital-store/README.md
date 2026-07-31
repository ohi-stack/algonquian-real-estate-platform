# Algonquian Digital Store

**Version:** 1.1.0  
**Author:** Onegodian | Algonquian Real Estate Technology Division

## Purpose

Algonquian Digital Store provides the public digital-product catalog, WooCommerce checkout bridge, authenticated Product Vault, and post-payment entitlement events for the Algonquian Real Estate platform.

## Authority boundaries

- **WooCommerce** is authoritative for products, prices, carts, orders, payment state, customer downloads, refunds, and order history.
- **Algonquian Platform Plugin** is authoritative for shared Stripe credentials, API transport, webhook verification, and platform-wide payment audit services when that integration is enabled.
- **Algonquian Digital Store** presents approved products and customer assets and emits idempotent entitlement events after a paid WooCommerce order.
- The plugin does not store card data, independently determine paid status, or trust browser-submitted prices.

## Shortcodes

- `[algq_digital_store]` — WooCommerce-backed catalog.
- `[algq_product_vault]` — authenticated downloads and paid-order history.
- `[algq_store_checkout]` — secure WooCommerce checkout bridge.

Optional catalog attributes:

```text
[algq_digital_store limit="12" category="plugins"]
```

## Generated pages

Activation creates pages only when they do not already exist and never overwrites administrator-edited content:

- `/store/`
- `/product-vault/`
- `/store/checkout/`

Generated content uses valid WPBakery syntax:

```text
[vc_column_text]
[algq_digital_store]
[/vc_column_text]
```

## Capabilities

- `manage_algq_digital_store`
- `view_algq_product_vault`

## Entitlement event

After a verified paid WooCommerce order, the plugin emits:

```php
do_action( 'algq_digital_store_entitlement_granted', $payload );
```

The event is marked on the order to prevent duplicate emission. WooCommerce remains the authoritative source for downloadable-file permissions.

## Deployment checks

1. Activate WooCommerce and configure server-side payment gateways.
2. Publish at least one product and verify visibility and price.
3. Complete a test-mode order.
4. Confirm the order reaches a paid state.
5. Confirm Product Vault access for the purchasing account.
6. Confirm downloadable permissions and expiration limits.
7. Confirm entitlement and audit events fire once.
8. Verify refund and revoked-download behavior.
9. Verify customer and administrator capability boundaries.
