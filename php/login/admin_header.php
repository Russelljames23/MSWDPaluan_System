<?php
// admin_header.php

// 1. Detect session context from URL
if (isset($_GET['session_context'])) {
    session_name("SESS_" . $_GET['session_context']);
}

// 2. Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Security – verify admin login
require_once __DIR__ . "/check_session.php";

if (!isAdmin()) {
    header("Location: /index.php?error=no_access");
    exit;
}
