# Changelog

## 1.0.0 — 2026-07-31

### Added

- Production plugin bootstrap and version constants.
- Authoritative `algq_digital_product` content type.
- Hierarchical product categories.
- Product type, version, SKU, visibility, delivery mode, pricing label, WooCommerce link, documentation link, protected attachment reference, and CTA metadata.
- Capability-based administration and product management.
- Catalog and single-product shortcodes.
- Responsive public catalog design.
- Branded administration overview and product metadata interface.
- Read-only public REST catalog endpoints.
- WooCommerce price and product-link integration when WooCommerce is available.
- Idempotent WPBakery page generation.
- Platform registration and health-check callback.
- Structured audit integration hooks.
- Conservative uninstall behavior.

### Changed

- Replaced the former `0.1.0` scaffold that displayed only a placeholder message.
- Defined a clear authority boundary between catalog management, checkout, payment, entitlement, and protected delivery.
