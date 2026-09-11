# Algonquian Real Estate API Gateway

This directory is the Node.js application runtime for **`api.algonquianrealestate.com`**.

## Canonical production role

`api.algonquianrealestate.com` is the unified external/API gateway and backend service boundary for the Algonquian Real Estate operating platform.

It is responsible for authenticated HTTP access to platform services, normalized API responses, correlation and idempotency context, health/version reporting, and controlled dispatch through the WordPress API Bridge and `ARE_Platform_Service_Interface`.

It is **not** the Agent Command Console node.

The Agent Command Console is deployed independently at:

- **ACC node:** `https://acc.algonquianrealestate.com`
- **ACC canonical repository/runtime:** separate ACC/agent runtime repository
- **API dependency:** ACC consumes `https://api.algonquianrealestate.com/v1/`

This separation gives ACC its own deployment, scaling, release, and security boundary while preserving one canonical ARE API surface.

## Current bootstrap scope

Implemented routes:

- `GET /` — service metadata
- `GET /v1/health` — non-secret health/status response

All other `/v1/*` routes intentionally return HTTP `501` until the corresponding authoritative ARE service and WordPress API Bridge mapping are implemented.

## Target API namespaces

The production namespace is:

```text
https://api.algonquianrealestate.com/v1/
```

Planned service families include:

- `/v1/deals`
- `/v1/intake`
- `/v1/underwriting`
- `/v1/offers`
- `/v1/documents`
- `/v1/buyers`
- `/v1/capital`
- `/v1/agents`
- `/v1/approvals`
- `/v1/automation`
- `/v1/events`
- `/v1/health`
- `/v1/auth`

## Runtime topology

```text
AlgonquianRealEstate.com / WordPress ARE Plugin Suite
                    │
                    ▼
          ARE API Bridge Plugin
                    │
                    ▼
      ARE_Platform_Service_Interface
                    │
                    ▼
      api.algonquianrealestate.com
                    ▲
                    │
      ┌─────────────┴─────────────┐
      │                           │
app.algonquianrealestate.com   acc.algonquianrealestate.com
Application surfaces           Dedicated ACC node
```

ACC does not become a second system of record. It reads and commands the ARE operating platform through the canonical API and human-approval controls.

## Authority boundary

The API gateway does not own Deal, intake, underwriting, offer, document, buyer, capital, funding, signature, or transaction records. It dispatches authenticated requests to the Algonquian Real Estate API Bridge, which maps requests through `ARE_Platform_Service_Interface` to the authoritative WordPress plugin.

Canonical ownership remains with the specialized ARE systems, including Pipeline CRM for the canonical Deal record.

## Local runtime

```bash
npm run build
npm start
```

Default local address: `http://localhost:3000`.

## Production configuration

Recommended deployment contract:

```text
Environment: Production
Branch: main
Application root: api/
Domain: api.algonquianrealestate.com
NODE_ENV: production
```

Copy the variable names from `api/.env.example` into the deployment provider's protected environment/secrets configuration. Never commit production API keys, signing secrets, model-provider keys, WordPress credentials, or other secrets.
