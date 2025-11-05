<?php
require_once 'config/config.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Handle add product
                $name = $_POST['name'];
                $description = $_POST['description'];
                $category_id = $_POST['category_id'];
                $price = $_POST['price'];
                $stock_quantity = $_POST['stock_quantity'];
                $barcode = $_POST['barcode'];

                // Handle image upload
                $image_path = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $file_extension;
                    $target_path = $upload_dir . $filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                        $image_path = $target_path;
                    }
                }

                $query = "INSERT INTO products (name, description, category_id, price, stock_quantity, barcode, image_path) 
                         VALUES (:name, :description, :category_id, :price, :stock_quantity, :barcode, :image_path)";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':stock_quantity', $stock_quantity);
                $stmt->bindParam(':barcode', $barcode);
                $stmt->bindParam(':image_path', $image_path);

                if ($stmt->execute()) {
                    $message = 'Product added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding product';
                    $message_type = 'error';
                }
                break;

            case 'edit':
                // Handle edit product
                $id = $_POST['id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                $category_id = $_POST['category_id'];
                $price = $_POST['price'];
                $stock_quantity = $_POST['stock_quantity'];
                $barcode = $_POST['barcode'];

                // Handle image upload if new image provided
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $file_extension;
                    $target_path = $upload_dir . $filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                        // Get old image to delete
                        $query = "SELECT image_path FROM products WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id', $id);
                        $stmt->execute();
                        $old_product = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($old_product['image_path'] && file_exists($old_product['image_path'])) {
                            unlink($old_product['image_path']);
                        }

                        $image_path = $target_path;

                        $query = "UPDATE products SET name = :name, description = :description, category_id = :category_id, 
                                 price = :price, stock_quantity = :stock_quantity, barcode = :barcode, image_path = :image_path WHERE id = :id";
                    } else {
                        $query = "UPDATE products SET name = :name, description = :description, category_id = :category_id, 
                                 price = :price, stock_quantity = :stock_quantity, barcode = :barcode WHERE id = :id";
                    }
                } else {
                    $query = "UPDATE products SET name = :name, description = :description, category_id = :category_id, 
                             price = :price, stock_quantity = :stock_quantity, barcode = :barcode WHERE id = :id";
                }

                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':stock_quantity', $stock_quantity);
                $stmt->bindParam(':barcode', $barcode);
                if (isset($image_path)) {
                    $stmt->bindParam(':image_path', $image_path);
                }

                if ($stmt->execute()) {
                    $message = 'Product updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating product';
                    $message_type = 'error';
                }
                break;

            case 'delete':
                // Handle delete product
                $id = $_POST['id'];

                // First get the image path to delete it
                $query = "SELECT image_path FROM products WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                // Delete image file if exists
                if ($product['image_path'] && file_exists($product['image_path'])) {
                    unlink($product['image_path']);
                }

                // Delete product
                $query = "DELETE FROM products WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);

                if ($stmt->execute()) {
                    $message = 'Product deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting product';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get all products with categories
$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$settings = getSettings($db);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($_SESSION['theme']); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Products - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.5.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
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

        /* Mobile-first responsive table */
        @media (max-width: 640px) {
            .table-container {
                font-size: 0.75rem;
            }

            .table-container th,
            .table-container td {
                padding: 0.25rem 0.5rem;
            }

            .action-buttons button {
                padding: 0.25rem;
                font-size: 0.875rem;
            }

            .product-card {
                display: block;
            }

            .product-row {
                display: none;
            }

            /* Make action buttons larger and more accessible */
            .action-button {
                min-width: 44px;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
            }

            /* Improve modal for mobile */
            .modal-content {
                width: 95%;
                max-height: 90vh;
                overflow-y: auto;
            }

            /* Make form inputs larger on mobile */
            input,
            select,
            textarea {
                font-size: 16px;
                /* Prevents zoom on iOS */
            }

            /* Better spacing for mobile cards */
            .mobile-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            /* Improve barcode visibility */
            .barcode-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        @media (min-width: 641px) {
            .product-card {
                display: none;
            }

            .product-row {
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
                    <a href="products.php" class="flex items-center p-3 text-blue-600 bg-blue-50 rounded-lg">
                        <i class="fas fa-boxes mr-3"></i>Products
                    </a>
                    <a href="inventory.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
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
                    <a href="products.php" class="flex items-center p-3 text-blue-600 bg-blue-50 rounded-lg">
                        <i class="fas fa-boxes mr-3"></i>Products
                    </a>
                    <a href="inventory.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
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
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-base-content mb-1 sm:mb-2">Products Management</h1>
                    <p class="text-sm sm:text-base text-base-content/70">Manage your product catalog</p>
                </div>
                <button onclick="openAddModal()" class="bg-primary hover:bg-primary/90 text-primary-content px-4 py-2.5 rounded-lg font-medium text-sm transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-md">
                    <i class="fas fa-plus mr-2"></i>Add Product
                </button>
            </div>

            <!-- Message -->
            <?php if ($message): ?>
                <div class="mb-4 sm:mb-6 p-3 sm:p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Products Table (Desktop) -->
            <div class="bg-base-100 rounded-xl shadow-md overflow-hidden mb-6 sm:mb-0">
                <div class="overflow-x-auto table-container">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barcode</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($products as $product): ?>
                                <tr class="hover:bg-gray-50 product-row">
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <?php if ($product['image_path']): ?>
                                            <img src="<?php echo $product['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                class="w-10 h-10 sm:w-12 sm:h-12 object-cover rounded-lg">
                                        <?php else: ?>
                                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-box text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="text-xs sm:text-sm text-gray-500"><?php echo htmlspecialchars(substr($product['description'], 0, 30)); ?>...</div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo formatCurrency($product['price']); ?>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $product['stock_quantity'] <= 5 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500">
                                        <?php echo htmlspecialchars($product['barcode']); ?>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-sm font-medium action-buttons">
                                        <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                            class="text-blue-600 hover:text-blue-900 mr-1 sm:mr-2 p-1" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteProduct(<?php echo $product['id']; ?>)"
                                            class="text-red-600 hover:text-red-900 mr-1 sm:mr-2 p-1" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button onclick="printBarcode(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                            class="text-green-600 hover:text-green-900 p-1" title="Print Barcode">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Product Cards -->
            <div class="space-y-4 sm:hidden">
                <?php foreach ($products as $product): ?>
                    <div class="bg-base-100 rounded-xl shadow-md mobile-card product-card">
                        <div class="flex flex-col">
                            <!-- Product Image and Basic Info -->
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
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($product['description'], 0, 60)); ?>...</p>
                                </div>
                            </div>

                            <!-- Category and Stock -->
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                                </span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $product['stock_quantity'] <= 5 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                    Stock: <?php echo $product['stock_quantity']; ?>
                                </span>
                            </div>

                            <!-- Price -->
                            <div class="mb-3">
                                <span class="text-lg font-bold text-gray-800"><?php echo formatCurrency($product['price']); ?></span>
                            </div>

                            <!-- Barcode Section -->
                            <div class="bg-gray-50 rounded-lg p-2 mb-3">
                                <div class="flex items-center text-xs text-gray-500 mb-1">
                                    <i class="fas fa-barcode mr-1"></i>
                                    <span class="truncate"><?php echo htmlspecialchars($product['barcode']); ?></span>
                                </div>

                                <div class="barcode-container">
                                    <svg id="barcode-<?php echo $product['id']; ?>"
                                        class="w-full h-12 sm:h-16 md:h-20 lg:h-24 max-w-[200px] mx-auto"></svg>
                                </div>
                            </div>


                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-1">
                                <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                    class="flex-1 min-w-[90px] max-w-[110px] flex flex-col items-center justify-center py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs">
                                    <i class="fas fa-edit text-base mb-1"></i>
                                    <span class="font-medium">Edit</span>
                                </button>

                                <button onclick="deleteProduct(<?php echo $product['id']; ?>)"
                                    class="flex-1 min-w-[90px] max-w-[110px] flex flex-col items-center justify-center py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg text-xs">
                                    <i class="fas fa-trash text-base mb-1"></i>
                                    <span class="font-medium">Delete</span>
                                </button>

                                <button onclick="printBarcode(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                    class="flex-1 min-w-[90px] max-w-[110px] flex flex-col items-center justify-center py-2 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg text-xs">
                                    <i class="fas fa-print text-base mb-1"></i>
                                    <span class="font-medium">Print</span>
                                </button>
                            </div>


                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="app-modal fixed inset-0 bg-base-300/50 items-center justify-center z-50">
        <div class="bg-base-100 rounded-xl shadow-2xl p-4 sm:p-6 md:p-8 max-w-2xl w-full mx-2 sm:mx-4 max-h-screen overflow-y-auto modal-content">
            <div class="flex justify-between items-center mb-4 sm:mb-6">
                <h2 id="modalTitle" class="text-xl sm:text-2xl font-bold text-gray-800">Add Product</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 p-2">
                    <i class="fas fa-times text-xl sm:text-2xl"></i>
                </button>
            </div>
            <form id="productForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="productId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                        <input type="text" name="name" id="productName" required
                            class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category_id" id="productCategory" class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price *</label>
                        <input type="number" name="price" id="productPrice" step="0.01" min="0" required
                            class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
                        <input type="number" name="stock_quantity" id="productStock" min="0" required
                            class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Barcode</label>
                        <div class="flex gap-2">
                            <input type="text" name="barcode" id="productBarcode"
                                class="flex-1 px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="button" onclick="generateBarcode()"
                                class="px-3 py-2 sm:px-4 sm:py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                <i class="fas fa-barcode"></i>
                            </button>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                        <input type="file" name="image" id="productImage" accept="image/*"
                            class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <div id="imagePreview" class="mt-2 hidden">
                            <img id="previewImg" src="" alt="Preview" class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg">
                        </div>
                    </div>
                </div>

                <div class="mt-4 sm:mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="productDescription" rows="3 sm:rows-4"
                        class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter product description..."></textarea>
                </div>

                <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-4 mt-6 sm:mt-8">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 sm:px-6 sm:py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 w-full sm:w-auto">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 sm:px-6 sm:py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold w-full sm:w-auto">
                        <i class="fas fa-save mr-2"></i>Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="app-modal fixed inset-0 bg-base-300/50 items-center justify-center z-50">
        <div class="bg-base-100 rounded-xl shadow-2xl p-4 sm:p-6 md:p-8 max-w-md w-full mx-2 sm:mx-4 modal-content">
            <div class="text-center">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl sm:text-2xl"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2">Delete Product</h3>
                <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">Are you sure you want to delete this product? This action cannot be undone.</p>
            </div>

            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteProductId">

                <div class="flex flex-col sm:flex-row justify-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <button type="button" onclick="closeDeleteModal()"
                        class="px-4 py-2 sm:px-6 sm:py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 w-full sm:w-auto">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 sm:px-6 sm:py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold w-full sm:w-auto">
                        <i class="fas fa-trash mr-2"></i>Delete
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

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productForm').reset();
            document.getElementById('imagePreview').classList.add('hidden');
            document.getElementById('productModal').classList.add('show');
        }

        function printBarcode(product) {
            const printWindow = window.open(
                `barcode_print.php?id=${product.id}`,
                '_blank',
                'width=800,height=600,scrollbars=yes,resizable=yes');
        }

        function generateBarcode() {
            const barcode = 'PROD' + Date.now().toString().slice(-8);
            document.getElementById('productBarcode').value = barcode;
        }

        function editProduct(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productCategory').value = product.category_id || '';
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productStock').value = product.stock_quantity;
            document.getElementById('productBarcode').value = product.barcode || '';
            document.getElementById('productDescription').value = product.description || '';

            // Show existing image if any
            if (product.image_path) {
                document.getElementById('previewImg').src = product.image_path;
                document.getElementById('imagePreview').classList.remove('hidden');
            } else {
                document.getElementById('imagePreview').classList.add('hidden');
            }

            document.getElementById('productModal').classList.add('show');
        }

        function deleteProduct(id) {
            document.getElementById('deleteProductId').value = id;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        // Image preview
        document.getElementById('productImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        // Generate barcodes for mobile cards
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($products as $product): ?>
                JsBarcode("#barcode-<?php echo $product['id']; ?>", "<?php echo htmlspecialchars($product['barcode']); ?>", {
                    format: "CODE128",
                    width: 1,
                    height: 30,
                    displayValue: false
                });
            <?php endforeach; ?>
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === document.getElementById('productModal')) {
                closeModal();
            }
            if (e.target === document.getElementById('deleteModal')) {
                closeDeleteModal();
            }
        });
    </script>
</body>

</html>