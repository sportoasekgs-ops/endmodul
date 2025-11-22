<?php
// Test Environment Configuration for SportOase
session_start();

// Database connection
$dbUrl = getenv('DATABASE_URL');
if ($dbUrl) {
    $dbParts = parse_url($dbUrl);
    define('DB_HOST', $dbParts['host']);
    define('DB_PORT', $dbParts['port'] ?? 5432);
    define('DB_NAME', ltrim($dbParts['path'], '/'));
    define('DB_USER', $dbParts['user']);
    define('DB_PASS', $dbParts['pass']);
} else {
    die('DATABASE_URL not configured');
}

// Connect to PostgreSQL
function getDb() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    return $pdo;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM sportoase_users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Check if user is admin
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Require admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        die('Access denied. Admin only.');
    }
}
