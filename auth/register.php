<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    header('Location: ../dashboard.php');
    exit();
}

$message = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'cashier';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $message = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        // Check if username or email already exists
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $message = 'Username or email already exists';
        } else {
            // Store plain text password (not recommended for production)
            $plain_password = $password;

            // Insert new user
            $query = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $plain_password);
            $stmt->bindParam(':role', $role);

            if ($stmt->execute()) {
                $message = 'Registration successful! You can now login.';
                // Redirect to login after 2 seconds
                echo '<script>setTimeout(function() { window.location.href = "login.php"; }, 2000);</script>';
            } else {
                $message = 'Error creating account. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .register-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>

<body class="flex items-center justify-center">
    <div class="w-full max-w-md mx-4">
        <div class="register-card rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-plus text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">MUHAIMENZXC</h1>
                <p class="text-gray-600">Create New Account</p>
            </div>

            <?php if ($message): ?>
                <div class="bg-<?php echo strpos($message, 'successful') !== false ? 'green' : 'red'; ?>-100 border border-<?php echo strpos($message, 'successful') !== false ? 'green' : 'red'; ?>-400 text-<?php echo strpos($message, 'successful') !== false ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Username *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="username" required
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter username">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" required
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter email">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" required
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter password">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Confirm Password *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="confirm_password" required
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Confirm password">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                    <select name="role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="cashier">Cashier</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition duration-200 transform hover:scale-105">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm mb-2">Already have an account?</p>
                <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-sign-in-alt mr-1"></i>Login Here
                </a>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const button = document.querySelector('button[type="submit"]');
                if (button) {
                    button.click();
                }
            }
        });
    </script>
</body>

</html>