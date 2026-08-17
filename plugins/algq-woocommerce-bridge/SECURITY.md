# Security Policy

The WooCommerce Bridge never owns payment credentials and must not duplicate payment processing. WooCommerce and the configured payment gateway remain authoritative for payment state.

Controls include capability checks, WordPress nonces, sanitized product configuration, escaped output, HPOS-compatible WooCommerce APIs, idempotent entitlement writes, and conservative uninstall behavior.

Do not expose order, customer, or entitlement records through public endpoints without record-level authorization.
