<?php
session_start();
echo '<pre>';
echo "=== SESSION DEBUG ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "\n=== COOKIES ===\n";
print_r($_COOKIE);
echo "\n=== SERVER ===\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo '</pre>';