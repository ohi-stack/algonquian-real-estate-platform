# Stripe Production Release Checklist

## Platform

- [ ] Load `ALGQ_Stripe_Integration` from the Platform Plugin bootstrap.
- [ ] Register `manage_algq_stripe` and assign it only to authorized administrators.
- [ ] Add test/live mode status to Platform Health.
- [ ] Add webhook connectivity and last-event diagnostics.
- [ ] Keep secret and webhook keys outside WordPress options and GitHub.
- [ ] Confirm API errors are masked in public responses and useful in protected diagnostics.

## Webhooks

- [ ] Verify raw-body signatures.
- [ ] Reject stale signatures.
- [ ] Reject missing or malformed event payloads.
- [ ] Prevent duplicate event processing.
- [ ] Log event ID, type, mode, timestamp, result, and affected ARE module.
- [ ] Test retry behavior.
- [ ] Test out-of-order subscription events.

## Digital Store and WooCommerce Bridge

- [ ] Create Checkout Sessions only from server-controlled products and prices.
- [ ] Grant downloads only after verified payment confirmation.
- [ ] Revoke or adjust entitlements after refunds.
- [ ] Reconcile WooCommerce order status with Stripe status.
- [ ] Prevent duplicate customers and duplicate entitlements.

## Property Stewardship

- [ ] Map service plans to approved Stripe Prices.
- [ ] Activate service only after verified payment or approved invoice status.
- [ ] Handle upgrades, downgrades, cancellations, and failed renewals.
- [ ] Preserve service history after billing cancellation.
- [ ] Separate vendor costs from client-facing Stripe charges.

## Automation Engine

- [ ] Add normalized Stripe triggers.
- [ ] Add dry-run support.
- [ ] Prevent circular payment/access automations.
- [ ] Send failed jobs to the dead-letter queue.
- [ ] Make access grants and revocations idempotent.

## Admin Command Center

- [ ] Display test/live mode prominently.
- [ ] Report revenue timestamps and coverage.
- [ ] Add failed-payment and webhook-failure alerts.
- [ ] Restrict customer-level financial views.
- [ ] Add refund and subscription-cancellation reporting.

## Buyer Portal, Investor Network, Documents, and Marketplace

- [ ] Define exactly which features require payment.
- [ ] Preserve free access paths where required.
- [ ] Use a shared entitlement service.
- [ ] Prevent direct-object-reference access to paid assets.
- [ ] Verify access expiry and cancellation behavior.

## Quality assurance

- [ ] Unit-test signature verification.
- [ ] Unit-test metadata sanitization.
- [ ] Integration-test Checkout Session creation.
- [ ] Integration-test webhook replay protection.
- [ ] Test refunds and partial refunds.
- [ ] Test failed and recovered invoice payments.
- [ ] Test subscription cancellation at period end and immediate cancellation.
- [ ] Test test-mode/live-mode isolation.
- [ ] Test WordPress multisite behavior if supported.
- [ ] Run PHP compatibility, WordPress coding-standard, and security scans.

## Deployment

- [ ] Configure test credentials.
- [ ] Register test webhook.
- [ ] Complete end-to-end test transactions.
- [ ] Document rollback and access-reconciliation procedures.
- [ ] Configure live credentials outside source control.
- [ ] Register live webhook.
- [ ] Perform a low-value live transaction and refund.
- [ ] Review audit, email, automation, and Command Center records.
- [ ] Approve production activation.
