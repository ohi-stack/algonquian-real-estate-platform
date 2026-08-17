=== Algonquian PDF & Signature Engine ===
Contributors: onegodian
Tags: pdf, electronic signatures, documents, real estate, workflow
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 2.0.0
License: Proprietary

Generates protected transaction PDFs, controls document versions, and coordinates provider-neutral signature workflows.

== Description ==

Algonquian PDF & Signature Engine is the authoritative PDF and signature-workflow module for the Algonquian Real Estate platform. It includes protected storage, secure downloads, SHA-256 integrity verification, versioned document records, signature requests, signers, provider event reconciliation, append-only evidence, REST integration, and WPBakery-compatible guide pages.

The plugin does not provide legal advice or independently determine the enforceability of a document or signature.

== Installation ==

1. Upload the `algq-pdf-signature` directory to `/wp-content/plugins/`.
2. Activate the plugin.
3. Confirm the private storage health check.
4. Assign the granular capabilities through the Platform Plugin.
5. Configure and test any external signature provider adapter.

== Changelog ==

= 2.0.0 =
* Rebuilt the plugin around protected storage, immutable versions, granular access control, provider-neutral requests, verified webhooks, and append-only evidence.
