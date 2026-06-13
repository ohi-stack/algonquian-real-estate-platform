# Security

## WordPress Security Standards

The Algonquian Offer Generator should maintain:

- Capability checks for administrative actions.
- Nonces for form submissions and AJAX requests.
- Sanitization for all incoming fields.
- Escaping for all rendered output.
- Restricted access to generated transaction documents.
- Audit logging for generated and modified offers.

## Sensitive Data

Offer documents may contain seller, buyer, property, transaction, and financial terms. Access should be restricted to authorized internal users unless explicitly shared.
