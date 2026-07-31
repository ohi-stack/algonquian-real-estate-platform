=== Algonquian Real Estate Platform ===
Contributors: onegodian
Tags: real estate, crm, automation, audit, smtp, document security
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 2.0.0
License: Proprietary

Shared platform infrastructure for the Algonquian Real Estate plugin ecosystem.

== Description ==

Algonquian Real Estate Platform provides centralized plugin registration, capabilities, buyer-role reconciliation, audit logging, SMTP delivery, mail logging, private file storage, health monitoring, safe WPBakery page generation, and shared integration contracts.

The Platform Plugin does not own canonical deal, underwriting, offer, document, signature, funding, or automation records. Those records remain under their designated companion plugins.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/`.
2. Activate Algonquian Real Estate Platform before companion plugins.
3. Open Algonquian > Settings.
4. Configure non-secret SMTP values and provide the password through `ALGQ_SMTP_PASSWORD` or an environment secret.
5. Run the health check and confirm companion-plugin status.

== Changelog ==

= 2.0.0 =
* Rebuilt the release-candidate monolith as a shared production infrastructure core.
* Added registry, capabilities, audit, mail, private files, health monitoring, safe page generation, and conservative uninstall controls.
