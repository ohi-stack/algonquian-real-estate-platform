# Deal Intake 2.1.0 Source Reconciliation

**Observed live version:** 2.1.0  
**Canonical source artifact:** `algq-deal-intake-2.1.0-production(1).zip`  
**Artifact SHA-256:** `d626c63a8eb4ab6e3eed44833da7f34c60bc4de177e03f9b34e2ef80281f0c7b`  
**Canonical plugin Git tree:** `8b1de4da4959f20da0aaa9d48e1b774a87a6012b`  
**Source provenance:** Matches the Deal Intake subtree in PR #74 (`agent/deal-intake-2.1.0-production`) byte-for-byte at the Git tree level.

## Reconciliation result

The previously deployed Deal Intake 2.1.0 source has been recovered from the production package and matched to repository source. The package contains the 2.1.0 bootstrap, 22 PHP files, release documentation, frontend/admin CSS, and the documented production features.

The canonical manifest may therefore record `source_version`, `deployed_version`, and `target_version` as 2.1.0 with `canonical_source_matches_production`.

## Authority boundary

Deal Intake owns submission-time seller, property, consent, source, duplicate-review, lead-score, and protected artifact evidence. It does not own the canonical Deal lifecycle after acceptance. Pipeline CRM remains the Deal authority; MAO Engine owns underwriting; Offer Generator owns offers/proposals; controlled document/signature systems retain their own authorities.

## Validation completed

- Production ZIP extracted successfully.
- Git tree hash of the extracted plugin equals the PR #74 plugin subtree hash.
- PHP syntax validation passed across all 22 PHP files in the recovered package.
- No executable PHP/TXT/HTML file contains the malformed WPBakery `</vc_column_text>` closing token.
- The only documentation occurrence of that token is an explicit warning not to use it.
- Protected upload code resolves storage under `wp-content/uploads/algq-private/deal-intake/` by default and remains subject to the plugin's server-deny/deployment requirements.

## Production behavior represented by the recovered source

- public/property-review/internal/quick-capture intake interfaces;
- homeowner-options and seller-portal interfaces;
- seller-funnel reconciliation for `/submit-a-property/` and `/sell-your-property/`;
- legacy `/submit-property/` redirect;
- consent evidence, duplicate review, lead scoring and idempotent Pipeline CRM handoff;
- protected supporting-document uploads with MIME checks, UUID names and SHA-256 hashes;
- optional Cloudflare Turnstile verification;
- protected PDF intake archive and private Media Library registration;
- capability-gated signed downloads;
- archive delivery through the configured mail path;
- ARE navy/gold/teal/white interface styling.

## Remaining operational gate

Source parity is now established. This does not by itself prove every live runtime path is healthy. Continue to verify WordPress activation/upgrade, permissions, public and internal form submission, duplicate handling, protected file blocking, PDF generation/download authorization, mail delivery, and exactly-one Pipeline CRM deal handoff in the deployed environment before describing a newly packaged build as production-certified.
