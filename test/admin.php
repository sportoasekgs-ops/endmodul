<?php
require_once __DIR__ . '/config.php';
requireLogin();

if (!isAdmin()) {
    die('Zugriff verweigert. Nur für Administratoren.');
}

$user = getCurrentUser();
$db = getDb();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Block slot
    if ($action === 'block_slot') {
        $date = $_POST['date'] ?? '';
        $period = (int)($_POST['period'] ?? 0);
        $reason = $_POST['reason'] ?? 'Gesperrt';
        
        if ($date && $period) {
            $stmt = $db->prepare("
                INSERT INTO sportoase_blocked_slots (slot_date, period, reason)
                VALUES (?, ?, ?)
                ON CONFLICT (slot_date, period) DO UPDATE SET reason = EXCLUDED.reason
            ");
            $stmt->execute([$date, $period, $reason]);
            header('Location: admin.php?success=slot_blocked');
            exit;
        }
    }
    
    // Unblock slot
    if ($action === 'unblock_slot') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("DELETE FROM sportoase_blocked_slots WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: admin.php?success=slot_unblocked');
            exit;
        }
    }
    
    // Delete booking
    if ($action === 'delete_booking') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("DELETE FROM sportoase_bookings WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: admin.php?success=booking_deleted');
            exit;
        }
    }
    
    // Toggle user active status
    if ($action === 'toggle_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId && $userId !== $user['id']) {
            $stmt = $db->prepare("UPDATE sportoase_users SET active = NOT active WHERE id = ?");
            $stmt->execute([$userId]);
            header('Location: admin.php?success=user_updated');
            exit;
        }
    }
    
    // Add/Update slot name
    if ($action === 'save_slot_name') {
        $date = $_POST['date'] ?? '';
        $period = (int)($_POST['period'] ?? 0);
        $customName = trim($_POST['custom_name'] ?? '');
        
        if ($date && $period && $customName) {
            $stmt = $db->prepare("
                INSERT INTO sportoase_slot_names (slot_date, period, custom_name)
                VALUES (?, ?, ?)
                ON CONFLICT (slot_date, period) DO UPDATE SET custom_name = EXCLUDED.custom_name
            ");
            $stmt->execute([$date, $period, $customName]);
            header('Location: admin.php?success=slot_name_saved');
            exit;
        }
    }
    
    // Delete slot name
    if ($action === 'delete_slot_name') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("DELETE FROM sportoase_slot_names WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: admin.php?success=slot_name_deleted');
            exit;
        }
    }
}

// Get statistics
$totalBookings = $db->query("SELECT COUNT(*) FROM sportoase_bookings")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM sportoase_users WHERE role = 'teacher'")->fetchColumn();
$blockedSlots = $db->query("SELECT COUNT(*) FROM sportoase_blocked_slots")->fetchColumn();

// Get current week bookings
$weekStart = (new DateTime('monday this week'))->format('Y-m-d');
$weekEnd = (new DateTime('sunday this week'))->format('Y-m-d');
$weekBookings = $db->prepare("SELECT COUNT(*) FROM sportoase_bookings WHERE booking_date BETWEEN ? AND ?");
$weekBookings->execute([$weekStart, $weekEnd]);
$currentWeekBookings = $weekBookings->fetchColumn();

