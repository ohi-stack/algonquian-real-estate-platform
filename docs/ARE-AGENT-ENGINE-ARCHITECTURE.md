# Algonquian ARE Agent Engine Architecture

**Owner:** Algonquian Real Estate, LLC  
**Technology owner:** Algonquian Real Estate Technology Division  
**Runtime/control-plane source:** `ohi-stack/algq-agent-engine`  
**Live WordPress adapter observed:** `Algonquian ARE Agent Engine` 0.1.0  
**Architecture status:** Registered companion runtime; WordPress adapter source recovery pending

## Purpose

The Algonquian ARE Agent Engine exists to advance real-estate transactions by coordinating approved operational agents, skills, approval gates, execution runs, audit traces, and service calls across the Algonquian Real Estate platform.

It is an **orchestration system**, not an authoritative business-record database.

The governing execution pattern is:

```text
Signal / Next Action
        ↓
Agent Registry
        ↓
Skill Registry
        ↓
Context + Deal ID + Allowed-State Check
        ↓
Input Validation + Idempotency
        ↓
Approval Policy
        ↓
ARE Platform Service Interface
        ↓
Authoritative Owning Plugin
        ↓
Result / Failure / Retry
        ↓
Agent Run + Audit Trace
```

## Source architecture

The current runtime/control-plane source is maintained separately at:

`ohi-stack/algq-agent-engine`

The repository currently contains a React/Vite application, TypeScript agent and skill models, agent-run/approval-ticket models, orchestration services, and Google Tasks integration code.

The source repository's current `package.json` reports package version `0.0.0`. This must **not** be treated as the version of the live WordPress plugin.

The live WordPress Plugins screen reports **Algonquian ARE Agent Engine 0.1.0**. The exact PHP plugin source that produced that deployed artifact has not yet been recovered in the canonical repositories.

Therefore there are currently two distinct source/artifact layers:

1. **Agent Engine runtime/control plane** — `ohi-stack/algq-agent-engine`.
2. **WordPress adapter/plugin** — deployed 0.1.0; source recovery pending.

They must not be represented as the same artifact until file-level evidence establishes that relationship.

## Authoritative ownership boundary

The Agent Engine may own and persist its own orchestration records, including:

- agent definitions;
- skill definitions;
- approval policies;
- execution runs;
- approval tickets;
- run traces;
- correlation IDs and idempotency keys;
- orchestration audit events;
- retry/failure state associated with agent execution.

It does **not** own the underlying real-estate records it acts upon.

| Business domain | Authoritative owner |
|---|---|
| Canonical Deal and transaction stage | Pipeline CRM |
| Intake submission / consent | Deal Intake |
| Underwriting scenarios / MAO results | MAO Engine |
| Offers / proposals / LOIs | Offer Generator |
| Controlled documents | Document Library |
| PDF rendering / signature workflow | PDF & Signature Engine |
| Buyer profile / criteria / protected access | Buyer Portal |
| Marketplace visibility / NDA / responses | Deal Marketplace |
| Capital criteria / commitments / funding status | Funding Tracker |
| Stewardship clients / visits / vendors | Property Stewardship |
| Automation rules / queues / execution history | Automation Engine |
| Executive reporting | Admin Command Center |

The Agent Engine may read authorized context and request actions from those systems, but the owning plugin validates and persists the authoritative operation.

## Integration contract

Cross-plugin execution must use the shared Platform Service Interface or another explicitly approved Platform-owned contract.

```text
Agent Engine decides what should happen
        ↓
ARE_Platform_Service_Interface dispatches the request
        ↓
Owning plugin validates authority and business rules
        ↓
Owning plugin persists the authoritative result
        ↓
Agent Engine records the orchestration outcome
```

Direct Agent Engine writes into another plugin's tables are prohibited.

The canonical transaction reference is `deal_id`. Implementations must support string/UUID-capable identifiers and must not assume an integer-only Deal ID.

## Current source implementation

The current TypeScript runtime source defines seven agent implementations:

1. Deal Intake & Sourcing Agent
2. MAO & Quantitative Underwriting Agent
3. Purchase Offer & Contract Agent
4. Debt & Equity Capital Allocator Agent
5. Stage Checklist & Task Coordinator Agent
6. Legal & Document Generation Agent
7. Escrow & Closing Coordinator Agent

These are implementation/prototype definitions in the current source. They are not the full ARE target operating roster.

## Target ARE operating roster

