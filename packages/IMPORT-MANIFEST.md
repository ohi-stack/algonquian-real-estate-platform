# Algonquian Real Estate Plugin Package Import Manifest

## Import Date
2026-06-04

## Repository
`ohi-stack/algonquian-real-estate-platform`

## Source
Uploaded package artifacts from the active project workspace.

## Package Artifacts Available

The following package ZIPs were present and available for import review:

1. `Algonquian-Real-Estate-Platform-v1.0.0.zip`
2. `algonquian-real-estate-plugin-suite.zip`
3. `algonquian-real-estate-plugin-suite-updated.zip`
4. `algq-automation-engine-1.0.0-production.zip`
5. `algq-buyer-portal-1.0.0.zip`
6. `algq-deal-intake-1.0.2-rc.2.zip`
7. `algq-deal-marketplace-1.0.0.zip`
8. `algq-digital-store-1.0.0.zip`
9. `algq-document-library-1.0.0.zip`
10. `algq-pdf-signature-1.0.0.zip`
11. `algq-pipeline-crm-1.0.0-production.zip`
12. `algq-woocommerce-bridge-1.0.0-rc3-dashboard-branding.zip`

## Note on Count
Twelve ZIP artifacts were available when including the platform package and plugin-suite bundle ZIPs. Fewer than twelve standalone individual plugin ZIPs were present.

## Installed Platform Modules Reported on Site as of 2026-06-02

- Algonquian Admin Command Center
- Algonquian Automation Engine
- Algonquian Buyer Portal
- Algonquian Deal Intake
- Algonquian Deal Marketplace
- Algonquian Digital Products
- Algonquian Digital Store
- Algonquian Document Library
- Algonquian Funding Tracker
- Algonquian MAO Engine
- Algonquian Offer Generator
- Algonquian PDF & Signature Engine
- Algonquian Pipeline CRM
- Algonquian Real Estate Platform Plugin
- ALGQ WooCommerce Bridge

## Production-Hardening Status

These packages should be treated as release-candidate or production-hardening artifacts, not final audited production releases, until each plugin passes the universal release gate:

- Plugin bootstrap
- Activation hook
- Automatic page generation
- Shortcodes
- Admin menu
- Capabilities
- Nonces
- Input sanitization
- Output escaping
- README
- Documentation
- Branding assets
- Changelog
- Uninstall cleanup

## Handling Rule

Source ZIPs should be retained as package artifacts. Extracted plugin source should remain under `/plugins/{plugin-slug}` for review, hardening, and release packaging.
