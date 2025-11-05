<?php
require_once '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // start session only if not started yet
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit();
}


$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT id, username, password, role FROM users WHERE username = :username OR email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username);
        $stmt->execute();

        // Fetch the first matching user
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Compare plain password (use password_verify() if using hashed passwords)
        if ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header('Location: ../dashboard.php');
            exit();
        } else {
            $message = 'Invalid username or password';
        }
    } else {
        $message = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>

<body class="flex items-center justify-center">
    <div class="w-full max-w-md mx-4">
        <div class="login-card rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cash-register text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">MUHAIMENZXC STORE</h1>
                <p class="text-gray-600">Point of Sale System</p>
            </div>

            <?php if ($message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Username or Email</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="username" required
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter username or email">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" required
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter password">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition duration-200 transform hover:scale-105">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm mb-2">Don't have an account?</p>
                <a href="register.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-user-plus mr-1"></i>Create New Account
                </a>
            </div>


        </div>
    </div>

    <script>
        document.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const button = document.querySelector('button[type="submit"]');
                if (button) button.click();
            }
        });
    </script>
</body>

</html>