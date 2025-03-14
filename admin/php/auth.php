<?php
// auth.php

function has_permission($admin_id, $requested_permission) {
    global $conn;
    
    // Fetch admin data
    $stmt = $conn->prepare("
        SELECT role, permissions 
        FROM admin 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $admin = $result->fetch_assoc();
    $role = $admin['role'];
    $permissions = json_decode($admin['permissions'], true) ?? [];

    // Super admin has all permissions
    if ($role === 'super_admin') {
        return true;
    }

    // Define role-based permissions
    $role_permissions = [
        'view_feedback' => ['super_admin', 'moderator'],
        'manage_users' => ['super_admin'],
        'manage_portfolios' => ['super_admin', 'moderator'],
        'view_analytics' => ['super_admin', 'moderator'],
        'manage_settings' => ['super_admin']
    ];

    // Check role-based permissions
    if (isset($role_permissions[$requested_permission])) {
        if (in_array($role, $role_permissions[$requested_permission])) {
            return true;
        }
    }

    // Check custom permissions
    if (in_array($requested_permission, $permissions)) {
        return true;
    }

    return false;
}