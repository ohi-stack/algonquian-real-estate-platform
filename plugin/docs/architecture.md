# Platform Plugin 2.0 Architecture

## Authority

The Platform Plugin owns shared bootstrap, registry, dependency reporting, capabilities, mail transport, audit logging, private file services, security utilities, page generation, health monitoring, and shared integration contracts.

It does not own canonical deals, pipeline stages, underwriting scenarios, offers, document versions, signature requests, funding commitments, or automation rules.

## Initialization order

1. Requirement validation
2. Capabilities
3. Registry
4. Audit service
5. Mail Gateway
6. Private file service
7. Health monitor
8. Administrative navigation and actions
9. Platform and compatibility shortcodes

## Data tables

### `wp_algq_audit_log`

Append-only material event log.

### `wp_algq_mail_log`

Delivery metadata for successful and failed `wp_mail()` calls. Message bodies are not stored.

## Shared contracts

- `algq_register_plugin( $slug, $definition )`
- `algq_log_event( $event_name, $payload, $context )`
- `ALGQ_Private_Files::store()`
- `ALGQ_Private_Files::create_download_url()`
- `GET /wp-json/algq/v1/health`

## Compatibility bridges

Legacy Platform Plugin shortcodes are registered only when the authoritative companion plugin has not registered them. This prevents duplicate handlers while providing an administrator-visible dependency notice.

## Page-generation rule

Generated pages are created only when missing. The generator stores page identifiers and metadata but never replaces content on an existing page. Nested plugin routes are created through explicit parent relationships.
