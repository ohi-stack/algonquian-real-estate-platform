# Getting Started With the Algonquian Pipeline CRM

1. Install and activate the Platform Plugin first when available.
2. Install and activate Pipeline CRM 2.0.0.
3. Activation creates the schema, capabilities, default settings, and plugin pages.
4. Existing 1.0 `algq_deal` records are imported once and retained as legacy records.
5. Open **Pipeline CRM → Dashboard**.
6. Create a test deal or submit one through Deal Intake.
7. Verify that the deal has an `ARE-YYYY-######` deal number.
8. Move the deal only through permitted stages.
9. Confirm that stage history and recent activity record the change.
10. Test one concurrent-update conflict by opening the board in two sessions.

## Controlled lifecycle

New Intake → Contact → Preliminary Review → Underwriting → Offer Preparation → Offer Sent → Negotiation → Under Contract → Due Diligence/Funding/Buyer Distribution → Closing Scheduled → Closed → Archived.

Lost and Withdrawn are terminal operational outcomes that may be archived or reopened through controlled transitions.
