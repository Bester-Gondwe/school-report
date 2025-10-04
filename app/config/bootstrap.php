<?php
// Centralized bootstrap for session and database connection

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Database.php';
use App\Config\Database;

// Initialize database connection
$db = (new Database())->getConnection(); 