# Algonquian Property Stewardship Services

**Canonical source version:** 1.0.0  
**Observed production version:** 1.2.0  
**Production observed:** 2026-09-09  
**Reconciliation status:** Production is ahead of canonical source. Do not package or deploy this repository's 1.0.0 Stewardship source over the live 1.2.0 installation.

Owner-authorized property observation, visit reporting, vendor coordination, maintenance tracking, and transition support for Algonquian Real Estate LLC.

## Production reconciliation control

The live AlgonquianRealEstate.com WordPress Plugins inventory identifies **Algonquian Property Stewardship Services 1.2.0** with the description:

> Owner-authorized property observation, visit reporting, vendor coordination, maintenance tracking, and secure client stewardship records.

The canonical repository currently contains only the 1.0.0 source package. No exact 1.2.0 source package, commit, tag, or branch has been identified in this repository.

Until the deployed 1.2.0 package is recovered and reconciled:

- `1.2.0` is the production downgrade floor;
- the repository's `1.0.0` package must not be released to production;
- no source header, constant, changelog, or package should be relabeled `1.2.0` merely to match the live Plugins screen;
- production behavior that is not represented in canonical source must be treated as unreconciled rather than reconstructed from assumption;
- further Stewardship feature work should branch from the recovered/reconciled 1.2.0 source, not from 1.0.0.

See `docs/reconciliation/PROPERTY-STEWARDSHIP-1.2.0.md` for the recovery and acceptance gate.

## Security model represented by canonical 1.0.0 source

- Stewardship record post types are non-public and excluded from REST exposure.
- Administrative record editing is restricted to `manage_algq_stewardship`.
- Portal queries are scoped to `_algq_steward_owner_user_id = current user`.
- Visit queries require both the authorized client record and matching owner user ID.
- Visit files/photos are represented as protected Document Library identifiers; the plugin does not expose WordPress attachment URLs directly.
- Secure document links are provided only through the `algq_secure_document_url` integration filter.
- Generated pages are idempotent and use valid WPBakery closing syntax.

These controls describe the canonical 1.0.0 source and must be re-verified against the recovered 1.2.0 production package before source parity is declared.

## Shortcodes represented by canonical 1.0.0 source

- `[algq_property_stewardship]`
- `[algq_stewardship_portal]`

The recovered 1.2.0 package must be inspected for additional, renamed, removed, or changed shortcodes before the shortcode inventory is updated.

## Service boundaries

The service is property coordination and stewardship. It does not represent legal, fiduciary, guardianship, caregiving, insurance-adjusting, licensed-inspection, or security authority.

## Production acceptance

Source parity with the live 1.2.0 installation requires, at minimum:

1. recovery of the exact deployed plugin files or the exact release artifact that produced them;
2. file-tree and file-hash comparison against repository source;
3. reconciliation of plugin header metadata, version constants, schema/data migrations, shortcodes, capabilities, admin UI, portal UI, dependencies, hooks, and integrations;
4. preservation of live data and uninstall-retention behavior;
5. activation/upgrade testing from the current production state;
6. capability and client record-isolation tests;
7. secure document-link authorization tests;
8. end-to-end stewardship client, visit, vendor, maintenance, and portal workflow testing;
9. confirmation that shared ARE Admin UI integration does not weaken record authorization;
10. promotion of `source_version` to `1.2.0` only after the reconciled source matches the recovered production package and validation evidence is recorded.
