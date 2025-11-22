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
        
        if ($date && $period && $offer && !empty($students)) {
            // Validate module is in FREE_MODULES
            if (!in_array($offer, FREE_MODULES)) {
                $error = 'Ungültiges Modul. Bitte wählen Sie ein Modul aus der Liste.';
            }
            // Validate slot is bookable (checks: weekend, advance time - NOT fixed offers!)
            elseif (!isSlotBookable($date, $period)) {
                $error = 'Dieser Slot ist nicht buchbar (Wochenende oder zu kurz vor Beginn).';
            } 
            // CRITICAL: If slot has a fixed offer, user MUST book that specific offer (original key)
            elseif ($fixedOfferKey = getFixedOfferKey($date, $period)) {
                if ($offer !== $fixedOfferKey) {
                    $fixedOfferDisplayName = getFixedOfferDisplayName($date, $period);
                    $error = 'Dieser Slot hat ein festes Angebot (' . htmlspecialchars($fixedOfferDisplayName) . '). Sie können nur dieses Modul buchen.';
                }
            }
            
            if (!isset($error)) {
                // Check if slot is blocked by admin
                $stmt = $db->prepare("SELECT id FROM sportoase_blocked_slots WHERE slot_date = ? AND period = ?");
                $stmt->execute([$date, $period]);
                if ($stmt->fetch()) {
                    $error = 'Dieser Slot ist gesperrt.';
                }
                // Check if slot is already booked
                elseif ($stmt = $db->prepare("SELECT id FROM sportoase_bookings WHERE booking_date = ? AND period = ?")) {
                    $stmt->execute([$date, $period]);
                    if ($stmt->fetch()) {
                        $error = 'Dieser Slot ist bereits gebucht.';
                    } else {
                        // All validations passed, create booking
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
            }
        }
    }
    
    // Edit booking
    if ($action === 'edit_booking') {
        $id = (int)($_POST['id'] ?? 0);
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
        
        if ($id && $offer && !empty($students)) {
            // Validate module is in FREE_MODULES
            if (!in_array($offer, FREE_MODULES)) {
                $error = 'Ungültiges Modul. Bitte wählen Sie ein Modul aus der Liste.';
            } else {
                // Get booking to check fixed offer restriction
                $stmt = $db->prepare("SELECT booking_date, period FROM sportoase_bookings WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user['id']]);
                $booking = $stmt->fetch();
                
                if (!$booking && !$isAdminUser) {
                    $error = 'Buchung nicht gefunden oder keine Berechtigung.';
                }
                // CRITICAL: If slot has a fixed offer, user MUST edit to that specific offer only
                elseif ($booking && ($fixedOfferKey = getFixedOfferKey($booking['booking_date'], $booking['period']))) {
                    if ($offer !== $fixedOfferKey) {
                        $fixedOfferDisplayName = getFixedOfferDisplayName($booking['booking_date'], $booking['period']);
                        $error = 'Dieser Slot hat ein festes Angebot (' . htmlspecialchars($fixedOfferDisplayName) . '). Sie können nur dieses Modul bearbeiten.';
                    }
                }
                
                if (!isset($error)) {
                    try {
                        if ($isAdminUser) {
                            $stmt = $db->prepare("UPDATE sportoase_bookings SET offer_details = ?, students_json = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                            $stmt->execute([
                                $offer,
                                json_encode($students),
                                $id
                            ]);
                        } else {
                            $stmt = $db->prepare("UPDATE sportoase_bookings SET offer_details = ?, students_json = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
                            $stmt->execute([
                                $offer,
                                json_encode($students),
                                $id,
                                $user['id']
                            ]);
                        }
                        header('Location: dashboard.php?success=booking_updated');
                        exit;
                    } catch (PDOException $e) {
                        $error = 'Fehler beim Aktualisieren der Buchung: ' . $e->getMessage();
                    }
                }
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
    WHERE b.booking_date BETWEEN ? AND ?
    ORDER BY b.booking_date, b.period
");
$bookingsStmt->execute([$weekStart, $weekEnd]);
$bookings = $bookingsStmt->fetchAll();

// Get blocked slots
$blockedStmt = $db->prepare("
    SELECT * FROM sportoase_blocked_slots
    WHERE slot_date BETWEEN ? AND ?
");
$blockedStmt->execute([$weekStart, $weekEnd]);
$blockedSlots = $blockedStmt->fetchAll();

// Build matrices
$schedule = [];
foreach ($bookings as $booking) {
    $key = $booking['booking_date'] . '_' . $booking['period'];
    $schedule[$key] = $booking;
}

$blocked = [];
foreach ($blockedSlots as $slot) {
    $key = $slot['slot_date'] . '_' . $slot['period'];
    $blocked[$key] = $slot;
}

// Time periods from config
$periods = PERIOD_TIMES;
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
                                    
                                    // Get data for this slot
                                    $booking = $schedule[$key] ?? null;
                                    $isBlocked = isset($blocked[$key]);
                                    $fixedOfferKey = getFixedOfferKey($dateStr, $periodNum);
                                    $fixedOfferDisplay = getFixedOfferDisplayName($dateStr, $periodNum);
                                    $hasFixedOffer = $fixedOfferKey !== null;
                                    
                                    // Calculate bookability: NOT blocked AND passes time/weekend checks
                                    $bookable = !$isBlocked && isSlotBookable($dateStr, $periodNum);
                                    ?>
                                    <td class="px-2 py-2">
                                        <?php if ($isBlocked): ?>
                                            <!-- Gesperrter Slot - NICHT buchbar -->
                                            <div class="bg-orange-100 border border-orange-300 rounded-lg p-3 text-center">
                                                <div class="text-sm font-semibold text-orange-700">🔒 Gesperrt</div>
                                                <div class="text-xs text-orange-600 mt-1"><?= htmlspecialchars($blocked[$key]['reason']) ?></div>
                                            </div>
                                        <?php elseif ($booking): ?>
                                            <!-- Bereits gebucht -->
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                                <div class="text-sm font-semibold text-blue-700 mb-2">
                                                    <?= htmlspecialchars($booking['teacher_username']) ?>
                                                </div>
                                                <div class="text-xs text-gray-600 mb-1">
                                                    📌 <?= htmlspecialchars($booking['offer_details'] ?? 'Sportangebot') ?>
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
                                                    <div class="mt-2 flex gap-2">
                                                        <button 
                                                            onclick="openEditModal(<?= $booking['id'] ?>, '<?= htmlspecialchars($booking['offer_details'], ENT_QUOTES) ?>', <?= htmlspecialchars(json_encode($students), ENT_QUOTES) ?>)"
                                                            class="text-xs text-blue-600 hover:text-blue-700 underline"
                                                        >
                                                            Bearbeiten
                                                        </button>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Buchung löschen?')">
                                                            <input type="hidden" name="action" value="delete_booking">
                                                            <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                                                            <button class="text-xs text-red-600 hover:text-red-700 underline">
                                                                Löschen
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($bookable && $hasFixedOffer): ?>
                                            <!-- Slot mit festem Angebot - BUCHBAR mit vorausgewähltem Modul! -->
                                            <button 
                                                onclick="openBookingModal('<?= $dateStr ?>', <?= $periodNum ?>, '<?= htmlspecialchars($fixedOfferKey, ENT_QUOTES) ?>')"
                                                class="w-full bg-yellow-50 hover:bg-yellow-100 border border-yellow-300 rounded-lg p-3 text-sm transition"
                                            >
                                                <div class="font-semibold text-yellow-800">⭐ <?= htmlspecialchars($fixedOfferDisplay) ?></div>
                                                <div class="text-xs text-yellow-700 mt-1">+ Buchen</div>
                                            </button>
                                        <?php elseif ($bookable): ?>
                                            <!-- Freier Slot - buchbar -->
                                            <button 
                                                onclick="openBookingModal('<?= $dateStr ?>', <?= $periodNum ?>)"
                                                class="w-full bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-3 text-sm text-green-700 font-medium transition"
                                            >
                                                + Buchen
                                            </button>
                                        <?php else: ?>
                                            <div class="bg-gray-100 border border-gray-300 rounded-lg p-3 text-center">
                                                <div class="text-xs text-gray-500">Nicht verfügbar</div>
                                            </div>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Modul / Aktivität *</label>
                    <select name="offer" id="modal_offer" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Modul wählen --</option>
                        <?php foreach (FREE_MODULES as $module): ?>
                            <option value="<?= htmlspecialchars($module) ?>"><?= htmlspecialchars($module) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p id="fixed_offer_notice" class="hidden mt-2 text-sm text-yellow-700 bg-yellow-50 border border-yellow-300 rounded px-3 py-2">
                        ℹ️ <strong>Festes Angebot:</strong> Beim Klick auf ein festes Angebot können Sie nur dieses Modul buchen.
                    </p>
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

    <!-- Edit Booking Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <form method="POST" class="p-8">
                <input type="hidden" name="action" value="edit_booking">
                <input type="hidden" name="id" id="edit_booking_id">
                
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Buchung bearbeiten</h2>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Modul / Aktivität *</label>
                    <select name="offer" id="edit_offer" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Modul wählen --</option>
                        <?php foreach (FREE_MODULES as $module): ?>
                            <option value="<?= htmlspecialchars($module) ?>"><?= htmlspecialchars($module) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-4">Schüler (mindestens 1, maximal 5) *</label>
                    <div class="space-y-3" id="edit_students_container">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="flex gap-3">
                                <input type="text" name="student<?= $i ?>_name" id="edit_student<?= $i ?>_name" placeholder="Name des Schülers" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" <?= $i === 1 ? 'required' : '' ?>>
                                <input type="text" name="student<?= $i ?>_class" id="edit_student<?= $i ?>_class" placeholder="Klasse" class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" <?= $i === 1 ? 'required' : '' ?>>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition">
                        Änderungen speichern
                    </button>
                    <button type="button" onclick="closeEditModal()" class="px-6 py-3 text-gray-600 hover:text-gray-800 transition">
                        Abbrechen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openBookingModal(date, period, fixedOffer = null) {
            document.getElementById('modal_date').value = date;
            document.getElementById('modal_period').value = period;
            
            const offerSelect = document.getElementById('modal_offer');
            const fixedOfferNotice = document.getElementById('fixed_offer_notice');
            
            // Remove any existing hidden field for fixed offer
            const existingHidden = document.getElementById('fixed_offer_hidden');
            if (existingHidden) {
                existingHidden.remove();
            }
            
            // If fixed offer is provided, pre-select it and make it readonly
            if (fixedOffer) {
                offerSelect.value = fixedOffer;
                offerSelect.disabled = true;
                offerSelect.classList.add('bg-yellow-50', 'border-yellow-300', 'cursor-not-allowed');
                fixedOfferNotice.classList.remove('hidden');
                
                // Add hidden field to ensure value is submitted even when select is disabled
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'offer';
                hiddenInput.value = fixedOffer;
                hiddenInput.id = 'fixed_offer_hidden';
                offerSelect.parentNode.appendChild(hiddenInput);
            } else {
                // Reset to normal state
                offerSelect.value = '';
                offerSelect.disabled = false;
                offerSelect.classList.remove('bg-yellow-50', 'border-yellow-300', 'cursor-not-allowed');
                fixedOfferNotice.classList.add('hidden');
            }
            
            document.getElementById('bookingModal').classList.remove('hidden');
        }
        
        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }
        
        function openEditModal(id, offer, students) {
            document.getElementById('edit_booking_id').value = id;
            document.getElementById('edit_offer').value = offer;
            
            // Clear all student fields first
            for (let i = 1; i <= 5; i++) {
                document.getElementById('edit_student' + i + '_name').value = '';
                document.getElementById('edit_student' + i + '_class').value = '';
            }
            
            // Fill in existing students
            students.forEach((student, index) => {
                const num = index + 1;
                if (num <= 5) {
                    document.getElementById('edit_student' + num + '_name').value = student.name;
                    document.getElementById('edit_student' + num + '_class').value = student.class;
                }
            });
            
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        // Close modals on outside click
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBookingModal();
            }
        });
        
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
