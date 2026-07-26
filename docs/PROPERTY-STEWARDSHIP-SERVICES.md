# Property Stewardship Services

## Purpose

Property Stewardship Services provide property observation, documentation, communication, and owner-authorized service coordination for homeowners who cannot always be present.

The service must be presented as property coordination and stewardship—not caregiving, legal representation, fiduciary administration, guardianship, conservatorship, trusteeship, executorship, power of attorney, emergency response, or an unlicensed professional trade.

## Generated Pages

The platform page generator creates the following public pages:

| Page | Slug | Purpose |
|---|---|---|
| Property Stewardship Services | `/property-stewardship-services/` | Main service overview, services, service levels, and limitations |
| Trusted Property Contact™ | `/trusted-property-contact/` | Relationship-focused local property contact service |
| Property Stewardship Consultation | `/property-stewardship-consultation/` | Initial inquiry and consultation entry point |

The consultation page currently embeds `[algq_seller_intake]` as the available property-information intake interface. A dedicated stewardship intake shortcode may replace it in a later release.

## Service Scope

The page set identifies the following available functions:

- Scheduled exterior or interior property check-ins, as authorized
- Date-stamped photographs and written condition updates
- Vendor, landscaping, lawn, snow-removal, repair, and maintenance coordination
- Storm observations after conditions are reasonably safe
- Visible vacancy, damage, or deterioration monitoring
- Property-related emergency contact coordination
- Maintenance and service-record organization
- Preparation support for a future sale, lease, renovation, or property transition

## Service Levels

### Essential Watch

- Scheduled exterior visit
- Photographic update
- Visible-condition summary
- Owner notification of observed concerns

### Active Steward

- Essential Watch services
- Maintenance scheduling
- Vendor coordination
- Seasonal observations
- Vacancy monitoring

### Transition Support

- Active Steward services
- Property-transition consultation
- Maintenance-priority planning
- Preparation support for sale, lease, or renovation

## Required Boundaries

Public copy and service agreements must clearly state that Algonquian Real Estate:

- Acts only within written owner authorization
- Does not control owner finances
- Does not enter contracts in the owner’s name without lawful authority
- Does not approve expenditures outside written authorization
- Does not provide legal, tax, insurance, medical, or financial advice
- Does not replace licensed contractors, inspectors, property managers, attorneys, insurance professionals, or emergency services
- Does not guarantee prevention of theft, vandalism, weather damage, mechanical failure, or other loss

## WPBakery Standard

All generated content must use valid WPBakery closing syntax:

```text
[vc_column_text]
Content
[/vc_column_text]
```

Do not use HTML-style shortcode closures such as `</vc_column_text>`.

## Deployment

The page generator runs during plugin activation. For an existing installation, deactivate and reactivate the platform plugin after deploying the updated source, or invoke `ALGQ_Platform_Page_Generator::create_pages()` through an authorized upgrade routine.

The generator is idempotent: it detects existing pages by slug and does not overwrite administrator-edited content when the required marker or shortcode remains present.
