=== Algonquian Deal Marketplace ===
Contributors: onegodian
Tags: real estate, marketplace, buyer portal, nda, offers, secure downloads
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 2.0.0
License: Proprietary

Controlled buyer marketplace for curated real estate opportunities, versioned NDA evidence, record-level access, secure package delivery, and buyer offers.

== Description ==

Algonquian Deal Marketplace is a production module of the Algonquian Real Estate Platform. Version 2.0.0 resolves shared buyer-role and Buyer Dashboard route conflicts, validates each deal action, and integrates with shared Platform audit, mail, private storage, automation, and Stripe-entitlement services.

== Installation ==

1. Upload the `algq-deal-marketplace` directory to `/wp-content/plugins/`.
2. Activate Algonquian Deal Marketplace.
3. Confirm the shared `algq_buyer` role has the Marketplace capabilities.
4. Configure a private package path integration.
5. Publish Marketplace Deals and test the full buyer workflow.

== Changelog ==

= 2.0.0 =
* Production access-control and data-model upgrade.
* Versioned NDA acceptance evidence.
* Controlled package download handler.
* Record-level offer authorization.
* Shared buyer capability migration.
* Buyer Dashboard collision removal.
* Stripe entitlement hooks and Platform service integration.
