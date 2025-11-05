<?php
require_once 'config/config.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();


// Handle search and filter
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(s.transaction_id LIKE :search OR u.username LIKE :search2)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
}

if ($date_from) {
    $where_conditions[] = "DATE(s.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(s.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get sales with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$query = "SELECT s.*, u.username FROM sales s 
          LEFT JOIN users u ON s.user_id = u.id 
          $where_clause 
          ORDER BY s.created_at DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM sales s LEFT JOIN users u ON s.user_id = u.id $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_sales = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_sales / $per_page);

$settings = getSettings($db);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($_SESSION['theme']); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sales History - <?php echo SITE_NAME; ?></title>
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

            .sales-card {
                display: block;
            }

            .sales-row {
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

            /* Compact search and filter for mobile */
            .compact-search {
                padding: 0.4rem;
                margin-bottom: 0.5rem;
                width: 75%;
            }

            .compact-search .form-label {
                font-size: 0.6rem;
                margin-bottom: 0.15rem;
            }

            .compact-search .form-input {
                padding: 0.25rem 0.3rem;
                font-size: 0.7rem;
                height: 1.8rem;
            }

            .compact-search .search-button {
                padding: 0.25rem 0.3rem;
                font-size: 0.7rem;
                height: 1.8rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .compact-search .clear-filters {
                font-size: 0.6rem;
                margin-top: 0.15rem;
            }

            /* Stacked search layout with 60% width, left-aligned */
            .search-container {
                display: flex;
                flex-direction: column;
                gap: 0.3rem;
                align-items: flex-start;
            }

            .search-row,
            .date-row,
            .button-row {
                width: 100%;
                box-sizing: border-box;
                overflow: hidden;
            }

            .search-input-container,
            .date-input-container,
            .search-button-container {
                width: 100%;
            }

            /* Adjust button text and icon size */
            .search-button i {
                font-size: 0.65rem;
                margin-right: 0.2rem;
            }

            /* Mobile payment method badge */
            .mobile-payment-badge {
                display: inline-block;
                padding: 0.4rem 0.8rem;
                border-radius: 1rem;
                font-weight: 600;
                font-size: 0.8rem;
                margin-top: 0.5rem;
            }

            .payment-cash {
                background-color: #dcfce7;
                color: #166534;
            }

            .payment-card {
                background-color: #dbeafe;
                color: #1d4ed8;
            }

            .payment-other {
                background-color: #f3e8ff;
                color: #7e22ce;
            }
        }

        @media (min-width: 641px) {
            .sales-card {
                display: none;
            }

            .sales-row {
                display: table-row;
            }

            /* Desktop search layout */
            .compact-search {
                width: 100%;
                padding: 1.5rem;
            }

            .search-container {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr 1fr;
                gap: 1rem;
            }

            .search-row {
                grid-column: span 1;
            }

            .date-row {
                grid-column: span 2;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .button-row {
                grid-column: span 1;
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
                    <a href="inventory.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-warehouse mr-3"></i>Inventory
                    </a>
                    <a href="sales_history.php" class="flex items-center p-3 text-blue-600 bg-blue-50 rounded-lg">
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
                    <a href="inventory.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-warehouse mr-3"></i>Inventory
                    </a>
                    <a href="sales_history.php" class="flex items-center p-3 text-blue-600 bg-blue-50 rounded-lg">
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
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 mb-1 sm:mb-2">Sales History</h1>
                    <p class="text-sm sm:text-base text-gray-600">View and search through all transactions</p>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6 compact-search">
                <form method="GET" class="search-container">
                    <div class="search-row">
                        <div class="search-input-container">
                            <label class="block text-sm font-medium text-gray-700 mb-2 form-label">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="ID or cashier"
                                class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent form-input">
                        </div>
                    </div>
                    <div class="button-row">
                        <div class="search-button-container">
                            <label class="block text-sm font-medium text-gray-700 mb-2 form-label">&nbsp;</label>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-md search-button">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>

                    <div class="date-row">
                        <div class="date-input-container">
                            <label class="block text-sm font-medium text-gray-700 mb-2 form-label">From</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
                                class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent form-input">
                        </div>
                    </div>
                    <div class="date-row">
                        <div class="date-input-container">
                            <label class="block text-sm font-medium text-gray-700 mb-2 form-label">To</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                                class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent form-input">
                        </div>
                    </div>
                </form>
                <?php if ($search || $date_from || $date_to): ?>
                    <div class="mt-4 clear-filters">
                        <a href="sales_history.php" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                            <i class="fas fa-times mr-2"></i>Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sales Table (Desktop) -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6 sm:mb-0">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Transactions (<?php echo number_format($total_sales); ?> total)
                    </h3>
                </div>
                <div class="overflow-x-auto table-container">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($sales as $sale): ?>
                                <tr class="hover:bg-gray-50 sales-row">
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['transaction_id']); ?></div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($sale['created_at'])); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo date('H:i:s', strtotime($sale['created_at'])); ?></div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($sale['username'] ?? 'Unknown'); ?></div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900"><?php echo formatCurrency($sale['total_amount']); ?></div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $sale['payment_method'] === 'cash' ? 'bg-green-100 text-green-800' : ($sale['payment_method'] === 'card' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'); ?>">
                                            <?php echo ucfirst($sale['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-sm font-medium action-buttons">
                                        <button onclick="viewSaleDetails(<?php echo $sale['id']; ?>)"
                                            class="text-blue-600 hover:text-blue-900 mr-1 sm:mr-2 p-1" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="printReceipt('<?php echo $sale['transaction_id']; ?>')"
                                            class="text-green-600 hover:text-green-900 p-1" title="Print">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Sales Cards -->
            <div class="space-y-4 sm:hidden">
                <?php foreach ($sales as $sale): ?>
                    <div class="bg-white rounded-xl shadow-md mobile-card sales-card card-hover">
                        <div class="flex flex-col">
                            <!-- Transaction Info -->
                            <div class="flex items-start space-x-3 mb-3">
                                <div class="flex-1">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($sale['transaction_id']); ?></h3>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php echo $sale['payment_method'] === 'cash' ? 'bg-green-100 text-green-800' : ($sale['payment_method'] === 'card' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'); ?>">
                                            <?php echo ucfirst($sale['payment_method']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($sale['created_at'])); ?></p>
                                    <p class="text-sm text-gray-600">Cashier: <?php echo htmlspecialchars($sale['username'] ?? 'Unknown'); ?></p>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="mb-3">
                                <span class="text-gray-500 text-sm">Total Amount</span>
                                <div class="text-xl font-bold text-gray-800"><?php echo formatCurrency($sale['total_amount']); ?></div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-1">
                                <button onclick="viewSaleDetails(<?php echo $sale['id']; ?>)"
                                    class="flex-1 min-w-[90px] max-w-[110px] flex flex-col items-center justify-center py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs">
                                    <i class="fas fa-eye text-base mb-1"></i>
                                    <span class="font-medium">View</span>
                                </button>
                                <button onclick="printReceipt('<?php echo $sale['transaction_id']; ?>')"
                                    class="flex-1 min-w-[90px] max-w-[110px] flex flex-col items-center justify-center py-2 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg text-xs">
                                    <i class="fas fa-print text-base mb-1"></i>
                                    <span class="font-medium">Print</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-6 sm:mt-8 flex justify-center">
                    <nav class="flex flex-wrap justify-center">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"
                                class="px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        // Show limited page numbers on mobile
                        $start_page = max(1, $page - 1);
                        $end_page = min($total_pages, $page + 1);

                        // Always show first page
                        if ($start_page > 1) {
                        ?>
                            <a href="?page=1&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"
                                class="px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 <?php echo 1 === $page ? 'bg-blue-600 text-white' : ''; ?>">
                                1
                            </a>
                            <?php if ($start_page > 2) { ?>
                                <span class="px-2 py-2 text-gray-500">...</span>
                            <?php }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"
                                class="px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg <?php echo $i === $page ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php
                        // Always show last page
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) { ?>
                                <span class="px-2 py-2 text-gray-500">...</span>
                            <?php }
                            ?>
                            <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"
                                class="px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 <?php echo $total_pages === $page ? 'bg-blue-600 text-white' : ''; ?>">
                                <?php echo $total_pages; ?>
                            </a>
                        <?php
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"
                                class="px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sale Details Modal -->
    <div id="saleModal" class="app-modal fixed inset-0 bg-base-300/50 items-center justify-center z-50">
        <div class="bg-base-100 rounded-xl shadow-2xl p-4 sm:p-6 md:p-8 max-w-4xl w-full mx-2 sm:mx-4 max-h-screen overflow-y-auto modal-content">
            <div class="flex justify-between items-center mb-4 sm:mb-6">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Sale Details</h2>
                <button onclick="closeSaleModal()" class="text-gray-400 hover:text-gray-600 p-2">
                    <i class="fas fa-times text-xl sm:text-2xl"></i>
                </button>
            </div>

            <div id="saleDetailsContent">
                <!-- Content will be loaded here -->
            </div>
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

        function viewSaleDetails(saleId) {
            // Fetch sale details
            fetch(`api/get_sale_details.php?id=${saleId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySaleDetails(data.sale);
                    } else {
                        alert('Error loading sale details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading sale details');
                });
        }

        function displaySaleDetails(sale) {
            // Determine payment method class for mobile
            let paymentClass = 'payment-other';
            if (sale.payment_method === 'cash') {
                paymentClass = 'payment-cash';
            } else if (sale.payment_method === 'card') {
                paymentClass = 'payment-card';
            }

            const content = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-2">Transaction Info</h3>
                        <p><strong>ID:</strong> ${sale.transaction_id}</p>
                        <p><strong>Date:</strong> ${new Date(sale.created_at).toLocaleDateString()}</p>
                        <p><strong>Time:</strong> ${new Date(sale.created_at).toLocaleTimeString()}</p>
                        <p><strong>Cashier:</strong> ${sale.username || 'Unknown'}</p>
                        <!-- Mobile Payment Method Badge -->
                        <div class="sm:hidden">
                            <span class="mobile-payment-badge ${paymentClass}">
                                <i class="fas ${sale.payment_method === 'cash' ? 'fa-money-bill-wave' : (sale.payment_method === 'card' ? 'fa-credit-card' : 'fa-wallet')} mr-1"></i>
                                ${sale.payment_method.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-2">Payment Info</h3>
                        <p><strong>Method:</strong> ${sale.payment_method.toUpperCase()}</p>
                        <p><strong>Subtotal:</strong> ₱${parseFloat(sale.total_amount - sale.tax_amount).toFixed(2)}</p>
                        <p><strong>Tax:</strong> ₱${parseFloat(sale.tax_amount).toFixed(2)}</p>
                        <p><strong>Total:</strong> ₱${parseFloat(sale.total_amount).toFixed(2)}</p>
                        ${sale.payment_method === 'cash' ? `<p><strong>Paid:</strong> ₱${parseFloat(sale.amount_paid).toFixed(2)}</p><p><strong>Change:</strong> ₱${parseFloat(sale.change_amount).toFixed(2)}</p>` : ''}
                    </div>
                </div>
                
                <div class="mb-4 sm:mb-6">
                    <h3 class="font-semibold text-gray-800 mb-2 sm:mb-4">Items Sold</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                ${sale.items.map(item => `
                                    <tr>
                                        <td class="px-4 py-2">${item.name}</td>
                                        <td class="px-4 py-2">${item.quantity}</td>
                                        <td class="px-4 py-2">₱${parseFloat(item.unit_price).toFixed(2)}</td>
                                        <td class="px-4 py-2">₱${parseFloat(item.total_price).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-4">
                    <button onclick="printReceipt('${sale.transaction_id}')" 
                            class="px-4 py-2 sm:px-6 sm:py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold w-full sm:w-auto">
                        <i class="fas fa-print mr-2"></i>Print Receipt
                    </button>
                    <button onclick="closeSaleModal()" 
                            class="px-4 py-2 sm:px-6 sm:py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 w-full sm:w-auto">
                        Close
                    </button>
                </div>
            `;

            document.getElementById('saleDetailsContent').innerHTML = content;
            document.getElementById('saleModal').classList.add('show');
        }

        function closeSaleModal() {
            document.getElementById('saleModal').classList.remove('show');
        }

        function printReceipt(transactionId) {
            window.open(`receipt.php?transaction_id=${transactionId}`, '_blank');
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === document.getElementById('saleModal')) {
                closeSaleModal();
            }
        });
    </script>
</body>

</html>