// Get all bookings
$allBookings = $db->query("
    SELECT b.*, u.username
    FROM sportoase_bookings b
    JOIN sportoase_users u ON b.user_id = u.id
    ORDER BY b.booking_date DESC, b.period ASC
    LIMIT 50
")->fetchAll();

// Get all users
$allUsers = $db->query("
    SELECT id, username, email, role, active, created_at
    FROM sportoase_users
    ORDER BY role DESC, username
")->fetchAll();

// Get all blocked slots
$allBlockedSlots = $db->query("
    SELECT * FROM sportoase_blocked_slots
    ORDER BY slot_date DESC, period
    LIMIT 20
")->fetchAll();

// Get all slot names
$allSlotNames = $db->query("
    SELECT * FROM sportoase_slot_names
    ORDER BY slot_date DESC, period
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportOase - Admin Panel (Test)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-600 to-indigo-700 text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center text-2xl">
                        ⚙️
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">SportOase Admin-Panel</h1>
                        <p class="text-purple-100 text-sm">Test-Umgebung</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="dashboard.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                        ← Zurück zum Dashboard
                    </a>
                    <div class="text-right">
                        <p class="font-semibold"><?= htmlspecialchars($user['username']) ?></p>
                        <p class="text-sm text-purple-100">Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mb-6">
                ✓ Aktion erfolgreich durchgeführt
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-sm text-gray-600 mb-2">Gesamt Buchungen</div>
                <div class="text-3xl font-bold text-blue-600"><?= $totalBookings ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-sm text-gray-600 mb-2">Aktive Lehrer</div>
                <div class="text-3xl font-bold text-green-600"><?= $totalUsers ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-sm text-gray-600 mb-2">Gesperrte Slots</div>
                <div class="text-3xl font-bold text-orange-600"><?= $blockedSlots ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-sm text-gray-600 mb-2">Diese Woche</div>
                <div class="text-3xl font-bold text-purple-600"><?= $currentWeekBookings ?></div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button onclick="showTab('bookings')" id="tab-bookings" class="tab-btn border-b-2 border-blue-500 px-6 py-4 text-sm font-medium text-blue-600">
                        Buchungen verwalten
                    </button>
                    <button onclick="showTab('slots')" id="tab-slots" class="tab-btn border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Slots sperren
                    </button>
                    <button onclick="showTab('users')" id="tab-users" class="tab-btn border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Benutzer verwalten
                    </button>
                    <button onclick="showTab('slotnames')" id="tab-slotnames" class="tab-btn border-b-2 border-transparent px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Slot-Namen
                    </button>
                </nav>
            </div>

            <!-- Bookings Tab -->
            <div id="content-bookings" class="tab-content p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Letzte 50 Buchungen</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Datum</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Stunde</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Lehrer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Angebot</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Schüler</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Aktion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($allBookings as $booking): ?>
                                <tr>
                                    <td class="px-4 py-3 text-sm"><?= date('d.m.Y', strtotime($booking['booking_date'])) ?></td>
                                    <td class="px-4 py-3 text-sm"><?= $booking['period'] ?></td>
                                    <td class="px-4 py-3 text-sm font-medium"><?= htmlspecialchars($booking['username']) ?></td>
                                    <td class="px-4 py-3 text-sm"><?= htmlspecialchars($booking['offer_details'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php 
                                        $students = json_decode($booking['students_json'], true);
                                        echo count($students) . ' Schüler';
                                        ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <form method="POST" onsubmit="return confirm('Buchung löschen?')">
                                            <input type="hidden" name="action" value="delete_booking">
                                            <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                                            <button class="text-red-600 hover:text-red-700 font-medium">Löschen</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Slots Tab -->
            <div id="content-slots" class="tab-content hidden p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Block Slot Form -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Slot sperren</h3>
                        <form method="POST" class="bg-gray-50 p-6 rounded-lg space-y-4">
                            <input type="hidden" name="action" value="block_slot">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Datum *</label>
                                <input type="date" name="date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Stunde *</label>
                                <select name="period" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Bitte wählen...</option>
                                    <option value="1">1. Stunde (07:50 - 08:35)</option>
                                    <option value="2">2. Stunde (08:35 - 09:20)</option>
                                    <option value="3">3. Stunde (09:40 - 10:25)</option>
                                    <option value="4">4. Stunde (10:30 - 11:15)</option>
                                    <option value="5">5. Stunde (11:20 - 12:05)</option>
                                    <option value="6">6. Stunde (12:10 - 12:55)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Grund</label>
                                <input type="text" name="reason" value="Beratung" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-6 rounded-lg transition">
                                Slot sperren
                            </button>
                        </form>
                    </div>
                    
                    <!-- Blocked Slots List -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Gesperrte Slots</h3>
                        <div class="space-y-3">
                            <?php foreach ($allBlockedSlots as $slot): ?>
                                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold text-gray-800">
                                                <?= date('d.m.Y', strtotime($slot['slot_date'])) ?> - Stunde <?= $slot['period'] ?>
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                <?= htmlspecialchars($slot['reason']) ?>
                                            </div>
                                        </div>
                                        <form method="POST" onsubmit="return confirm('Sperrung aufheben?')">
                                            <input type="hidden" name="action" value="unblock_slot">
                                            <input type="hidden" name="id" value="<?= $slot['id'] ?>">
                                            <button class="text-red-600 hover:text-red-700 text-sm font-medium">
                                                Aufheben
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($allBlockedSlots)): ?>
                                <div class="text-center text-gray-500 py-8">
                                    Keine gesperrten Slots vorhanden
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Tab -->
            <div id="content-users" class="tab-content hidden p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Benutzer verwalten</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Benutzername</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">E-Mail</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Rolle</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Erstellt</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Aktion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($allUsers as $u): ?>
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium"><?= htmlspecialchars($u['username']) ?></td>
                                    <td class="px-4 py-3 text-sm"><?= htmlspecialchars($u['email']) ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>">
                                            <?= $u['role'] === 'admin' ? 'Admin' : 'Lehrer' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $u['active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                            <?= $u['active'] ? 'Aktiv' : 'Inaktiv' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if ($u['id'] !== $user['id']): ?>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="toggle_user">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button class="text-blue-600 hover:text-blue-700 font-medium">
                                                    <?= $u['active'] ? 'Deaktivieren' : 'Aktivieren' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Slot Names Tab -->
            <div id="content-slotnames" class="tab-content hidden p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Add Slot Name Form -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Slot-Namen hinzufügen</h3>
                        <form method="POST" class="bg-gray-50 p-6 rounded-lg space-y-4">
                            <input type="hidden" name="action" value="save_slot_name">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Datum *</label>
                                <input type="date" name="date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Stunde *</label>
                                <select name="period" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Bitte wählen...</option>
                                    <option value="1">1. Stunde (07:50 - 08:35)</option>
                                    <option value="2">2. Stunde (08:35 - 09:20)</option>
                                    <option value="3">3. Stunde (09:40 - 10:25)</option>
                                    <option value="4">4. Stunde (10:30 - 11:15)</option>
                                    <option value="5">5. Stunde (11:20 - 12:05)</option>
                                    <option value="6">6. Stunde (12:10 - 12:55)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                                <input type="text" name="custom_name" required placeholder="z.B. Volleyball, Fußball" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition">
                                Slot-Namen speichern
                            </button>
                        </form>
                    </div>
                    
                    <!-- Slot Names List -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Definierte Slot-Namen</h3>
                        <div class="space-y-3">
                            <?php foreach ($allSlotNames as $slotName): ?>
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold text-gray-800">
                                                <?= htmlspecialchars($slotName['custom_name']) ?>
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                <?= date('d.m.Y', strtotime($slotName['slot_date'])) ?> - Stunde <?= $slotName['period'] ?>
                                            </div>
                                        </div>
                                        <form method="POST" onsubmit="return confirm('Slot-Namen löschen?')">
                                            <input type="hidden" name="action" value="delete_slot_name">
                                            <input type="hidden" name="id" value="<?= $slotName['id'] ?>">
                                            <button class="text-red-600 hover:text-red-700 text-sm font-medium">
                                                Löschen
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($allSlotNames)): ?>
                                <div class="text-center text-gray-500 py-8">
                                    Keine Slot-Namen definiert
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active state from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Activate selected button
            const btn = document.getElementById('tab-' + tabName);
            btn.classList.remove('border-transparent', 'text-gray-500');
            btn.classList.add('border-blue-500', 'text-blue-600');
        }
    </script>
</body>
</html>
