# Property Stewardship 1.2.0 Production Reconciliation

**Plugin:** Algonquian Property Stewardship Services  
**Canonical slug:** `algq-property-stewardship`  
**Observed production version:** `1.2.0`  
**Canonical source version currently in repository:** `1.0.0`  
**Production observation date:** `2026-09-09`  
**Status:** `production_ahead_of_canonical_source_reconciliation_required`

## Current state

The live AlgonquianRealEstate.com WordPress Plugins inventory supplied on September 9, 2026 identifies Algonquian Property Stewardship Services version 1.2.0.

The canonical monorepo currently contains a 1.0.0 Stewardship package. Repository branch, commit, and code searches did not identify an exact 1.2.0 package, tag, commit, or branch. The historical `agent/stewardship-login-ui` branch is behind `main` and does not provide a distinct Stewardship source delta that can be treated as 1.2.0.

The live plugin description is:

> Owner-authorized property observation, visit reporting, vendor coordination, maintenance tracking, and secure client stewardship records.

This establishes deployed version state only. It does not establish the code delta between 1.0.0 and 1.2.0.

## Authority boundary

Property Stewardship remains authoritative for owner-authorized stewardship client records, property visits, vendor coordination, maintenance/stewardship activity, reports, and client portal access.

The reconciliation must not move authoritative stewardship records into Pipeline CRM, the Platform Plugin, Command Center, Automation Engine, or Agent Engine.

Future CRM integration may link Stewardship clients to the shared Pipeline CRM relationship layer, but Stewardship operational records remain owned here.

## Mandatory recovery source

At least one of the following must be obtained before source parity can be declared:

1. the exact `algq-property-stewardship` directory from the live WordPress installation;
2. the exact ZIP/plugin artifact used to install or upgrade production to 1.2.0; or
3. an authoritative source commit/tag proven to have generated the live 1.2.0 package.

Do not reconstruct 1.2.0 from memory, old chat output, planned features, or version numbers alone.

## Recovery evidence to record

For the recovered production package, record:

- retrieval date/time;
- source environment or artifact location;
- plugin directory name;
- complete file inventory;
- SHA-256 hash of the release ZIP if available;
- SHA-256 hashes of the bootstrap PHP file and other material source files;
- plugin header version;
- internal version constant(s);
- schema/data version option(s);
- WordPress and PHP minimums;
- author and author URI metadata;
- dependency declarations;
- activation/deactivation/uninstall behavior.

## Source comparison

Compare recovered 1.2.0 against canonical 1.0.0 for at least:

### Records and schema

- custom post types or custom tables;
- registered meta fields;
- schema migrations;
- ownership/user linkage;
- visit records;
- vendor records;
- maintenance/activity records;
- document/media references;
- uninstall-retention behavior.

### Security and authorization

- management capabilities;
- client portal capability;
- owner/record isolation;
- nonce checks;
- form validation and sanitization;
- output escaping;
- secure document URLs;
- protection against public attachment exposure;
- protected record downloads;
- direct-access guards.

### User interfaces

- admin menu/screens;
- shared ARE Admin UI adoption;
- login-required and unauthorized states;
- stewardship dashboard/client portal;
- empty states;
- property/visit/vendor tables;
- forms and action buttons;
- responsive behavior;
- reduced-motion behavior if motion is present.

### Public/plugin interfaces

Inspect and record every registered shortcode. Canonical 1.0.0 currently represents:

- `[algq_property_stewardship]`
- `[algq_stewardship_portal]`

Do not assume 1.2.0 has the same shortcode inventory until verified from recovered source.

### Integrations

Verify the recovered 1.2.0 behavior with:

- Algonquian Real Estate Platform;
- Document Library / secure-document delivery;
- Pipeline CRM, if present;
- Automation Engine, if present;
- Admin Command Center, if present;
- mail/notification services;
- audit logging;
- client authentication.

## Production safeguards

Until reconciliation is complete:

- **DO NOT** deploy canonical 1.0.0 over production 1.2.0.
- **DO NOT** change the 1.0.0 source header or version constant to 1.2.0 merely to satisfy version checks.
- **DO NOT** generate a 1.2.0 release ZIP from unreconciled 1.0.0 source.
- **DO NOT** remove the manifest `downgrade_prohibited` control.
- **DO NOT** begin a new Stewardship feature release from the 1.0.0 branch if it would later replace production.

## Reconciliation sequence

1. Freeze the repository's Stewardship package as non-deployable to production.
2. Recover exact production 1.2.0 source/artifact.
3. Hash and inventory recovered files.
4. Diff recovered 1.2.0 against canonical 1.0.0.
5. Identify all 1.1.0/1.2.0 changes that are actually present.
6. Preserve production-only fixes while incorporating newer shared Platform/Admin UI changes where compatible.
7. Update plugin header, constants, README, CHANGELOG, SECURITY, and manifest only from reconciled evidence.
8. Run static validation and PHP 8.2 lint.
9. Run WordPress upgrade testing using a copy of production data.
10. Run authorization and client-isolation tests.
11. Run secure-document tests.
12. Run end-to-end client → property → visit → vendor/maintenance → report/document → portal workflow tests.
13. Confirm deactivation/uninstall retention.
14. Record rollback artifact and rollback procedure.
15. Only then set canonical `source_version` to `1.2.0` and mark source parity.

## Definition of done

Property Stewardship 1.2.0 is reconciled only when:

- the exact live source or exact release artifact has been recovered;
- material differences are understood and incorporated;
- source files declare 1.2.0 because they actually represent the recovered 1.2.0 implementation;
- manifest `source_version`, `deployed_version`, and `target_version` all equal `1.2.0`;
- static, migration, permissions, security, portal, document, and end-to-end tests pass;
- the release artifact is retained with a cryptographic hash;
- rollback is documented and tested; and
- no authoritative Stewardship record ownership has been transferred to another plugin.
