# Algonquian Stripe Integration

## Architecture decision

Stripe is a shared Platform Plugin integration. Individual ARE plugins must not store Stripe secret keys, register independent webhook endpoints, or maintain separate customer records.

The Platform Plugin owns:

- API authentication;
- webhook verification and idempotency;
- customer and payment-method references;
- Checkout Sessions;
- Billing Portal sessions;
- subscriptions, invoices, refunds, and payment status;
- audit events;
- capability enforcement;
- health checks and diagnostics.

## Credential policy

Secrets must be supplied through environment variables or `wp-config.php` constants:

```php
define( 'ALGQ_STRIPE_SECRET_KEY', 'sk_live_...' );
define( 'ALGQ_STRIPE_WEBHOOK_SECRET', 'whsec_...' );
```

Do not commit credentials to GitHub or store readable secret keys in WordPress options.

The publishable key may be stored in the sanitized `algq_stripe_settings` option.

## Central endpoint

```text
POST /wp-json/algq/v1/stripe/webhook
```

Webhook requests are verified using Stripe's signed payload, a five-minute timestamp tolerance, and constant-time signature comparison. Event IDs are temporarily retained to prevent duplicate processing.

## Supported event contract

The shared service dispatches:

```text
algq_stripe_event_received
algq_stripe_event_checkout_session_completed
algq_stripe_event_payment_intent_succeeded
algq_stripe_event_payment_intent_payment_failed
algq_stripe_event_customer_subscription_created
algq_stripe_event_customer_subscription_updated
algq_stripe_event_customer_subscription_deleted
algq_stripe_event_invoice_paid
algq_stripe_event_invoice_payment_failed
algq_stripe_event_charge_refunded
```

Each integration must be idempotent and must not grant access solely from a browser redirect. Entitlements are granted only after a verified webhook or server-side Stripe retrieval.

## Plugin integration matrix

| Plugin or module | Stripe responsibility | Priority |
|---|---|---:|
| Digital Store | Checkout, products, prices, downloads, subscriptions | Required |
| WooCommerce Bridge | Order, customer, refund, and entitlement synchronization | Required |
| Property Stewardship | Recurring service plans, invoices, one-time service fees | Required |
| Automation Engine | Stripe event triggers and failure workflows | Required |
| Admin Command Center | Revenue, MRR, subscriptions, failures, refunds | Required |
| Buyer Portal | Paid memberships, premium deal access, billing portal | Phase 2 |
| Investor Network | Membership and education access | Phase 2 |
| Document Library | Paid document packages and premium resources | Phase 2 |
| Deal Marketplace | Featured listings, subscriptions, package access | Phase 2 |
| Funding Tracker | Administrative invoice/payment references only | Phase 2 |
| PDF & Signature Engine | Payment-gated premium workflows | Optional |
| Deal Intake | Paid consultation add-ons only; core intake remains free | Optional |

## Shared service use

Plugins retrieve the service through the Platform Plugin and request a Checkout Session:

```php
$stripe = ALGQ_Stripe_Integration::instance();

$session = $stripe->create_checkout_session(
    array(
        'mode'        => 'subscription',
        'success_url' => home_url( '/account/billing-success/' ),
        'cancel_url'  => home_url( '/account/billing/' ),
        'line_items'  => array(
            array(
                'price'    => 'price_...',
                'quantity' => 1,
            ),
        ),
        'metadata'    => array(
            'algq_module' => 'property-stewardship',
            'user_id'     => get_current_user_id(),
        ),
    )
);
```

No dependent plugin may accept an arbitrary Stripe Price ID from an untrusted browser request. Products and prices must be resolved from an administrator-controlled catalog or server-side mapping.

## Security controls

- Dedicated capability: `manage_algq_stripe`.
- No secret values in HTML, JavaScript, logs, exports, or REST responses.
- Verify webhook signatures before parsing business data.
- Preserve the raw request body for verification.
- Use idempotency for both API writes and webhook processing.
- Store only Stripe object identifiers needed for reconciliation.
- Do not store card numbers, CVC values, or full payment-method details.
- Mask customer information in general audit views.
- Log access grants, revocations, refunds, and failed payments.
- Separate test and live configuration.
- Prevent live-mode activation until the webhook health check passes.

## Automation Engine events

Recommended normalized platform events:

```text
stripe.checkout.completed
stripe.payment.succeeded
stripe.payment.failed
stripe.subscription.created
stripe.subscription.updated
stripe.subscription.cancelled
stripe.invoice.paid
stripe.invoice.failed
stripe.refund.completed
```

Example automations:

- successful Digital Store payment → create entitlement and send receipt;
- Property Stewardship subscription created → activate service plan;
- invoice payment failed → notify client and create follow-up task;
- subscription cancelled → schedule access revocation at period end;
- refund completed → revoke refundable entitlement and audit the change.

## Admin Command Center metrics

- gross payment volume;
- net collected revenue;
- monthly recurring revenue;
- active subscriptions;
- trialing subscriptions;
- failed invoices;
- refunds;
- average order value;
- new customers;
- revenue by ARE module.

Financial metrics must indicate the Stripe data timestamp and whether the installation is in test or live mode.

## Deployment sequence

1. Load the shared integration from the Platform Plugin bootstrap.
2. Add the `manage_algq_stripe` capability.
3. Configure test credentials outside the database.
4. Register the centralized webhook endpoint in Stripe.
5. Test signature verification and duplicate-event handling.
6. Enable Digital Store and WooCommerce Bridge consumers.
7. Enable Property Stewardship billing.
8. Connect Automation Engine events.
9. Add Command Center reporting.
10. Complete refund, cancellation, failed-payment, and access-revocation tests.
11. Repeat all tests with live credentials before production enablement.

## Production gate

The integration is not production-ready until:

- the shared class is loaded by the Platform Plugin bootstrap;
- automated tests cover webhook signature validation and replay protection;
- API request failures return actionable `WP_Error` objects;
- entitlement creation and revocation are idempotent;
- live and test records cannot be mixed;
- customer data access is capability-protected;
- Stripe and WooCommerce are reconciled where both are active;
- no secret appears in source control or WordPress exports;
- the Command Center reports Stripe health and mode;
- a documented rollback procedure exists.