The current ARE target agent architecture contains fourteen operating roles:

1. Intake
2. Enrichment
3. Qualification
4. Property Analysis
5. Underwriting
6. Acquisition
7. Follow-Up
8. Offer
9. Transaction
10. Buyer
11. Capital
12. Closing
13. Relationship
14. Executive

The seven-agent runtime implementation may be refactored, expanded, or mapped into these fourteen operating roles. No source document should claim all fourteen are implemented until code and runtime evidence support that claim.

## Required deal operating invariant

Every qualified active Deal should have:

- current status;
- next action;
- responsible agent or human;
- deadline when applicable;
- durable activity/history.

The Agent Engine should identify and escalate violations of this invariant, but Pipeline CRM remains authoritative for the Deal record and Deal-specific next-action state.

## Human approval boundary

Agents may perform approved work such as:

- research;
- data enrichment;
- classification;
- calculations;
- drafting;
- recommendations;
- task routing;
- reminders;
- monitoring;
- authorized administrative actions.

Human leadership retains final authority over:

- negotiations;
- binding offers;
- contracts and signatures;
- legal decisions;
- acquisition or disposition approval;
- capital commitments;
- movement or release of funds;
- transaction approval;
- closing.

Any skill definition or UI copy that appears to grant broader autonomous authority is subordinate to this architecture rule and must be corrected before production execution.

## Approval model

A production skill execution should evaluate, at minimum:

1. agent is enabled;
2. skill is registered and allowed for the agent;
3. operator is authorized;
4. canonical Deal can be resolved when the skill is deal-scoped;
5. current Deal state permits the requested skill;
6. required inputs are valid;
7. idempotency check passes;
8. consequential-action approval requirements are satisfied;
9. owning service is available;
10. result/failure is recorded with a correlation ID and audit trace.

`approvalPolicy: none` may be used only for genuinely non-consequential work. It cannot override ARE's human-control requirements.

## Source-model boundaries

The current frontend source includes `RealEstateDeal`, `FundingSource`, seed runs, sample metrics, and other application-level objects. Until connected to authoritative services, these must be treated as DTOs, demonstration state, test fixtures, or UI models.

They are **not** canonical ARE records.

In particular:

- `RealEstateDeal` must not become a competing Deal database;
- FundingSource objects must not replace Funding Tracker records;
- seed run counts/success rates must not be presented as actual operating performance;
- sample properties, lenders, approvals, and audit events must be clearly identifiable as non-production data.

## Language cleanup requirement

The current prototype source contains legacy wording such as "sovereign" in some operational/legal descriptions. Algonquian Real Estate LLC is a Connecticut real-estate operating company. ARE Agent Engine production interfaces and documentation must use institutional business language and must not imply governmental, sovereign, statutory, legal-professional, escrow, title, lending, or regulatory authority that ARE does not possess.

## Runtime / ACC relationship

The Agent Engine is the orchestration runtime used by ARE agents. The ARE Agent Command Console may expose controls and observability for agents, skills, approvals, runs, and health, but the ACC is a control surface rather than a competing business-record owner.

The canonical ARE ACC node is `acc.algonquianrealestate.com`. Deployment status of any particular Agent Engine build to that node must be documented separately from source registration.

## Production promotion gate

Before the Agent Engine may be described as fully integrated production orchestration, verify:

- exact deployed WordPress 0.1.0 adapter source is recovered and hashed;
- runtime/control-plane source and adapter responsibilities are documented;
- authentication and authorization are enforced server-side;
- Platform Service Interface calls replace simulated/direct business-state mutations;
- no competing Deal/funding/document/offer storage exists;
- approval gates are tested for contracts, capital, funds, and closing;
- idempotency and retry behavior are tested;
- audit events contain correlation IDs but do not leak secrets;
- failure and disconnected-plugin states are safe;
- Command Center/ACC surfaces display read-only or authorized controls only;
- end-to-end tests cover Intake → Qualification → Underwriting → Acquisition/Offer → Follow-Up and at least one approval-required action.

## Definition of done

Agent Engine architecture/source registration is complete when:

1. the runtime repository is linked from the ARE platform source registry;
2. the runtime repository documents this same authority contract;
3. the WordPress adapter remains separately identified until its source is recovered;
4. canonical record ownership is preserved;
5. human approval boundaries are enforced in both source and runtime;
6. prototype data and production data cannot be confused;
7. the 14-role target model is tracked separately from the currently implemented 7-agent source roster.
