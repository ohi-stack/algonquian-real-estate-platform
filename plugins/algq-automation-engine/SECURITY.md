# Security Policy

## Access control

All administrative writes require a granular Algonquian capability and a WordPress nonce. REST routes require explicit permission callbacks.

## Execution boundary

The engine does not evaluate PHP, execute arbitrary shell commands, or call arbitrary remote URLs. Platform action hooks must use an `algq_` prefix and be explicitly allowed through `algq_automation_allowed_platform_hooks`.

## Sensitive data

Passwords, tokens, secrets, authorization values, signatures, and private keys are redacted from local logs and REST output. Email message bodies are not copied into the automation log.

## Idempotency

Queue jobs use deterministic idempotency keys within a configurable suppression window to reduce duplicate execution.

## Reporting vulnerabilities

Report security issues privately to the repository owner. Do not publish exploit details in a public issue.
