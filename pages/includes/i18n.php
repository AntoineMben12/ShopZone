<?php
// pages/includes/i18n.php
// Global Internationalization logic

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default to English if no language is selected
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

$langFile = __DIR__ . "/lang/{$_SESSION['lang']}.php";

// Fallback to English if file doesn't exist
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/lang/en.php";
}

$translations = require $langFile;

if (!function_exists('__')) {
    /**
     * Translates a given key based on the loaded language dictionary.
     * Returns the key itself if the translation is not found.
     */
    function __(string $key) {
        global $translations;
        return $translations[$key] ?? $key;
    }
}
