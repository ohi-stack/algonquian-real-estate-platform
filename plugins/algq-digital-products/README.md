# Algonquian Digital Products

**Version:** 1.0.0  
**Author:** Onegodian | Algonquian Real Estate Technology Division  
**Status:** Production source candidate

## Purpose

Algonquian Digital Products is the authoritative WordPress catalog for digital assets developed or distributed by Algonquian Real Estate LLC. It replaces the former `0.1.0` shortcode scaffold with governed product records, metadata, visibility rules, category management, REST catalog access, WPBakery pages, and optional WooCommerce product linking.

## Authority Boundary

This plugin owns:

- Product catalog records
- Product type, version, SKU, visibility, and delivery classification
- Product categories
- Public product presentation
- WooCommerce product references
- Documentation links
- Protected attachment references that are not exposed publicly

This plugin does **not** own:

- Checkout or payment processing
- WooCommerce orders
- Stripe credentials or webhooks
- Customer entitlements
- License activation
- Protected file delivery
- Refunds, taxes, or subscription billing

Those functions belong to Digital Store, WooCommerce, the WooCommerce Bridge, the shared platform payment layer, or the designated entitlement service.

## Shortcodes

```text
[algq_digital_products]
[algq_digital_products category="templates" limit="12" columns="3"]
[algq_digital_product id="123"]
[algq_digital_product slug="seller-financing-offer-pack"]
```

WPBakery placement:

```text
[vc_column_text]
[algq_digital_products]
[/vc_column_text]
```

## Generated Pages

Activation creates pages only when they do not already exist:

- `/digital-products/`
- `/plugin/digital-products/`
- `/plugin/digital-products/start/`
- `/plugin/digital-products/docs/`

Administrator-edited pages are never overwritten during ordinary activation.

## REST API

Read-only public catalog endpoints:

```text
GET /wp-json/algq/v1/digital-products
GET /wp-json/algq/v1/digital-products/{id}
```

The API excludes protected attachment identifiers and internal products from unauthorized responses.

## Security

- State-changing actions require `manage_algq_digital_products`.
- Product metadata saves require nonce verification and capability checks.
- Public output is escaped.
- Internal products are not exposed in the public catalog.
- Protected attachment URLs are never emitted by this plugin.
- Uninstall preserves catalog records unless destructive cleanup is explicitly enabled.

## Validation Performed

- PHP syntax linting for all PHP files.
- JavaScript syntax validation.
- Static review of nonce, capability, sanitization, escaping, REST visibility, and generated-page behavior.

A WordPress/WooCommerce integration environment remains required for activation, REST, role, page-generation, theme, and checkout-link testing before production deployment.
