<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Set Default Language
if (!isset($_SESSION['lang'])) { $_SESSION['lang'] = 'en'; }

// 2. Change Language via URL (?lang=rw)
if (isset($_GET['lang'])) {
    $requested_lang = $_GET['lang'];
    $allowed = ['en', 'rw', 'fr', 'sw'];
    if (in_array($requested_lang, $allowed)) {
        $_SESSION['lang'] = $requested_lang;
    }
}

// 3. Load File
require_once "languages/" . $_SESSION['lang'] . ".php";

// 4. Global Translation Helper
function __($key) {
    global $lang;
    return $lang[$key] ?? $key;
}
?>