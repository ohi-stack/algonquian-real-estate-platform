# Architecture

## Authority

The PDF & Signature Engine owns:

- Generated PDF versions.
- Protected file references and hashes.
- Signature request records.
- Signer workflow metadata.
- Provider identifiers and normalized statuses.
- Signature execution evidence.

It does not own canonical deals, offers, document templates, platform users, mail transport, automation rules, or the central audit log.

## Data flow

```text
Approved offer or document
        ↓
PDF generation request
        ↓
Protected version + SHA-256 hash
        ↓
Signature request + ordered signers
        ↓
Provider adapter or manual tracking
        ↓
Authenticated provider events
        ↓
Normalized status + append-only evidence
        ↓
Completed document archive
```

## Provider contract

A provider integration should:

1. Register through `algq_signature_providers`.
2. Send a request through `algq_signature_send_request`.
3. Return a provider request ID and normalized status.
4. Authenticate webhooks through `algq_signature_webhook_authorized`.
5. Normalize payloads through `algq_signature_normalize_webhook`.
6. Avoid placing provider secrets or full payloads in logs.

## Status authority

Normalized request statuses are:

`draft`, `pending`, `sent`, `viewed`, `partially_signed`, `completed`, `declined`, `expired`, `cancelled`, and `failed`.

Provider-specific statuses must be mapped to this controlled vocabulary.
