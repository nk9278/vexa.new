<?php
// File: /includes/notification_functions.php

/**
 * Creates a notification for a user.
 */
function createNotification($user_id, $title, $message, $link = null) {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, link)
        VALUES (:user_id, :title, :message, :link)
    ");
    return $stmt->execute([
        'user_id' => $user_id,
        'title' => $title,
        'message' => $message,
        'link' => $link
    ]);
}

/**
 * Logs an activity into the timeline.
 */
function logActivity($agency_id, $user_id, $action, $details = null, $project_id = null, $client_id = null, $voice_note_path = null) {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }

    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (agency_id, project_id, client_id, user_id, action, details, voice_note_path)
        VALUES (:agency_id, :project_id, :client_id, :user_id, :action, :details, :voice_note_path)
    ");
    return $stmt->execute([
        'agency_id' => $agency_id,
        'project_id' => $project_id,
        'client_id' => $client_id,
        'user_id' => $user_id,
        'action' => $action,
        'details' => $details,
        'voice_note_path' => $voice_note_path
    ]);
}

/**
 * Logs a strict audit action.
 */
function logAudit($agency_id, $user_id, $role_name, $action, $project_id = null, $client_id = null) {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = getDbConnection();
    }

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (agency_id, user_id, role_name, action, project_id, client_id, ip_address)
        VALUES (:agency_id, :user_id, :role_name, :action, :project_id, :client_id, :ip_address)
    ");
    return $stmt->execute([
        'agency_id' => $agency_id,
        'user_id' => $user_id,
        'role_name' => $role_name,
        'action' => $action,
        'project_id' => $project_id,
        'client_id' => $client_id,
        'ip_address' => $ip_address
    ]);
}
