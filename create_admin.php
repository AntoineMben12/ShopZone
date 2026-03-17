<?php
require_once __DIR__ . '/database/database.php';

try {
    $db = getDB();

    // Load credentials from .env
    $envFile = __DIR__ . '/.env';
    $env = file_exists($envFile) ? parse_ini_file($envFile) : [];

    $name = $env['ADMIN_NAME'] ?? 'Admin';
    $email = $env['ADMIN_EMAIL'] ?? 'admin@example.com';
    $password = $env['ADMIN_PASSWORD'] ?? 'password';
    $role = 'admin'; // Usually 'admin' or similar for admin role

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $st = $db->prepare("SELECT id FROM users WHERE email = ?");
    $st->execute([$email]);
    if ($st->fetch()) {
        $db->prepare("UPDATE users SET password = ?, role = ? WHERE email = ?")->execute([$hash, $role, $email]);
        echo "Admin user updated successfully.\n";
    } else {
        $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")->execute([$name, $email, $hash, $role]);
        echo "Admin user created successfully.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
