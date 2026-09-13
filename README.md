# Algonquian Real Estate Platform

Enterprise WordPress and service platform for real estate acquisition, seller intake, canonical deal management, underwriting, offers, documents, signatures, automation, buyer access, capital relationships, property stewardship, commerce, reporting, agent orchestration, and system administration.

## Organization

- **Parent entity:** Algonquian Real Estate LLC
- **Technology division:** Algonquian Real Estate Technology Division
- **Primary market:** Connecticut
- **Author display:** Algonquian Real Estate, LLC

Algonquian Real Estate LLC was organized as a Connecticut limited liability company on February 11, 2026.

## Business Objective

ARE's present objective is to become a credible, financeable, repeatable real-estate operating company by completing a manageable first Connecticut project and converting that execution into a documented track record.

The preferred Stage 1 profile is approximately **3–20 residential units** or a similarly manageable small multifamily acquisition/rehabilitation project. A small infill/new-construction project may also qualify where financing, approvals, construction scope, and operating risk are sufficiently controlled.

The operating rule is:

**Real estate first. Revenue first. Automation first.**

Technology is infrastructure. It exists to improve lead generation, qualification, underwriting, follow-up, transaction execution, evidence preservation, reporting, operational efficiency, or legitimate technology value.

See `docs/ARE-OPERATING-OBJECTIVE-2026-09-13.md` for the current operating objective, Stage 1 progression, authority model, human-control rules, and repository priorities.

## Current Source and Production Boundary

The canonical repository currently contains:

- **Algonquian Real Estate Platform 3.1.0** source;
- **Pipeline CRM 2.2.1** source using schema 2.2.0;
- a reconciled **18-plugin production inventory** observed on 2026-09-09;
- a shared ARE WordPress admin design system;
- the Platform Service Interface for controlled cross-plugin operations;
- lifecycle Modules 13–20 inside the Platform architecture;
- companion Agent Engine architecture through `ohi-stack/algq-agent-engine`.

Canonical source state and live production state are intentionally tracked separately. Repository progress must not be described as deployed or production-certified until the applicable WordPress installation, migration, permissions, security, integration, and end-to-end acceptance gates are recorded as passed.

The highest-priority production blocker is the live **Pipeline CRM 2.1.0 → schema 2.2.0** migration. The migration must preserve production Deal semantics and pass the documented preflight/post-migration checks before the 2.2.1 runtime is deployed over the live database.

## Canonical Authority Model

- **Deal Intake** — seller/property submissions, source, consent, protected intake evidence, duplicate review, and initial accepted-opportunity handoff.
- **Pipeline CRM** — canonical Deal lifecycle plus shared CRM contacts, organizations, relationships, activity, and next actions.
- **MAO Engine** — underwriting scenarios, assumptions, calculations, risk flags, and underwriting approvals.
- **Offer Generator** — proposals, offers, versions, approval workflow, and delivery state.
- **Document Library** — controlled document metadata, classification, versions, retention, legal hold, and packages.
- **PDF & Signature Engine** — PDF rendering, signature requests, routing, provider events, and executed-file references.
- **Funding Tracker** — capital-source relationships, commitments, deal-level funding state, and related funding activity.
- **Buyer Portal / Deal Marketplace** — protected buyer identity/access and controlled opportunity distribution within their documented boundaries.
- **Automation Engine** — automation rules, events, jobs, retries, failure handling, and execution history.
- **Agent Engine** — orchestration objects, skills, approval routing, execution runs, correlation/idempotency evidence, and agent audit controls; it does not become a second system of record.
- **Admin Command Center** — executive aggregation, KPIs, reports, alerts, system health, and audit visibility.
- **Platform** — shared bootstrap, service registry, capabilities, security, mail, audit, protected storage, common UI, health monitoring, and lifecycle coordination contracts.

## Platform Lifecycle Modules 13–20

Platform 3.1.0 adds coordinated lifecycle capabilities without replacing specialized record authorities:

