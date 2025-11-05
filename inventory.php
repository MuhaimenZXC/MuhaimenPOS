<?php
require_once 'config/config.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle stock update
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $product_id = $_POST['product_id'];
    $stock_quantity = $_POST['stock_quantity'];

    $query = "UPDATE products SET stock_quantity = :stock_quantity WHERE id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':stock_quantity', $stock_quantity);
    $stmt->bindParam(':product_id', $product_id);

    if ($stmt->execute()) {
        $message = 'Stock updated successfully!';
        $message_type = 'success';
    } else {
        $message = 'Error updating stock';
        $message_type = 'error';
    }
}

// Get all products with low stock filter
$low_stock_only = isset($_GET['low_stock']) && $_GET['low_stock'] === '1';

if ($low_stock_only) {
    $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock_quantity < 20 ORDER BY p.stock_quantity ASC, p.name";
} else {
    $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name";
}

$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock count
$query = "SELECT COUNT(*) as total FROM products WHERE stock_quantity < 20";
$stmt = $db->prepare($query);
$stmt->execute();
$low_stock_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$settings = getSettings($db);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($_SESSION['theme']); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inventory - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.5.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        .app-modal {
            display: none;
        }

        .app-modal.show {
            display: flex;
        }

        /* Mobile-first responsive styles */
        @media (max-width: 640px) {
            .table-container {
                font-size: 0.75rem;
            }

            .table-container th,
            .table-container td {
                padding: 0.25rem 0.5rem;
            }

            .inventory-card {
                display: block;
            }

            .inventory-row {
                display: none;
            }

            /* Stats cards responsive */
            .stats-card {
                margin-bottom: 1rem;
            }
        }

        @media (min-width: 641px) {
            .inventory-card {
                display: none;
            }

            .inventory-row {
                display: table-row;
            }
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
                    <i class="fas fa-cash-register text-primary text-2xl"></i>
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
                    <a href="dashboard.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                    </a>
                    <a href="pos.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-cash-register mr-3"></i>Point of Sale
                        <span id="cartBadge" class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">0</span>
                    </a>
                    <a href="products.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-boxes mr-3"></i>Products
                    </a>
                    <a href="inventory.php" class="flex items-center p-3 text-blue-600 bg-blue-50 rounded-lg">
                        <i class="fas fa-warehouse mr-3"></i>Inventory
                    </a>
                    <a href="sales_history.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-history mr-3"></i>Sales History
                    </a>
                    <a href="sales_report.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-chart-bar mr-3"></i>Sales Report
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="settings.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                            <i class="fas fa-cog mr-3"></i>Settings
                        </a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-sign-out-alt mr-3 text-red-500"></i>Logout
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
                    <a href="dashboard.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                    </a>
                    <a href="pos.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-cash-register mr-3"></i>Point of Sale
                        <span id="mobileCartBadge" class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">0</span>
                    </a>
                    <a href="products.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-boxes mr-3"></i>Products
                    </a>
                    <a href="inventory.php" class="flex items-center p-3 text-blue-600 bg-blue-50 rounded-lg">
                        <i class="fas fa-warehouse mr-3"></i>Inventory
                    </a>
                    <a href="sales_history.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-history mr-3"></i>Sales History
                    </a>
                    <a href="sales_report.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-chart-bar mr-3"></i>Sales Report
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="settings.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                            <i class="fas fa-cog mr-3"></i>Settings
                        </a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-sign-out-alt mr-3 text-red-500"></i>Logout
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4 sm:p-6 md:p-8">
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 mb-1 sm:mb-2">Inventory Management</h1>
                    <p class="text-sm sm:text-base text-gray-600">Monitor and manage your product stock levels</p>
                </div>
                <div class="flex flex-wrap gap-2 sm:gap-4">
                    <a href="inventory.php?low_stock=1"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-md">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Low Stock (<?php echo $low_stock_count; ?>)
                    </a>
                    <a href="inventory.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-md">
                        <i class="fas fa-list mr-2"></i>All Products
                    </a>
                </div>
            </div>

            <!-- Message -->
            <?php if ($message): ?>
                <div class="mb-4 sm:mb-6 p-3 sm:p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Inventory Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 stats-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-boxes text-xl sm:text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-sm">Total Products</p>
                            <p class="text-xl sm:text-2xl font-bold text-gray-800"><?php echo count($products); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 stats-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check-circle text-xl sm:text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-sm">In Stock</p>
                            <p class="text-xl sm:text-2xl font-bold text-gray-800"><?php echo count(array_filter($products, function ($p) {
                                                                                        return $p['stock_quantity'] >= 20;
                                                                                    })); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 stats-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-exclamation-triangle text-xl sm:text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-sm">Low Stock</p>
                            <p class="text-xl sm:text-2xl font-bold text-gray-800"><?php echo $low_stock_count; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 stats-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-times-circle text-xl sm:text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-sm">Out of Stock</p>
                            <p class="text-xl sm:text-2xl font-bold text-gray-800"><?php echo count(array_filter($products, function ($p) {
                                                                                        return $p['stock_quantity'] == 0;
                                                                                    })); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Table (Desktop) -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6 sm:mb-0">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <?php echo $low_stock_only ? 'Low Stock Products' : 'All Products'; ?>
                    </h3>
                </div>
                <div class="overflow-x-auto table-container">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($products as $product): ?>
                                <tr class="hover:bg-gray-50 inventory-row">
                                    <td class="px-4 sm:px-6 py-2 sm:py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                                                <?php if ($product['image_path']): ?>
                                                    <img src="<?php echo $product['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                        class="w-10 h-10 object-cover rounded-lg">
                                                <?php else: ?>
                                                    <i class="fas fa-box text-gray-400"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo formatCurrency($product['price']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <span class="text-lg font-semibold <?php echo $product['stock_quantity'] < 20 ? 'text-red-600' : 'text-gray-900'; ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <?php if ($product['stock_quantity'] == 0): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Out of Stock</span>
                                        <?php elseif ($product['stock_quantity'] < 20): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Low Stock</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="updateStock(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                            class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit mr-1"></i>Update Stock
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Inventory Cards -->
            <div class="space-y-4 sm:hidden">
                <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-xl shadow-md p-4 inventory-card">
                        <div class="flex items-start space-x-3 mb-3">
                            <?php if ($product['image_path']): ?>
                                <img src="<?php echo $product['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    class="w-16 h-16 object-cover rounded-lg">
                            <?php else: ?>
                                <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-box text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($product['name']); ?></h3>
                                </div>
                                <p class="text-sm text-gray-600 mt-1"><?php echo formatCurrency($product['price']); ?></p>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                                    </span>
                                    <?php if ($product['stock_quantity'] == 0): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Out of Stock</span>
                                    <?php elseif ($product['stock_quantity'] < 20): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Low Stock</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">In Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-gray-500 text-sm">Current Stock</span>
                                <div class="text-xl font-bold <?php echo $product['stock_quantity'] < 20 ? 'text-red-600' : 'text-gray-800'; ?>">
                                    <?php echo $product['stock_quantity']; ?>
                                </div>
                            </div>
                            <button onclick="updateStock(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm">
                                <i class="fas fa-edit mr-1"></i>Update Stock
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Stock Update Modal -->
    <div id="stockModal" class="app-modal fixed inset-0 bg-base-300/50 items-center justify-center z-50">
        <div class="bg-base-100 rounded-xl shadow-2xl p-4 sm:p-6 md:p-8 max-w-md w-full mx-2 sm:mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4 sm:mb-6">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Update Stock</h2>
                <button onclick="closeStockModal()" class="text-gray-400 hover:text-gray-600 p-2">
                    <i class="fas fa-times text-xl sm:text-2xl"></i>
                </button>
            </div>

            <form id="stockForm" method="POST">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="product_id" id="stockProductId">

                <div class="mb-4 sm:mb-6">
                    <div class="flex items-center mb-4">
                        <div id="stockProductImage" class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-box text-gray-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 id="stockProductName" class="text-lg font-semibold text-gray-800"></h3>
                            <p id="stockProductPrice" class="text-blue-600 font-semibold"></p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Stock</label>
                        <div id="currentStock" class="text-2xl font-bold text-gray-800"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Stock Quantity *</label>
                        <input type="number" name="stock_quantity" id="newStockQuantity" min="0" required
                            class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <button type="button" onclick="closeStockModal()"
                        class="px-4 py-2 sm:px-6 sm:py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 w-full sm:w-auto">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 sm:px-6 sm:py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold w-full sm:w-auto">
                        <i class="fas fa-save mr-2"></i>Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileSidebar = document.getElementById('mobileSidebar');
        const closeMobileSidebar = document.getElementById('closeMobileSidebar');

        mobileMenuBtn.addEventListener('click', () => mobileSidebar.classList.remove('hidden'));
        closeMobileSidebar.addEventListener('click', () => mobileSidebar.classList.add('hidden'));

        // Update cart badge dynamically (example)
        function updateCartBadge(count) {
            document.getElementById('cartBadge').textContent = count;
            document.getElementById('mobileCartBadge').textContent = count;
        }

        function updateStock(product) {
            document.getElementById('stockProductId').value = product.id;
            document.getElementById('stockProductName').textContent = product.name;
            document.getElementById('stockProductPrice').textContent = formatCurrency(product.price);
            document.getElementById('currentStock').textContent = product.stock_quantity;
            document.getElementById('newStockQuantity').value = product.stock_quantity;

            // Update product image
            const imageDiv = document.getElementById('stockProductImage');
            if (product.image_path) {
                imageDiv.innerHTML = `<img src="${product.image_path}" alt="${product.name}" class="w-16 h-16 object-cover rounded-lg">`;
            } else {
                imageDiv.innerHTML = '<i class="fas fa-box text-gray-400 text-2xl"></i>';
            }

            document.getElementById('stockModal').classList.add('show');
        }

        function closeStockModal() {
            document.getElementById('stockModal').classList.remove('show');
        }

        // Format currency helper function
        function formatCurrency(amount) {
            return '₱' + parseFloat(amount).toFixed(2);
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === document.getElementById('stockModal')) {
                closeStockModal();
            }
        });
    </script>
</body>

</html>