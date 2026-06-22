<?php
// FILE: includes/crm-utils.php

/**
 * Capture a new lead and handle auto-assignment
 */
function crmCaptureLead($pdo, $data) {
    $name    = $data['name'] ?? 'Unknown';
    $email   = $data['email'] ?? null;
    $phone   = $data['phone'] ?? null;
    $source  = $data['source'] ?? 'Website';
    $message = $data['message'] ?? '';
    $propId  = $data['property_id'] ?? null;

    // 1. Find or create contact
    $contactId = null;
    if ($email || $phone) {
        $stmt = $pdo->prepare("SELECT id FROM crm_contacts WHERE (email IS NOT NULL AND email = ?) OR (phone IS NOT NULL AND phone = ?)");
        $stmt->execute([$email, $phone]);
        $contactId = $stmt->fetchColumn();
    }

    if (!$contactId) {
        $stmt = $pdo->prepare("INSERT INTO crm_contacts (name, email, phone, source, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $source, $message]);
        $contactId = $pdo->lastInsertId();
    }

    // 2. Auto-assignment (Round Robin)
    $agentId = crmGetNextAgent($pdo);

    if ($agentId) {
        $pdo->prepare("UPDATE crm_contacts SET assigned_to = ? WHERE id = ?")
            ->execute([(int)$agentId, (int)$contactId]);
    }

    // 3. Create a deal in the Pipeline
    $firstStage = $pdo->query("SELECT id FROM crm_stages ORDER BY display_order ASC LIMIT 1")->fetchColumn();
    
    if ($firstStage) {
        $stmt = $pdo->prepare("INSERT INTO crm_deals (contact_id, property_id, stage_id, deal_value) VALUES (?, ?, ?, ?)");
        $val = 0;
        if ($propId) {
            $stmtProp = $pdo->prepare("SELECT price FROM properties WHERE id = ?");
            $stmtProp->execute([$propId]);
            $val = (float)$stmtProp->fetchColumn();
        }
        $stmt->execute([$contactId, $propId, $firstStage, $val]);
        $dealId = $pdo->lastInsertId();
    }

    // 4. Log initial activity
    $pdo->prepare("INSERT INTO crm_activities (contact_id, agent_id, activity_type, details) VALUES (?, ?, 'note', ?)")
        ->execute([$contactId, $agentId ?: 1, 'Inquiry received via ' . $source . ': ' . $message]);

    return $contactId;
}

/**
 * Basic Round Robin Agent Assignment
 */
function crmGetNextAgent($pdo) {
    $agents = $pdo->query("SELECT id FROM users WHERE role IN ('agent', 'admin') ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($agents)) return null;

    $lastAssigned = (int)($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'crm_last_assigned_agent_id'")->fetchColumn() ?: 0);
    
    $nextAgent = $agents[0];
    foreach ($agents as $id) {
        if ($id > $lastAssigned) {
            $nextAgent = $id;
            break;
        }
    }

    // Save using raw SQL to avoid dependency on loadSettings if possible, but we have it
    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('crm_last_assigned_agent_id', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
        ->execute([$nextAgent]);

    return $nextAgent;
}

/**
 * Log a communication activity
 */
function crmLogActivity($pdo, $contactId, $type, $details, $status = 'completed') {
    $agentId = $_SESSION['user']['id'] ?? 1;
    $stmt = $pdo->prepare("INSERT INTO crm_activities (contact_id, agent_id, activity_type, details, status) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$contactId, $agentId, $type, $details, $status]);
}
