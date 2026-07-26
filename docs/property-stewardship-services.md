# Property Stewardship Services™

## Module classification

Property Stewardship Services is an owner-authorized property observation and coordination module for Algonquian Real Estate LLC. It is not a caregiving, fiduciary, legal, estate-planning, insurance-adjustment, licensed-inspection, or security service.

## Service levels

### Property Watch

- Scheduled exterior observations
- Time-stamped photographs
- Visible-condition summaries
- Storm-related observations
- Notification of apparent concerns

### Active Stewardship

- Property Watch services
- Maintenance scheduling
- Vendor coordination
- Seasonal service oversight
- Owner-approved follow-up tracking
- Emergency-contact coordination

### Transition Stewardship

- Active Stewardship services
- Property-readiness planning
- Clean-out coordination
- Repair and improvement coordination
- Document organization
- Coordination with owner-selected attorneys, accountants, brokers, contractors, inspectors, and other licensed professionals

## Required records

- Stewardship client record
- Property profile
- Written service authorization
- Emergency contacts
- Access instructions and key-control record
- Approved vendor list
- Spending limit and expense authorization
- Scheduled visit record
- Visit report
- Time-stamped photographs
- Maintenance history
- Vendor activity history
- Owner instructions
- Incident or escalation report
- Cancellation and termination record

## WordPress implementation

The initial module is located at:

```text
modules/property-stewardship/algq-property-stewardship.php
```

It registers:

```text
[algq_property_stewardship]
[algq_stewardship_portal]
```

It creates:

```text
/property-stewardship-services/
/property-stewardship-portal/
```

Generated WPBakery content uses the required syntax:

```text
[vc_column_text]
[algq_property_stewardship]
[/vc_column_text]
```

## Data authority

The stewardship module owns stewardship-specific client, visit, and vendor records. It must not become the authoritative owner of acquisition deals, pipeline stages, underwriting, offers, tenant leases, document versions, signature status, or automation rules.

Future integration should use stable platform hooks and canonical record identifiers.

## Security requirements

- Capability-protected administrative access
- Private-by-default records
- No public post archives
- No public search indexing
- Written owner authorization before service activity
- Controlled access to photographs and reports
- No storage of keys or access codes in ordinary post content
- No unrestricted direct file URLs
- Audit events for material changes and downloads
- Data minimization and retention controls

## Recommended next production phase

1. Replace generic custom fields with registered meta schemas.
2. Add a dedicated service-agreement record.
3. Add visit scheduling and recurring reminders.
4. Add private photo storage and signed download URLs.
5. Add expense-approval and vendor-insurance verification workflows.
6. Add a client-to-property access map.
7. Add emergency escalation rules through the Automation Engine.
8. Add document relationships through the Document Library.
9. Add report PDF generation through the PDF & Signature Engine.
10. Add automated production tests for permissions, page generation, data retention, and portal access.
