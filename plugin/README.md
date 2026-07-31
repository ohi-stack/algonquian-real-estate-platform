# Algonquian Real Estate Platform Plugin

**Version:** 2.0.0  
**Status:** Production infrastructure core  
**Author:** Onegodian  
**Parent organization:** Algonquian Real Estate LLC

## Purpose

The Platform Plugin is the infrastructure authority for the Algonquian Real Estate plugin ecosystem. It provides shared services and contracts; it does not own canonical deal, underwriting, offer, document, signature, funding, or automation records.

## Core services

- Companion-plugin registry and compatibility status
- Shared roles and granular capabilities
- Activation-order-safe `algq_buyer` capability reconciliation
- Append-only structured audit logging
- Centralized WordPress SMTP transport and delivery logging
- Private file storage and tokenized download delivery
- Scheduled and on-demand platform health checks
- Idempotent WPBakery page generation that never overwrites existing pages
- Legacy shortcode bridges that defer to authoritative companion plugins
- REST health endpoint at `/wp-json/algq/v1/health`

## Requirements

- WordPress 6.8 or later
- PHP 8.2 or later

## Installation

1. Upload the plugin directory to `wp-content/plugins/`.
2. Activate **Algonquian Real Estate Platform** before companion plugins.
3. Open **Algonquian → Settings**.
4. Configure mail transport without storing SMTP passwords in WordPress options.
5. Run the Platform Health Check.
6. Confirm the registry recognizes every installed companion plugin.

## SMTP secrets

Use constants or environment secrets:

```php
define( 'ALGQ_SMTP_HOST', 'smtp.example.com' );
define( 'ALGQ_SMTP_PORT', 587 );
define( 'ALGQ_SMTP_USERNAME', 'notifications@example.com' );
define( 'ALGQ_SMTP_PASSWORD', 'application-specific-secret' );
define( 'ALGQ_SMTP_ENCRYPTION', 'tls' );
define( 'ALGQ_SMTP_FROM_EMAIL', 'notifications@example.com' );
define( 'ALGQ_SMTP_FROM_NAME', 'Algonquian Real Estate' );
```

## Companion registration

```php
algq_register_plugin(
    'algq-example',
    array(
        'name'                 => 'Algonquian Example',
        'file'                 => 'algq-example/algq-example.php',
        'min_platform_version' => '2.0.0',
        'required_plugins'     => array(),
    )
);
```

## Audit events

```php
algq_log_event(
    'example.record_updated',
    array( 'record_id' => 123 ),
    array(
        'plugin'      => 'algq-example',
        'object_type' => 'example',
        'object_id'   => '123',
    )
);
```

Sensitive keys such as passwords, secrets, tokens, signatures, authorization data, API keys, and account numbers are redacted before storage.

## Page generation

The plugin creates only missing Platform overview, Plugin Library, Getting Started, and Documentation pages. Existing pages are never overwritten. WPBakery content uses:

```text
[vc_column_text]
[algq_platform_overview]
[/vc_column_text]
```

## Data ownership

The Platform Plugin owns shared infrastructure only. Companion plugins remain authoritative for their designated operational records.
