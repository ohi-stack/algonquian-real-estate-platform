# Production Acceptance — Algonquian Deal Marketplace 2.0.0

The plugin is production-approved only after evidence exists for every applicable item below.

## Installation and upgrade

- Install on a clean WordPress 6.8+ site running PHP 8.2+.
- Upgrade from the reviewed 1.0.0 package without losing Marketplace Deals.
- Confirm schema version `2.0.0` and all four Marketplace tables.
- Confirm legacy NDA metadata is imported once and not duplicated.
- Confirm deactivation preserves data.
- Confirm uninstall preserves data unless explicit cleanup is enabled.

## Shared buyer role

- Activate Buyer Portal first, then Marketplace; verify all required capabilities.
- Activate Marketplace first, then Buyer Portal; verify all required capabilities.
- Confirm Marketplace never deletes or replaces `algq_buyer`.
- Confirm Platform Plugin capability reconciliation reports healthy.

## Route ownership

- Confirm Buyer Portal owns `/buyer-dashboard/`.
- Confirm Marketplace creates `/marketplace/`.
- Confirm Marketplace uses `/buyer-dashboard/marketplace/`, `/buyer-dashboard/nda/`, and `/buyer-dashboard/submit-offer/` when the Buyer Dashboard exists.
- Confirm no generated page is overwritten after administrator edits.
- Confirm WPBakery content uses `[vc_column_text]...[/vc_column_text]`.

## Buyer authorization

Test with administrator, approved buyer, registered buyer without entitlement, expired grant, revoked grant, and ordinary subscriber accounts.

- Marketplace summary behavior is appropriate for each account.
- Premium and private deals require an active access grant or Platform entitlement.
- Allowed-buyer restrictions are enforced.
- Expired deals are inaccessible to buyers.
- Direct REST requests cannot bypass access.

## NDA evidence

- Acceptance requires login, capability, nonce, explicit checkbox, deal scope, and current version.
- Acceptance stores UUID, user, deal, version, document hash when supplied, UTC timestamp, IP hash, and user-agent hash.
- A changed NDA version requires new acceptance.
- Revoked acceptance is no longer sufficient.

## Package delivery

- No raw package URL is rendered.
- Package attachment is stored outside direct public access or resolved through the private file-service filter.
- Download requires exact-deal access, current NDA, download capability, and nonce.
- Unauthorized and expired requests are denied.
- Download activity is audited.

## Offers

- Deal ID cannot be changed to an unauthorized deal.
- NDA is required where configured.
- Amount validation, maximum term length, nonce, capability, and rate limit are enforced.
- Offer submission creates one record and one audit event.
- Administrative status changes are capability and nonce protected.
- Submission is clearly identified as non-binding until formal agreement.

## Integrations

- Central Platform audit listener receives Marketplace events.
- Central Mail Gateway sends offer notifications.
- Automation Engine receives NDA, offer, package, and status events.
- Stripe entitlement creation grants the intended deal only.
- Stripe entitlement revocation removes access.
- Document Library or private storage resolves the package path.
- Admin Command Center can report Marketplace health and failures.

## Security and quality

- Run `php -l` on every PHP file.
- Run WordPress Coding Standards and static analysis.
- Test CSRF, stored XSS, reflected XSS, SQL injection, IDOR, privilege escalation, and forced browsing.
- Test keyboard navigation, visible focus, labels, status text, mobile layout, and screen-reader output.
- Verify audit records omit raw secrets, payment data, full package contents, and full message bodies.
