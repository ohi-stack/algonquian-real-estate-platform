# Security Policy

## Supported Version

Security maintenance applies to the current `1.x` release line.

## Security Controls

- Direct file access is blocked.
- Product management requires the `manage_algq_digital_products` capability.
- Product metadata updates require a valid WordPress nonce.
- Inputs are sanitized before storage.
- Public output and URLs are escaped.
- REST responses are limited to published products visible to the current user.
- Protected attachment identifiers are excluded from public REST responses and product cards.
- The plugin does not process card data, store Stripe secrets, or implement its own payment transport.
- Catalog and page data are retained by default during uninstall.

## Responsible Reporting

Report suspected vulnerabilities privately to the Algonquian Real Estate Technology Division. Do not include customer records, credentials, protected file URLs, payment data, or exploit details in public issues.

## Deployment Requirements

Before production use:

1. Test on the supported WordPress and PHP versions.
2. Confirm that WooCommerce product links resolve only to intended products.
3. Confirm that customer/internal visibility behaves correctly for logged-out, customer, and administrator accounts.
4. Confirm that protected downloads are enforced by the entitlement/download controller and are not delivered directly from the Media Library URL.
5. Review generated pages before public launch.
