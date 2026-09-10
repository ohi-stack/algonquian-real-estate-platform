# ARE Plugin Suite Architecture

`Deal Intake → Pipeline CRM → MAO → Offer Generator → Documents/PDF → Buyers/Marketplace → Funding → Automation → Command Center`

- Platform provides shared services, events, audit, permissions, health and integration infrastructure.
- Pipeline CRM owns the canonical Deal record.
- `deal_id` is the cross-plugin transaction anchor.
- Plugins synchronize references/events rather than duplicate authoritative records.
- `app.algonquianrealestate.com` operates through `api.algonquianrealestate.com` and the Platform service layer.
