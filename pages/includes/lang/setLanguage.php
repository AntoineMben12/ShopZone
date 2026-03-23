<?php
session_start();

// Expected 'en', 'fr', or 'es'
$allowedLangs = ['en', 'fr', 'es'];
$requestedLang = $_GET['lang'] ?? '';

if (in_array($requestedLang, $allowedLangs)) {
    $_SESSION['lang'] = $requestedLang;
}

// Redirect the user back to the page they came from
$referer = $_SERVER['HTTP_REFERER'] ?? '/e-commerce/index.php';
header('Location: ' . $referer);
exit;
