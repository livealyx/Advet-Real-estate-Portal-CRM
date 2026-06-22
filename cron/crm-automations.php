<?php
// FILE: cron/crm-automations.php
// This script should be run via Cron every hour.
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

// 1. SYSTEM MAINTENANCE: Update Overdue Tasks
$stmt = $pdo->query("UPDATE crm_tasks SET status = 'overdue' WHERE status = 'pending' AND due_date < NOW()");
$overdueCount = $stmt->rowCount();

// 2. SMART REMINDERS: Check for Stale Leads (No activity in 24h)
$stmt = $pdo->query("
    SELECT c.*, u.email as agent_email, u.name as agent_name 
    FROM crm_contacts c
    JOIN users u ON u.id = c.assigned_to 
    WHERE c.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    AND NOT EXISTS (SELECT 1 FROM crm_activities a WHERE a.contact_id = c.id AND a.activity_date > c.created_at)
");
$staleLeads = $stmt->fetchAll();

foreach ($staleLeads as $lead) {
    // In a real environment, we'd send an email/WhatsApp here.
    // For now, we log it to the activity timeline as a system alert.
    $pdo->prepare("INSERT INTO crm_activities (contact_id, agent_id, activity_type, details, status) VALUES (?, 1, 'note', ?, 'completed')")
        ->execute([$lead['id'], "SYSTEM ALERT: Lead has had no activity for over 24 hours. Please follow up immediately."]);
}

// 3. LOG CRON EXECUTION
$pdo->prepare("INSERT INTO cron_logs (task_name, status, message) VALUES (?, ?, ?)")
    ->execute(['CRM Automations', 'success', "Updated $overdueCount tasks to overdue. Alerted on " . count($staleLeads) . " stale leads."]);

echo "CRM Automations successful.";
