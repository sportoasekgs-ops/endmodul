<?php
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $db = getDb();
        $stmt = $db->prepare('SELECT * FROM sportoase_users WHERE username = ? AND active = true');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Ungültiger Benutzername oder Passwort';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportOase - Login (Test)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="w-24 h-24 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg flex items-center justify-center text-4xl text-white">
                    🏃
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">SportOase</h1>
                <p class="text-gray-600">Test-Umgebung</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        Benutzername
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="admin, lehrer1, oder lehrer2"
                    >
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Passwort
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="test123"
                    >
                </div>
                
                <button 
                    type="submit"
                    class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-600 hover:to-indigo-700 transform hover:scale-[1.02] transition-all duration-200 shadow-lg"
                >
                    Anmelden
                </button>
            </form>
            
            <div class="mt-8 p-4 bg-blue-50 rounded-lg text-sm text-gray-700">
                <p class="font-semibold mb-2">Test-Zugangsdaten:</p>
                <ul class="space-y-1">
                    <li><strong>admin</strong> / test123 (Administrator)</li>
                    <li><strong>lehrer1</strong> / test123 (Lehrer)</li>
                    <li><strong>lehrer2</strong> / test123 (Lehrer)</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
