<?php
require_once __DIR__ . '/config.php';

echo "🔧 Setting up SportOase Test Database...\n\n";

$db = getDb();

try {
    // Drop existing tables to start fresh
    echo "Dropping existing tables...\n";
    $db->exec("DROP TABLE IF EXISTS sportoase_notifications CASCADE");
    $db->exec("DROP TABLE IF EXISTS sportoase_bookings CASCADE");
    $db->exec("DROP TABLE IF EXISTS sportoase_blocked_slots CASCADE");
    $db->exec("DROP TABLE IF EXISTS sportoase_slot_names CASCADE");
    $db->exec("DROP TABLE IF EXISTS sportoase_users CASCADE");
    $db->exec("DROP TABLE IF EXISTS sportoase_system_config CASCADE");
    echo "✓ Existing tables dropped\n\n";
    
    // Create tables
    echo "Creating tables...\n";
    
    // Users table
    $db->exec("CREATE TABLE sportoase_users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        is_admin BOOLEAN DEFAULT FALSE,
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Users table created\n";
    
    // Bookings table
    $db->exec("CREATE TABLE sportoase_bookings (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES sportoase_users(id) ON DELETE CASCADE,
        date DATE NOT NULL,
        period INTEGER NOT NULL,
        weekday VARCHAR(20) NOT NULL,
        teacher_name VARCHAR(255) NOT NULL,
        teacher_class VARCHAR(100) NOT NULL,
        students_json JSON NOT NULL,
        offer_type VARCHAR(50) NOT NULL,
        offer_label VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Bookings table created\n";
    
    // Slot names table
    $db->exec("CREATE TABLE sportoase_slot_names (
        id SERIAL PRIMARY KEY,
        weekday VARCHAR(20) NOT NULL,
        period INTEGER NOT NULL,
        label VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(weekday, period)
    )");
    echo "✓ Slot names table created\n";
    
    // Blocked slots table
    $db->exec("CREATE TABLE sportoase_blocked_slots (
        id SERIAL PRIMARY KEY,
        date DATE NOT NULL,
        period INTEGER NOT NULL,
        weekday VARCHAR(20) NOT NULL,
        reason VARCHAR(255) DEFAULT 'Beratung',
        blocked_by_id INTEGER REFERENCES sportoase_users(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(date, period)
    )");
    echo "✓ Blocked slots table created\n";
    
    // Notifications table
    $db->exec("CREATE TABLE sportoase_notifications (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES sportoase_users(id) ON DELETE CASCADE,
        booking_id INTEGER REFERENCES sportoase_bookings(id) ON DELETE CASCADE,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Notifications table created\n";
    
    // System config table
    $db->exec("CREATE TABLE sportoase_system_config (
        id SERIAL PRIMARY KEY,
        config_key VARCHAR(100) UNIQUE NOT NULL,
        config_value JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ System config table created\n\n";
    
    // Insert default configuration
    echo "Inserting default configuration...\n";
    
    $periods = json_encode([
        1 => ['start' => '07:50', 'end' => '08:35'],
        2 => ['start' => '08:35', 'end' => '09:20'],
        3 => ['start' => '09:40', 'end' => '10:25'],
        4 => ['start' => '10:25', 'end' => '11:20'],
        5 => ['start' => '11:40', 'end' => '12:25'],
        6 => ['start' => '12:25', 'end' => '13:10'],
    ]);
    
    $fixedOffers = json_encode([
        'Monday' => [
            1 => 'Wochenstart-Aktivierung',
            3 => 'Konflikt-Reset & Deeskalation',
            5 => 'Koordinationszirkel'
        ],
        'Tuesday' => [],
        'Wednesday' => [
            1 => 'Sozialtraining / Gruppenreset',
            3 => 'Aktivierung Mini-Fitness',
            5 => 'Motorik-Parcours'
        ],
        'Thursday' => [
            2 => 'Konflikt-Reset',
            5 => 'Turnen + Balance'
        ],
        'Friday' => [
            2 => 'Atem & Reflexion',
            4 => 'Bodyscan Light',
            5 => 'Ruhezone / Entspannung'
        ]
    ]);
    
    $freeModules = json_encode([
        'Aktivierung',
        'Regulation / Entspannung',
        'Konflikt-Reset',
        'Egal / flexibel'
    ]);
    
    $systemSettings = json_encode([
        'max_students_per_period' => 5,
        'booking_advance_minutes' => 60
    ]);
    
    $stmt = $db->prepare("
        INSERT INTO sportoase_system_config (config_key, config_value, created_at, updated_at) VALUES
        (:key, :value, NOW(), NOW())
    ");
    
    $configs = [
        ['key' => 'periods', 'value' => $periods],
        ['key' => 'fixed_offers', 'value' => $fixedOffers],
        ['key' => 'free_modules', 'value' => $freeModules],
        ['key' => 'system_settings', 'value' => $systemSettings]
    ];
    
    foreach ($configs as $config) {
        $stmt->execute($config);
    }
    echo "✓ Default configuration inserted\n\n";
    
    // Create test users
    echo "Creating test users...\n";
    
    $password = password_hash('test123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        INSERT INTO sportoase_users (username, password, is_admin, active) VALUES
        (:username, :password, :is_admin, :active)
    ");
    
    $stmt->execute(['username' => 'admin', 'password' => $password, 'is_admin' => 't', 'active' => 't']);
    $stmt->execute(['username' => 'lehrer1', 'password' => $password, 'is_admin' => 'f', 'active' => 't']);
    $stmt->execute(['username' => 'lehrer2', 'password' => $password, 'is_admin' => 'f', 'active' => 't']);
    echo "✓ Test users created\n\n";
    
    echo "✅ Database setup complete!\n\n";
    echo "You can now login with:\n";
    echo "  - admin / test123 (Administrator)\n";
    echo "  - lehrer1 / test123 (Teacher)\n";
    echo "  - lehrer2 / test123 (Teacher)\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
