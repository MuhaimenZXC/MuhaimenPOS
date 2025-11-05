<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
require_once 'database.php';

// Site configuration
define('SITE_NAME', 'Modern POS');
define('SITE_URL', 'http://localhost/modern-pos/');
define('UPLOAD_PATH', 'uploads/');

// Get current theme from database if not set in session
if (!isset($_SESSION['theme'])) {
    $database = new Database();
    $db = $database->getConnection();
    $settings = getSettings($db);
    $_SESSION['theme'] = $settings['theme'] ?? 'light';
}

// Theme configuration
$themes = [
    'light' => ['primary' => 'blue', 'secondary' => 'indigo'],
    'dark' => ['primary' => 'gray', 'secondary' => 'slate'],
    'cupcake' => ['primary' => 'pink', 'secondary' => 'rose'],
    'bumblebee' => ['primary' => 'yellow', 'secondary' => 'amber'],
    'emerald' => ['primary' => 'green', 'secondary' => 'teal'],
    'corporate' => ['primary' => 'blue', 'secondary' => 'gray'],
    'synthwave' => ['primary' => 'purple', 'secondary' => 'pink'],
    'retro' => ['primary' => 'orange', 'secondary' => 'amber'],
    'cyberpunk' => ['primary' => 'purple', 'secondary' => 'cyan'],
    'valentine' => ['primary' => 'pink', 'secondary' => 'rose'],
    'halloween' => ['primary' => 'orange', 'secondary' => 'purple'],
    'garden' => ['primary' => 'green', 'secondary' => 'lime'],
    'forest' => ['primary' => 'green', 'secondary' => 'emerald'],
    'aqua' => ['primary' => 'cyan', 'secondary' => 'blue'],
    'lofi' => ['primary' => 'gray', 'secondary' => 'slate'],
    'pastel' => ['primary' => 'pink', 'secondary' => 'purple'],
    'fantasy' => ['primary' => 'purple', 'secondary' => 'pink'],
    'wireframe' => ['primary' => 'gray', 'secondary' => 'zinc'],
    'black' => ['primary' => 'gray', 'secondary' => 'zinc'],
    'luxury' => ['primary' => 'purple', 'secondary' => 'gray'],
    'dracula' => ['primary' => 'purple', 'secondary' => 'pink'],
    'cmyk' => ['primary' => 'cyan', 'secondary' => 'yellow'],
    'autumn' => ['primary' => 'orange', 'secondary' => 'yellow'],
    'business' => ['primary' => 'blue', 'secondary' => 'gray'],
    'acid' => ['primary' => 'lime', 'secondary' => 'yellow'],
    'lemonade' => ['primary' => 'yellow', 'secondary' => 'orange'],
    'night' => ['primary' => 'blue', 'secondary' => 'indigo'],
    'coffee' => ['primary' => 'amber', 'secondary' => 'yellow'],
    'winter' => ['primary' => 'cyan', 'secondary' => 'blue'],
    'dim' => ['primary' => 'blue', 'secondary' => 'gray'],
    'nord' => ['primary' => 'cyan', 'secondary' => 'blue'],
    'sunset' => ['primary' => 'orange', 'secondary' => 'yellow'],
];

// Get current theme
function getCurrentTheme()
{
    global $themes;
    if (isset($_SESSION['theme']) && isset($themes[$_SESSION['theme']])) {
        return $themes[$_SESSION['theme']];
    }
    return $themes['light'];
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect if not logged in
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin()
{
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Generate unique transaction ID
function generateTransactionId()
{
    return 'TXN' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Format currency
function formatCurrency($amount)
{
    return '₱' . number_format($amount, 2);
}

// Get settings
function getSettings($db)
{
    $query = "SELECT * FROM settings WHERE id = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
