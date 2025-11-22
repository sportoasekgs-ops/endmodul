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

// SportOase Configuration
define('PERIOD_TIMES', [
    1 => '07:50 - 08:35',
    2 => '08:35 - 09:20',
    3 => '09:40 - 10:25',
    4 => '10:25 - 11:20',
    5 => '11:40 - 12:25',
    6 => '12:25 - 13:10'
]);

// All available modules for booking (including fixed offers)
define('FREE_MODULES', [
    'Aktivierung',
    'Regulation / Entspannung',
    'Konflikt-Reset',
    'Turnen / flexibel',
    'Wochenstart Warm-Up'
]);

// Get all fixed offer placements from database (cached)
function getFixedOfferPlacements() {
    static $placements = null;
    
    if ($placements === null) {
        $db = getDb();
        $stmt = $db->query("SELECT weekday, period, offer_name FROM sportoase_fixed_offer_placements ORDER BY weekday, period");
        $placements = [];
        while ($row = $stmt->fetch()) {
            $placements[$row['weekday']][$row['period']] = $row['offer_name'];
        }
    }
    
    return $placements;
}

// Maximum students per period
define('MAX_STUDENTS_PER_PERIOD', 5);

// Booking advance time in minutes
define('BOOKING_ADVANCE_MINUTES', 60);

// Get custom name for an offer (from database or default)
function getOfferCustomName($offerKey) {
    static $customNames = null;
    
    if ($customNames === null) {
        $db = getDb();
        $stmt = $db->query("SELECT offer_key, custom_name FROM sportoase_fixed_offer_names");
        $customNames = [];
        while ($row = $stmt->fetch()) {
            $customNames[$row['offer_key']] = $row['custom_name'];
        }
    }
    
    return $customNames[$offerKey] ?? $offerKey;
}

// Get fixed offer KEY (original name) for a slot - for validation/comparison
function getFixedOfferKey($date, $period) {
    $dayOfWeek = (int)(new DateTime($date))->format('N'); // 1 = Monday, 7 = Sunday
    $fixedOffers = getFixedOfferPlacements();
    return $fixedOffers[$dayOfWeek][$period] ?? null;
}

// Get fixed offer DISPLAY name for a slot (with custom name if set) - for display only
function getFixedOfferDisplayName($date, $period) {
    $offerKey = getFixedOfferKey($date, $period);
    
    if ($offerKey === null) {
        return null;
    }
    
    return getOfferCustomName($offerKey);
}

// Deprecated: Use getFixedOfferKey() for validation or getFixedOfferDisplayName() for display
function getFixedOffer($date, $period) {
    return getFixedOfferDisplayName($date, $period);
}

// Check if a slot has a fixed offer
function hasFixedOffer($date, $period) {
    return getFixedOfferKey($date, $period) !== null;
}

// Check if a slot is bookable
function isSlotBookable($date, $period) {
    // Weekend check
    $dayOfWeek = (int)(new DateTime($date))->format('N');
    if ($dayOfWeek >= 6) {
        return false; // Saturday/Sunday not bookable
    }
    
    // Time advance check
    $slotDateTime = new DateTime($date);
    $periodTimes = PERIOD_TIMES;
    $timeRange = $periodTimes[$period] ?? '';
    if ($timeRange) {
        $startTime = explode(' - ', $timeRange)[0];
        $slotDateTime->setTime(
            (int)substr($startTime, 0, 2),
            (int)substr($startTime, 3, 2)
        );
        
        $now = new DateTime();
        $diffMinutes = ($slotDateTime->getTimestamp() - $now->getTimestamp()) / 60;
        
        if ($diffMinutes < BOOKING_ADVANCE_MINUTES) {
            return false; // Too close to start time
        }
    }
    
    return true;
}
