# Algonquian Real Estate Platform — Production Release Train

## Purpose

This document is the authoritative production-release sequence for the Algonquian Real Estate WordPress platform. It converts the existing collection of upgrade pull requests and validated packages into one controlled release program.

A plugin may have production-directed source or a stable semantic version without being classified as production-ready. Final production classification requires the acceptance evidence in `docs/wordpress-installation-readiness.md`.

## Platform Baseline

- WordPress: 6.8+
- PHP: 8.2+
- Canonical source: unpacked source under version control
- Shared namespace/prefix: `algq`
- Platform authority: Algonquian Real Estate Platform Plugin
- Production release artifacts: generated only from accepted canonical source
- WPBakery closing syntax: `[/vc_column_text]`

## Release Sequence

1. Algonquian Real Estate Platform Plugin 2.0.0 — shared registry, roles/capabilities, security, mail, audit, file storage, page generation and health services.
2. Algonquian Pipeline CRM 2.0.0 — canonical deal authority.
3. Algonquian Deal Intake 2.0.0 — seller/property intake and controlled CRM handoff.
4. Algonquian MAO Engine 2.0.0 — versioned underwriting and approval.
5. Algonquian Document Library 2.0.0 — authoritative private document records and access control.
6. Algonquian PDF & Signature Engine 2.0.0 — controlled PDF and signature workflow.
7. Algonquian Offer Generator 2.0.0 — versioned offer records and approval workflow.
8. Algonquian Automation Engine 2.0.0 — durable rules, queues, retries and event execution.
9. Algonquian Admin Command Center 1.2.0 — executive reporting, health, audit visibility and authorized system commands.
10. Algonquian Buyer Portal 1.1.0 — authorized buyer access and NDA controls.
11. Algonquian Deal Marketplace 2.0.0 — controlled opportunity distribution.
12. Algonquian Funding Tracker 1.0.0 — capital-source and funding-status administration.
13. Algonquian Digital Products 1.0.0 — governed digital catalog.
14. Algonquian Digital Store 1.1.0 — WooCommerce-backed store and product vault.
15. Algonquian WooCommerce Bridge 2.0.0 — entitlements, refunds, subscriptions and revenue integration.
16. Property Stewardship Services — remains in production hardening until a stable target version and acceptance evidence are recorded.

## Current Repository Work

The target versions and active upgrade pull requests are tracked in `config/plugin-manifest.json`.

The repository-wide WordPress installation-readiness gate is already merged to `main` and is the controlling release standard.

## Mandatory Acceptance Gates

For each plugin:

- PHP syntax and static validation pass.
- Required package documentation is present.
- Clean WordPress installation succeeds.
- Upgrade/migration from the currently deployed version succeeds.
- Activation, deactivation and reactivation succeed.
- Capability and record-level authorization tests pass.
- Nonces and REST permission callbacks are enforced.
- Private-file delivery cannot be bypassed by direct URL.
- Generated pages are idempotent and preserve administrator-edited content.
- Database migrations are repeatable and non-destructive.
- Cross-plugin contracts resolve the same canonical deal, buyer, document and approval identities.
- No plaintext credentials or uncontrolled debug output are present.
- The relevant end-to-end workflow passes.

## Transaction Workflow Acceptance

The protected foundation is not accepted as a suite until this controlled flow works end to end:

`Seller Submission → Deal Intake → Pipeline CRM → MAO Engine → Human Approval → Offer Generator → Document/PDF Workflow → Follow-Up/Automation → Buyer/Capital Workflow → Closing Administration → Command Center Reporting`

Every qualified deal must have a recorded next action, responsible human or agent, status and deadline where applicable.

## AI Agent Boundary

Algonquian Real Estate Agents may research, enrich, classify, calculate, draft, prioritize, schedule, monitor and execute approved administrative workflows.

Human approval remains mandatory for:

- final acquisition strategy;
- final purchase price;
- binding or materially revised offers;
- contract execution;
- financing or capital commitments;
- funds movement;
- final disposition acceptance; and
- closing-document execution.

The software should enforce these as approval gates rather than relying only on written policy.

## Release Evidence

A final production tag or ZIP must be traceable to:

- commit SHA;
- plugin version;
- acceptance checklist;
- migration test result;
- security/permissions test result;
- end-to-end test result; and
- SHA-256 checksum of the generated release package.

No release artifact should be treated as authoritative if its source is not represented in the repository.
