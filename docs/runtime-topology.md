# ARE Runtime Topology

## Canonical domains

| Domain | Role | Authority |
|---|---|---|
| `algonquianrealestate.com` | Public website, WordPress operating platform, canonical plugin records | Specialized ARE plugins retain record authority |
| `api.algonquianrealestate.com` | Unified external API gateway and backend service boundary | No independent business-record ownership |
| `acc.algonquianrealestate.com` | Dedicated Agent Command Console node | Orchestration, monitoring, approvals, commands; no duplicate business records |

## Governing architecture

```text
Public / operational WordPress surfaces
                │
                ▼
Algonquian Real Estate Platform Plugin
                │
                ├── ARE_Platform_Service_Interface
                ├── Shared Event Bus
                ├── Audit Layer
                ├── Mail / Notifications
                ├── Auth / Capabilities
                ├── Health / Registry
                └── API Bridge
                        │
                        ▼
             api.algonquianrealestate.com
                        ▲
                        │
             acc.algonquianrealestate.com
             Dedicated ACC node
```

## API rule

All external applications and control surfaces use the canonical API namespace:

`https://api.algonquianrealestate.com/v1/`

The API translates authenticated requests into platform service calls. It does not reach directly into companion-plugin tables and does not create competing copies of authoritative records.

## ACC rule

`acc.algonquianrealestate.com` is an independent node and deployment boundary for the Agent Command Console.

ACC may provide:

- agent orchestration and run visibility;
- approval queue and human-gate controls;
- transaction next-action monitoring;
- cross-system health and exception monitoring;
- command dispatch through the canonical API;
- agent activity and audit visibility.

ACC must not become authoritative for Deals, underwriting, offers, funding, documents, buyer authorization, signatures, or closing records.

## Canonical deal identity

Pipeline CRM remains authoritative for the canonical `deal_id`. Cross-system services, agent runs, approvals, underwriting, offers, documents, funding, and buyer matching must reference that identity rather than create new deal objects.

## Synchronization rule

**Sync references and events, not duplicate authoritative records.**

Material changes are propagated through the shared event layer and API/service contracts, including events such as `intake.accepted`, `deal.created`, `deal.stage_changed`, `underwriting.completed`, `strategy.approval_required`, `offer.sent`, `funding.confirmed`, and `deal.closed`.
