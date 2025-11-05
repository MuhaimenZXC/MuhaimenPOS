<?php
require_once 'config/config.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats = [];

// Total products
$query = "SELECT COUNT(*) as total FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total sales today
$query = "SELECT COUNT(*) as total, SUM(total_amount) as amount FROM sales WHERE DATE(created_at) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$today = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['today_sales'] = $today['total'];
$stats['today_revenue'] = $today['amount'] ?? 0;

// Low stock products
$query = "SELECT COUNT(*) as total FROM products WHERE stock_quantity < 20";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total transactions
$query = "SELECT COUNT(*) as total FROM sales";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent sales
$query = "SELECT s.*, u.username FROM sales s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$query = "SELECT * FROM products WHERE stock_quantity < 20 ORDER BY stock_quantity ASC LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$settings = getSettings($db);
$currentTheme = getCurrentTheme();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($_SESSION['theme']); ?>">
<!-- Rest of the HTML remains the same -->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>

    <!-- Tailwind CSS with DaisyUI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.5.0/dist/full.css" rel="stylesheet" type="text/css" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-base-200">
    <!-- NAVIGATION -->
    <nav class="bg-base-100 shadow-lg sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16 items-center">
                <!-- Logo / Store Name -->
                <div class="flex items-center space-x-3">
                    <i class="fas fa-tachometer-alt text-primary text-2xl"></i>
                    <span class="text-xl font-bold text-base-content"><?php echo $settings['store_name']; ?></span>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex lg:hidden">
                    <button id="mobileMenuBtn" class="text-base-content focus:outline-none p-2">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>

                <!-- Desktop Header Info -->
                <div class="hidden lg:flex items-center space-x-4">
                    <span class="text-base-content">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- DESKTOP SIDEBAR -->
        <div id="sidebar" class="w-64 bg-base-100 shadow-lg min-h-screen lg:block hidden">
            <div class="p-4">
                <nav class="space-y-2">
                    <a href="dashboard.php" class="flex items-center p-3 text-primary bg-primary/10 rounded-lg">
                        <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                    </a>
                    <a href="pos.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-cash-register mr-3"></i>Point of Sale
                        <span id="cartBadge" class="ml-auto bg-error text-error-content text-xs font-bold px-2 py-0.5 rounded-full">0</span>
                    </a>
                    <a href="products.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-boxes mr-3"></i>Products
                    </a>
                    <a href="inventory.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-warehouse mr-3"></i>Inventory
                    </a>
                    <a href="sales_history.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-history mr-3"></i>Sales History
                    </a>
                    <a href="sales_report.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-chart-bar mr-3"></i>Sales Report
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="settings.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                            <i class="fas fa-cog mr-3"></i>Settings
                        </a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-sign-out-alt mr-3 text-error"></i>Logout
                    </a>
                </nav>
            </div>
        </div>

        <!-- MOBILE SIDEBAR -->
        <div id="mobileSidebar" class="fixed inset-0 bg-base-300/50 z-40 hidden">
            <div class="w-64 bg-base-100 h-full shadow-lg p-4">
                <button id="closeMobileSidebar" class="mb-4 text-base-content p-2">
                    <i class="fas fa-times text-2xl"></i>
                </button>
                <nav class="space-y-2">
                    <a href="dashboard.php" class="flex items-center p-3 text-primary bg-primary/10 rounded-lg">
                        <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                    </a>
                    <a href="pos.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-cash-register mr-3"></i>Point of Sale
                        <span id="mobileCartBadge" class="ml-auto bg-error text-error-content text-xs font-bold px-2 py-0.5 rounded-full">0</span>
                    </a>
                    <a href="products.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-boxes mr-3"></i>Products
                    </a>
                    <a href="inventory.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-warehouse mr-3"></i>Inventory
                    </a>
                    <a href="sales_history.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-history mr-3"></i>Sales History
                    </a>
                    <a href="sales_report.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-chart-bar mr-3"></i>Sales Report
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="settings.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                            <i class="fas fa-cog mr-3"></i>Settings
                        </a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-sign-out-alt mr-3 text-error"></i>Logout
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4 sm:p-6 md:p-8">
            <div class="mb-6 sm:mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-base-content mb-2">Dashboard</h1>
                <p class="text-base-content/70">Overview of your business performance</p>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <?php endif; ?>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div class="bg-base-100 rounded-xl shadow-md p-4 sm:p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-boxes text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-base-content/70 text-sm">Total Products</p>
                            <p class="text-2xl font-bold text-base-content"><?php echo $stats['total_products']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-base-100 rounded-xl shadow-md p-4 sm:p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-peso-sign text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-base-content/70 text-sm">Today's Revenue</p>
                            <p class="text-2xl font-bold text-base-content"><?php echo formatCurrency($stats['today_revenue']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-base-100 rounded-xl shadow-md p-4 sm:p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-shopping-cart text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-base-content/70 text-sm">Today's Sales</p>
                            <p class="text-2xl font-bold text-base-content"><?php echo $stats['today_sales']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-base-100 rounded-xl shadow-md p-4 sm:p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-exclamation-triangle text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-base-content/70 text-sm">Low Stock Alert</p>
                            <p class="text-2xl font-bold text-base-content"><?php echo $stats['low_stock']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sales and Low Stock -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <div class="bg-base-100 rounded-xl shadow-md p-4 sm:p-6">
                    <h3 class="text-lg sm:text-xl font-semibold text-base-content mb-4">
                        <i class="fas fa-clock mr-2 text-primary"></i>Recent Sales
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($recent_sales as $sale): ?>
                            <div class="flex justify-between items-center p-3 bg-base-200/50 rounded-lg">
                                <div>
                                    <p class="font-medium text-base-content"><?php echo $sale['transaction_id']; ?></p>
                                    <p class="text-sm text-base-content/70"><?php echo date('M d, Y H:i', strtotime($sale['created_at'])); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-base-content"><?php echo formatCurrency($sale['total_amount']); ?></p>
                                    <p class="text-sm text-base-content/70"><?php echo ucfirst($sale['payment_method']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="sales_history.php" class="text-primary hover:text-primary/80 font-medium">View All Sales →</a>
                    </div>
                </div>

                <div class="bg-base-100 rounded-xl shadow-md p-4 sm:p-6">
                    <h3 class="text-lg sm:text-xl font-semibold text-base-content mb-4">
                        <i class="fas fa-exclamation-triangle mr-2 text-error"></i>Low Stock Alert
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($low_stock_products as $product): ?>
                            <div class=" flex justify-between items-center p-3 bg-error/10 rounded-lg">
                                <div>
                                    <p class="font-medium text-base-content"><?php echo htmlspecialchars($product['name']); ?></p>
                                    <p class="text-sm text-base-content/70">Stock: <?php echo $product['stock_quantity']; ?> units</p>
                                </div>
                                <span class="px-2 py-1 bg-error text-error-content text-xs rounded-full">Low Stock</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="inventory.php" class="text-error hover:text-error/80 font-medium">Manage Inventory →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile navigation toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileSidebar = document.getElementById('mobileSidebar');
        const closeMobileSidebar = document.getElementById('closeMobileSidebar');

        mobileMenuBtn.addEventListener('click', () => {
            mobileSidebar.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });

        closeMobileSidebar.addEventListener('click', () => {
            mobileSidebar.classList.add('hidden');
            document.body.style.overflow = 'auto';
        });

        // Close mobile sidebar when clicking outside
        mobileSidebar.addEventListener('click', (e) => {
            if (e.target === mobileSidebar) {
                mobileSidebar.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        });

        // Update cart badge dynamically (example)
        function updateCartBadge(count) {
            document.getElementById('cartBadge').textContent = count;
            document.getElementById('mobileCartBadge').textContent = count;
        }

        // Apply theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = sessionStorage.getItem('theme') || '<?php echo $settings['theme']; ?>';
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }

            // Handle dark mode toggle if it exists
            const darkModeToggle = document.getElementById('darkMode');
            if (darkModeToggle) {
                darkModeToggle.addEventListener('change', function() {
                    if (this.checked) {
                        document.documentElement.setAttribute('data-theme', 'dark');
                        sessionStorage.setItem('theme', 'dark');
                    } else {
                        const currentTheme = '<?php echo $settings['theme']; ?>';
                        document.documentElement.setAttribute('data-theme', currentTheme);
                        sessionStorage.setItem('theme', currentTheme);
                    }
                });
            }
        });
    </script>
</body>

</html>