13. Seller Portal  
14. Title & Closing Engine  
15. Disposition Engine  
16. Investor Portal  
17. Reporting & Analytics  
18. Document Vault  
19. Task / Project Management  
20. Communications Hub

These modules coordinate work, assignments, deadlines, external references, audit events, and protected interfaces. They must not duplicate or override authoritative Deal, underwriting, offer, funding, document, buyer, signature, or closing records.

## Core Workflow

```text
Lead
→ Intake
→ Qualification
→ canonical Deal in Pipeline CRM
→ Underwriting in MAO Engine
→ Human Approval
→ Offer / Proposal
→ Documents / Signature
→ Funding
→ Closing
→ Operations
→ Reporting
```

Agent and automation systems may research, classify, recommend, route, monitor, schedule, create administrative tasks, and execute previously authorized actions. Human leadership retains final authority over negotiated terms, binding offers, contracts, signatures, legal decisions, financing approval, capital commitments, funds movement, acquisition/disposition approval, and closing authorization.

## Shared Production Standards

Every independently installable plugin must include:

- Valid WordPress plugin metadata.
- A protected bootstrap process and direct-access guard.
- Safe dependency validation.
- Activation, deactivation, migration, upgrade, and conservative uninstall behavior.
- Granular capabilities, nonce checks, REST permission callbacks, validation, sanitization, and escaping.
- Secure private-file access and upload validation where applicable.
- Centralized Platform Mail Gateway and audit services where applicable.
- `README.md`, `CHANGELOG.md`, `SECURITY.md`, and `uninstall.php`.
- No plaintext credentials or uncontrolled debug output.
- Accessible and responsive administration screens using the common ARE navy / blue / gold / teal interface system.
- `prefers-reduced-motion` support for shared motion behavior.
- No direct cross-plugin database writes where a Platform service contract exists.

## WPBakery Rule

Generated WPBakery content must use:

```text
[vc_column_text]
[algq_shortcode]
[/vc_column_text]
```

Never use the malformed closing tag `</vc_column_text>`.

Generated pages must be idempotent and must preserve administrator-edited content when the required shortcode or generation metadata remains present.

## Repository Layout

```text
plugin/       Legacy or current Platform Plugin source
plugins/      Canonical independently installable plugin source directories
modules/      Platform-integrated or transitional modules
assets/       Shared front-end and administrative assets
branding/     Brand standards and approved placeholders
database/     Schema and migration documentation
docs/         Architecture, installation, security and operating documentation
config/       Machine-readable plugin, service and route manifests
scripts/      Validation and build tooling
roadmap/      Version roadmap and launch planning
releases/     Generated release artifacts; never the only source of record
```

## Validation

Run:

```bash
php scripts/validate-wordpress-plugins.php
```

The GitHub Actions **WordPress Plugin Release Gate** verifies manifest integrity, source layout, plugin headers, required package files, PHP syntax, malformed WPBakery tags, debug indicators, and release-policy documentation.

Static validation does not replace activation, migration, permission, integration, or end-to-end testing in a disposable or production-equivalent WordPress environment.

## Documentation

- `docs/ARE-OPERATING-OBJECTIVE-2026-09-13.md` — current business objective, first-project progression, authority model, and repository priorities.
- `docs/wordpress-installation-readiness.md` — mandatory installation and production acceptance matrix.
- `docs/PLATFORM-SERVICE-INTERFACE.md` — shared service registry and cross-plugin operation contract.
- `SECURITY.md` — vulnerability handling and platform security baseline.
- `CHANGELOG.md` — release history and outstanding production requirements.
- `config/plugin-manifest.json` — authoritative plugin inventory, dependency graph, source/deployed version distinction, and release contract.

## Current Technical Objective

Safely reconcile canonical source with the observed production environment; complete the Pipeline CRM 2.1.0-to-2.2.0 migration path; validate Deal Intake handoff, Automation 2.1 event workflows, Command Center/Funding Track, production-ahead packages, Platform service calls, and Agent Engine integration; then package and deploy only releases supported by recorded acceptance evidence.
