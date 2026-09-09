# ARE Agent Engine 0.1.0 — Source Registration and Recovery Gate

## Verified production state

The AlgonquianRealEstate.com WordPress Plugins screen reports:

- **Plugin:** Algonquian ARE Agent Engine
- **Version:** 0.1.0
- **Description:** Orchestration, skills, approvals, execution runs, and audit controls for Algonquian Real Estate operational agents.
- **Author:** Algonquian Real Estate, LLC
- **Observed:** 2026-09-09

## Runtime source located

A separate source repository exists at:

`ohi-stack/algq-agent-engine`

Observed source characteristics:

- React/Vite/TypeScript application;
- agent and skill registries;
- approval-policy models;
- execution-run and run-trace models;
- approval tickets;
- idempotency and correlation concepts;
- Google Tasks integration;
- prototype/seed records and UI state;
- `package.json` currently reports package name `react-example` and version `0.0.0`.

This repository is registered as the **Agent Engine runtime/control-plane source**.

## WordPress source not yet recovered

No authoritative PHP source for a WordPress plugin bootstrap named `algq-agent-engine.php` was found in the searched canonical repositories.

Therefore:

- runtime/control-plane source is present;
- live WordPress adapter 0.1.0 is present in production;
- the two artifacts are not yet proven to be equivalent;
- the WordPress adapter must not be recreated by simply renaming or packaging the frontend runtime repository.

## Recovery evidence required

To reconcile the live WordPress adapter source, obtain one of:

1. the exact installed `algq-agent-engine` WordPress plugin directory;
2. the exact ZIP used for the 0.1.0 installation;
3. an authoritative Git commit/tag proven to have generated the deployed package.

Then record:

- file inventory;
- SHA-256 hashes;
- plugin header/version;
- database/options/schema changes;
- capabilities;
- REST/admin-post/AJAX endpoints;
- shortcodes or blocks;
- cron/scheduled work;
- authentication model;
- Platform Service Interface calls;
- runtime endpoint/configuration;
- audit/event behavior;
- uninstall/deactivation behavior.

## Architecture control

The WordPress adapter may bridge WordPress to the Agent Engine runtime and Platform services, but it must not become a second authority for Deals, underwriting, offers, documents, funding, buyers, marketplace records, stewardship records, automation rules, or closing state.

## Production prohibition

Until the live 0.1.0 adapter source is recovered and validated:

- do not claim WordPress source parity;
- do not overwrite the live plugin with an invented replacement;
- do not lower the deployed version;
- do not move authoritative records into the Agent Engine;
- do not allow prototype seed data to be interpreted as live operating evidence.

## Definition of done

Source registration is complete when:

- the runtime repo is linked and documented;
- the runtime authority contract matches the ARE platform architecture;
- the 7-agent implementation is distinguished from the 14-role target model;
- the live WordPress adapter remains explicitly marked as source-unrecovered;
- the exact deployed adapter source is later recovered, hashed, reviewed and tested before any replacement deployment.
