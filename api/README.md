# Algonquian Real Estate API Gateway

This directory is the Node.js application runtime for `api.algonquianrealestate.com`.

## Current bootstrap scope

The initial runtime exists to make the platform repository fully importable/deployable as a Node application without misrepresenting the WordPress/PHP plugin suite as a static website.

Implemented routes:

- `GET /` — service metadata
- `GET /v1/health` — non-secret health/status response

All other `/v1/*` routes intentionally return HTTP `501` until the corresponding authoritative ARE service and WordPress API Bridge mapping are implemented.

## Authority boundary

The API gateway does not own Deal, intake, underwriting, offer, document, buyer, capital, or transaction records. It will dispatch authenticated requests to the Algonquian Real Estate API Bridge, which maps requests through `ARE_Platform_Service_Interface` to the authoritative WordPress plugin.

## Local runtime

```bash
npm run build
npm start
```

Default local address: `http://localhost:3000`.

## Production configuration

Copy the variable names from `api/.env.example` into the deployment provider's protected environment/secrets configuration. Never commit production API keys, signing secrets, OpenAI keys, Gemini keys, WordPress credentials, or other secrets.
