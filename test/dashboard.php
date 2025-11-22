<?php
require_once __DIR__ . '/config.php';
requireLogin();

$user = getCurrentUser();
$isAdminUser = isAdmin();
$db = getDb();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Create booking
    if ($action === 'create_booking') {
        $date = $_POST['date'] ?? '';
        $period = (int)($_POST['period'] ?? 0);
        $offer = $_POST['offer'] ?? '';
        $students = [];
        
        // Collect students
        for ($i = 1; $i <= 5; $i++) {
            $name = trim($_POST["student{$i}_name"] ?? '');
            $class = trim($_POST["student{$i}_class"] ?? '');
            if ($name && $class) {
                $students[] = ['name' => $name, 'class' => $class];
            }
        }
        
        if ($date && $period && !empty($students)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO sportoase_bookings (user_id, booking_date, period, teacher_name, students_json, offer_details)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'],
                    $date,
                    $period,
                    $user['username'],
                    json_encode($students),
                    $offer
                ]);
                
                header('Location: dashboard.php?success=booking_created');
                exit;
            } catch (PDOException $e) {
                $error = 'Fehler beim Erstellen der Buchung: ' . $e->getMessage();
            }
        }
    }
    
    // Delete booking
    if ($action === 'delete_booking') {
        $id = (int)($_POST['id'] ?? 0);
        if ($isAdminUser) {
            $stmt = $db->prepare("DELETE FROM sportoase_bookings WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $db->prepare("DELETE FROM sportoase_bookings WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
        }
        header('Location: dashboard.php?success=booking_deleted');
        exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get current week
$weekOffset = (int)($_GET['week'] ?? 0);
$monday = new DateTime();
$monday->modify('monday this week');
if ($weekOffset !== 0) {
    $monday->modify($weekOffset > 0 ? "+{$weekOffset} week" : "{$weekOffset} week");
}

// Get all bookings for the week
$weekStart = $monday->format('Y-m-d');
$weekEnd = (clone $monday)->modify('+6 days')->format('Y-m-d');

$bookingsStmt = $db->prepare("
    SELECT b.*, u.username as teacher_username
    FROM sportoase_bookings b
    JOIN sportoase_users u ON b.user_id = u.id
    WHERE b.date BETWEEN ? AND ?
    ORDER BY b.date, b.period
");
$bookingsStmt->execute([$weekStart, $weekEnd]);
$bookings = $bookingsStmt->fetchAll();

// Get blocked slots
$blockedStmt = $db->prepare("
    SELECT * FROM sportoase_blocked_slots
    WHERE date BETWEEN ? AND ?
");
$blockedStmt->execute([$weekStart, $weekEnd]);
$blockedSlots = $blockedStmt->fetchAll();

// Build matrices
$schedule = [];
foreach ($bookings as $booking) {
    $key = $booking['date'] . '_' . $booking['period'];
    $schedule[$key] = $booking;
}

$blocked = [];
foreach ($blockedSlots as $slot) {
    $key = $slot['date'] . '_' . $slot['period'];
    $blocked[$key] = $slot;
}

// Time periods
$periods = [
    1 => '07:50 - 08:35',
    2 => '08:35 - 09:20',
    3 => '09:40 - 10:25',
    4 => '10:25 - 11:20',
    5 => '11:40 - 12:25',
    6 => '12:25 - 13:10'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportOase - Dashboard (Test)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center text-2xl">
                        🏃
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">SportOase</h1>
                        <p class="text-blue-100 text-sm">Test-Umgebung</p>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <?php if ($isAdminUser): ?>
                        <a href="admin.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                            ⚙️ Admin-Panel
                        </a>
                    <?php endif; ?>
                    <div class="text-right">
                        <p class="font-semibold"><?= htmlspecialchars($user['username']) ?></p>
                        <p class="text-sm text-blue-100"><?= $user['role'] === 'admin' ? 'Administrator' : 'Lehrer' ?></p>
                    </div>
                    <a href="?logout" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                        Abmelden
                    </a>
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

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Week Navigation -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center">
                <a href="?week=<?= $weekOffset - 1 ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    ← Vorherige Woche
                </a>
                <h2 class="text-xl font-bold text-gray-800">
                    📅 Woche vom <?= $monday->format('d.m.Y') ?>
                </h2>
                <a href="?week=<?= $weekOffset + 1 ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Nächste Woche →
                </a>
            </div>
        </div>

        <!-- Schedule Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Stunde</th>
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <?php 
                                $day = (clone $monday)->modify("+{$i} days");
                                $dayName = ['Mo', 'Di', 'Mi', 'Do', 'Fr'][$i];
                                ?>
                                <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">
                                    <?= $dayName ?><br>
                                    <span class="text-xs text-gray-500"><?= $day->format('d.m.') ?></span>
                                </th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($periods as $periodNum => $time): ?>
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-700">
                                    <?= $periodNum ?>. Stunde<br>
                                    <span class="text-xs text-gray-500"><?= $time ?></span>
                                </td>
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <?php 
                                    $day = (clone $monday)->modify("+{$i} days");
                                    $dateStr = $day->format('Y-m-d');
                                    $key = $dateStr . '_' . $periodNum;
                                    $booking = $schedule[$key] ?? null;
                                    $isBlocked = isset($blocked[$key]);
                                    ?>
                                    <td class="px-2 py-2">
                                        <?php if ($isBlocked): ?>
                                            <div class="bg-orange-100 border border-orange-300 rounded-lg p-3 text-center">
                                                <div class="text-sm font-semibold text-orange-700">🔒 Gesperrt</div>
                                                <div class="text-xs text-orange-600 mt-1"><?= htmlspecialchars($blocked[$key]['reason']) ?></div>
                                            </div>
                                        <?php elseif ($booking): ?>
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                                <div class="text-sm font-semibold text-blue-700 mb-2">
                                                    <?= htmlspecialchars($booking['teacher_username']) ?>
                                                </div>
                                                <div class="text-xs text-gray-600 mb-1">
                                                    <?= htmlspecialchars($booking['offer_details'] ?? 'Sportangebot') ?>
                                                </div>
                                                <div class="text-xs text-gray-600 space-y-1">
                                                    <?php 
                                                    $students = json_decode($booking['students_json'], true);
                                                    foreach ($students as $student):
                                                    ?>
                                                        <div>• <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['class']) ?>)</div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if ($isAdminUser || $booking['user_id'] == $user['id']): ?>
                                                    <form method="POST" class="mt-2" onsubmit="return confirm('Buchung löschen?')">
                                                        <input type="hidden" name="action" value="delete_booking">
                                                        <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                                                        <button class="text-xs text-red-600 hover:text-red-700 underline">
                                                            Löschen
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <button 
                                                onclick="openBookingModal('<?= $dateStr ?>', <?= $periodNum ?>)"
                                                class="w-full bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-3 text-sm text-green-700 font-medium transition"
                                            >
                                                + Buchen
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <form method="POST" class="p-8">
                <input type="hidden" name="action" value="create_booking">
                <input type="hidden" name="date" id="modal_date">
                <input type="hidden" name="period" id="modal_period">
                
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Neue Buchung erstellen</h2>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Angebot / Aktivität *</label>
                    <input type="text" name="offer" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="z.B. Fußball, Basketball, Volleyball">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-4">Schüler (mindestens 1, maximal 5) *</label>
                    <div class="space-y-3">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="flex gap-3">
                                <input type="text" name="student<?= $i ?>_name" placeholder="Name des Schülers" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" <?= $i === 1 ? 'required' : '' ?>>
                                <input type="text" name="student<?= $i ?>_class" placeholder="Klasse" class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" <?= $i === 1 ? 'required' : '' ?>>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition">
                        Buchung erstellen
                    </button>
                    <button type="button" onclick="closeBookingModal()" class="px-6 py-3 text-gray-600 hover:text-gray-800 transition">
                        Abbrechen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openBookingModal(date, period) {
            document.getElementById('modal_date').value = date;
            document.getElementById('modal_period').value = period;
            document.getElementById('bookingModal').classList.remove('hidden');
        }
        
        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }
        
        // Close modal on outside click
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBookingModal();
            }
        });
    </script>
</body>
</html>
