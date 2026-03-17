<?php

define('DB_HOST',     'localhost');
define('DB_USER',     'root');       
define('DB_PASS',     '');            
define('DB_NAME',     'ecommerce_db');
define('DB_CHARSET',  'utf8mb4');

define('SITE_NAME',   'ShopZone');
define('SITE_URL',    'http://localhost/ecommerce');
define('CURRENCY',    '$');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;padding:20px;color:#c00">
                 <strong>Database connection failed:</strong><br>'
                 . htmlspecialchars($e->getMessage()) .
                 '<br><br>Check your credentials in config/database.php</div>');
        }
    }
    return $pdo;
}