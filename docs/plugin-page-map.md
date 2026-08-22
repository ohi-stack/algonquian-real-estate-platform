# Plugin Page Map

## Master Plugin Library

URL:

/plugins

Each plugin card should include:

- Logo
- Plugin name
- Version
- By Algonquian Real Estate, LLC
- View Details
- Getting Started
- Documentation
- UI mockup image

Developer URL for every plugin:

- https://algonquianrealestate.com/technology/

The WordPress `Plugin URI` for each plugin must point to that plugin's own public overview page rather than the homepage or a generic plugin-suite page.

## Canonical Public Plugin Overview Routes

- Algonquian Real Estate Platform: `/algonquian-real-estate-platform/`
- Algonquian Pipeline CRM: `/algonquian-pipeline-crm/`
- Algonquian Deal Intake: `/algonquian-deal-intake/`
- Algonquian MAO Engine: `/algonquian-mao-engine/`
- Algonquian Document Library: `/algonquian-document-library/`
- Algonquian PDF & Signature Engine: `/algonquian-pdf-signature-engine/`
- Algonquian Offer Generator: `/algonquian-offer-generator/`
- Algonquian Automation Engine: `/algonquian-automation-engine/`
- Algonquian Admin Command Center: `/algonquian-admin-command-center/`
- Algonquian Buyer Portal: `/algonquian-buyer-portal/`
- Algonquian Deal Marketplace: `/algonquian-deal-marketplace/`
- Algonquian Funding Tracker: `/algonquian-funding-tracker/`
- Algonquian Digital Products: `/algonquian-digital-products/`
- Algonquian Digital Store: `/algonquian-digital-store/`
- ALGQ WooCommerce Bridge: `/algq-woocommerce-bridge/`
- Algonquian Property Stewardship Services: `/algonquian-property-stewardship-services/`
- Algonquian Navigation: `/algonquian-navigation/`

## Operational / Documentation Routes

Public plugin overview routes above are the URLs used by WordPress `Visit plugin site`. Operational, Getting Started, Documentation, dashboard, calculator, portal, and settings pages may continue to use their established application routes.

### Deal Intake
- /plugin/deal-intake/start
- /plugin/deal-intake/docs
- /plugin/deal-intake/settings

### Pipeline CRM
- /plugin/pipeline-crm/start
- /plugin/pipeline-crm/docs
- /plugin/pipeline-crm/board

### MAO Engine
- /plugin/mao-engine/start
- /plugin/mao-engine/docs
- /plugin/mao-engine/calculator

### Offer Generator
- /plugin/offer-generator/start
- /plugin/offer-generator/docs
- /plugin/offer-generator/templates

### Buyer Portal
- /plugin/buyer-portal/start
- /plugin/buyer-portal/docs
- /buyer-dashboard

### Funding Tracker
- /plugin/funding-tracker/start
- /plugin/funding-tracker/docs
- /funding-dashboard

### Automation Engine
- /plugin/automation-engine/start
- /plugin/automation-engine/docs
- /automation-rules

### PDF Engine
- /plugin/pdf-engine/start
- /plugin/pdf-engine/docs
- /documents/signatures

### Document Library
- /plugin/document-library/start
- /plugin/document-library/docs
- /documents

### Command Center
- /plugin/command-center/start
- /plugin/command-center/docs
- /dashboard

## Release Rule

Before a release is packaged, its version must compare greater than the corresponding production-site baseline in `config/plugin-metadata-standard.json`. A new package must never downgrade a plugin that is already installed on AlgonquianRealEstate.com.
