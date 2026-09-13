# /algonquian-automation-engine Production Specification

## Purpose

The **Algonquian Automation Engine** page explains and exposes the Automation Engine as the workflow-execution layer for Algonquian Real Estate. It should present automation as operational infrastructure that reduces repetitive labor, protects deadlines, improves follow-up, and advances transactions while preserving human authority over consequential decisions.

The Automation Engine owns workflow rules, triggers, conditions, scheduled actions, retries, notifications, failure handling, and execution history. It does not own Deals, underwriting results, offers, documents, buyer profiles, capital records, or closing authority.

## Recommended Page Title

Algonquian Automation Engine

## Recommended URL

`/algonquian-automation-engine/`

## Hero Copy

**Badge:** `Algonquian Real Estate Platform • Workflow Automation`

**H1:** `Algonquian Automation Engine`

**Lead:** `Automate Repetitive Work Without Losing Operational Control.`

**Supporting copy:**

> The Algonquian Automation Engine coordinates approved workflow rules, reminders, notifications, scheduled actions, transaction handoffs, deadline monitoring, and event-driven operations across the Algonquian Real Estate Platform.

Primary CTAs:

- Open Automation Rules → `#automation-rules`
- Getting Started → `#getting-started`

## Preferred Production Interfaces

New pages must use the Automation Engine's native interfaces rather than the compatibility bridge `[algq_automation_engine]`.

```text
[algq_automation_overview]
[algq_automation_rules]
[algq_automation_getting_started]
[algq_automation_docs]
```

## Required Sections

1. Hero with four summary cards:
   - Event Driven / Triggers
   - Operational / Actions
   - Controlled / Approvals
   - Recorded / Audit Trail
2. Automation Engine Overview.
3. Event → Rule → Action → Audit operating model.
4. Real Estate Automation use cases.
5. Automation Rules workspace.
6. Getting Started.
7. Automation Documentation.
8. Human Control Framework.
9. Final links to Platform Interfaces and Technology.

## Real Estate Automation Use Cases

- Seller and lead follow-up.
- Follow-up tasks and nurture reactivation.
- Inspection and due-diligence deadlines.
- Attorney/title/financing milestones.
- Deal handoffs between Intake, CRM, MAO, Offers, Documents, Funding and Closing.
- Buyer registration, NDA, opportunity-access and offer events.
- Capital requirement and funding-status monitoring.
- Missing-record, failed-action and overdue-work escalation.

## Governing Workflow Model

```text
Event
  ↓
Rule Evaluation
  ↓
Permission / Approval Check
  ↓
Permitted Action
  ↓
Audit / Outcome / Retry / Escalation
```

The Automation Engine must invoke authoritative plugin services rather than write directly into another plugin's tables.

## Human Approval Boundary

Automation may research, organize, route, remind, schedule, notify, monitor, classify, draft, and execute previously authorized administrative workflows.

Human leadership retains final authority over:

- negotiations;
- binding offers;
- contracts;
- legal decisions;
- acquisition/disposition approval;
- capital commitments;
- movement or release of funds;
- transaction approval; and
- closing.

## WPBakery Standard

```text
[vc_column_text]
[algq_automation_overview]
[/vc_column_text]
```

Use the same structure for each Automation Engine shortcode. Never use `</vc_column_text>`.

## Design Standard

Use the current ARE public-page presentation:

- full-width hero with image 6422 where available;
- deep navy `#071522` / `#0B1F33`;
- ARE blue `#0B3A63`;
- gold `#D1A54A` / `#F0D99A`;
- teal `#0F8F83` / `#36C2B4`;
- white/light operational surfaces;
- rounded cards;
- responsive layout;
- restrained operational motion;
- reduced-motion support.

## Production Acceptance Criteria

- All four native Automation shortcodes render usable interfaces or intentional empty/access/dependency states.
- No placeholder or raw shortcode text is visible.
- Rules cannot bypass capability or human-approval gates.
- Execution history records success, failure, retry and escalation states.
- Cross-plugin work goes through platform service contracts/events where available.
- Page remains informational/operational and does not claim that automation replaces professional or human transaction authority.
