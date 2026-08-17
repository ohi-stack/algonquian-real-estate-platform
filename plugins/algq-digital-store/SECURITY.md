# Security Policy

## Supported version

Security fixes are applied to the current 1.1.x release line.

## Trust boundaries

Algonquian Digital Store does not accept a browser-submitted price or payment-success flag as authoritative. Product pricing, cart totals, order state, download permissions, refund state, and customer ownership are resolved through WooCommerce server-side APIs.

The plugin does not store:

- full card numbers
- card verification values
- Stripe secret keys
- raw payment-method credentials
- unverified payment-success callbacks

Shared Stripe credentials and webhook verification belong to the Algonquian Platform Plugin. Gateway-specific payment processing belongs to WooCommerce or the approved shared integration.

## Access control

- Store administration requires `manage_algq_digital_store`.
- Product Vault access requires authentication and `view_algq_product_vault` or administrative authority.
- WooCommerce creates authorized, customer-specific download URLs.
- Admin output and front-end dynamic values are escaped before rendering.

## Reporting a vulnerability

Do not publish a suspected vulnerability in a public issue. Report it privately to the repository owner with the affected version, reproduction steps, impact, and any proposed remediation.
