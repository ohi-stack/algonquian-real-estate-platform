-- Algonquian Pipeline CRM 2.1.0 production migration baseline checks
-- Recorded 2026-09-13.
-- Replace `wp_` with the production WordPress table prefix before use.
-- READ-ONLY queries only. Run before any 2.1.0 -> 2.2.x migration.

-- 1. Baseline row counts.
SELECT 'deals' AS record_set, COUNT(*) AS total FROM wp_algq_deals
UNION ALL SELECT 'stage_history', COUNT(*) FROM wp_algq_deal_stage_history
UNION ALL SELECT 'notes', COUNT(*) FROM wp_algq_deal_notes
UNION ALL SELECT 'tasks', COUNT(*) FROM wp_algq_deal_tasks
UNION ALL SELECT 'activity', COUNT(*) FROM wp_algq_deal_activity
UNION ALL SELECT 'relationships', COUNT(*) FROM wp_algq_deal_relationships;

-- 2. Deal stage distribution.
SELECT stage_key, COUNT(*) AS total
FROM wp_algq_deals
WHERE deleted_at IS NULL
GROUP BY stage_key
ORDER BY stage_key;

-- 3. Active / archived / soft-deleted distribution.
SELECT
  SUM(CASE WHEN archived_at IS NULL AND deleted_at IS NULL THEN 1 ELSE 0 END) AS active_deals,
  SUM(CASE WHEN archived_at IS NOT NULL AND deleted_at IS NULL THEN 1 ELSE 0 END) AS archived_deals,
  SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS deleted_deals
FROM wp_algq_deals;

-- 4. Next-action obligations.
SELECT
  SUM(CASE WHEN next_action <> '' THEN 1 ELSE 0 END) AS deals_with_next_action,
  SUM(CASE WHEN next_action_due_at IS NOT NULL THEN 1 ELSE 0 END) AS deals_with_due_date,
  SUM(CASE WHEN next_action_due_at IS NOT NULL AND next_action_due_at < UTC_TIMESTAMP() THEN 1 ELSE 0 END) AS overdue_next_actions
FROM wp_algq_deals
WHERE deleted_at IS NULL;

-- 5. Intake / external source linkage.
SELECT
  SUM(CASE WHEN intake_submission_id IS NOT NULL THEN 1 ELSE 0 END) AS intake_linked_deals,
  SUM(CASE WHEN external_source_id IS NOT NULL AND external_source_id <> '' THEN 1 ELSE 0 END) AS external_source_linked_deals,
  SUM(CASE WHEN source_plugin <> '' THEN 1 ELSE 0 END) AS source_plugin_identified_deals
FROM wp_algq_deals;

-- 6. Deal identifier uniqueness checks. All duplicate rowsets must be empty.
SELECT uuid, COUNT(*) AS duplicate_count
FROM wp_algq_deals
GROUP BY uuid
HAVING COUNT(*) > 1;

SELECT deal_number, COUNT(*) AS duplicate_count
FROM wp_algq_deals
GROUP BY deal_number
HAVING COUNT(*) > 1;

-- 7. Deal ID and timestamp boundaries.
SELECT
  MIN(id) AS min_deal_id,
  MAX(id) AS max_deal_id,
  MIN(created_at) AS earliest_deal_created_at,
  MAX(created_at) AS latest_deal_created_at,
  MIN(updated_at) AS earliest_deal_updated_at,
  MAX(updated_at) AS latest_deal_updated_at
FROM wp_algq_deals;

-- 8. Orphan checks. Every query should return zero rows/count = 0.
SELECT COUNT(*) AS orphan_stage_history
FROM wp_algq_deal_stage_history h
LEFT JOIN wp_algq_deals d ON d.id = h.deal_id
WHERE d.id IS NULL;

SELECT COUNT(*) AS orphan_notes
FROM wp_algq_deal_notes n
LEFT JOIN wp_algq_deals d ON d.id = n.deal_id
WHERE d.id IS NULL;

SELECT COUNT(*) AS orphan_tasks
FROM wp_algq_deal_tasks t
LEFT JOIN wp_algq_deals d ON d.id = t.deal_id
WHERE d.id IS NULL;

SELECT COUNT(*) AS orphan_activity
FROM wp_algq_deal_activity a
LEFT JOIN wp_algq_deals d ON d.id = a.deal_id
WHERE d.id IS NULL;

SELECT COUNT(*) AS orphan_relationships
FROM wp_algq_deal_relationships r
LEFT JOIN wp_algq_deals d ON d.id = r.deal_id
WHERE d.id IS NULL;

-- 9. Downstream status preservation baseline.
SELECT underwriting_status, COUNT(*) AS total FROM wp_algq_deals GROUP BY underwriting_status ORDER BY underwriting_status;
SELECT offer_status, COUNT(*) AS total FROM wp_algq_deals GROUP BY offer_status ORDER BY offer_status;
SELECT contract_status, COUNT(*) AS total FROM wp_algq_deals GROUP BY contract_status ORDER BY contract_status;
SELECT buyer_status, COUNT(*) AS total FROM wp_algq_deals GROUP BY buyer_status ORDER BY buyer_status;
SELECT funding_status, COUNT(*) AS total FROM wp_algq_deals GROUP BY funding_status ORDER BY funding_status;
SELECT closing_status, COUNT(*) AS total FROM wp_algq_deals GROUP BY closing_status ORDER BY closing_status;

-- 10. Relationship type inventory.
SELECT related_type, source_plugin, COUNT(*) AS total
FROM wp_algq_deal_relationships
GROUP BY related_type, source_plugin
ORDER BY related_type, source_plugin;

-- Store/export the complete results with the deployment record before migration.
