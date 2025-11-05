<?php
require_once 'config/config.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get report type
$report_type = $_GET['type'] ?? 'daily';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Set default date range based on report type
$default_dates = [
    'daily' => date('Y-m-d'),
    'weekly' => date('Y-m-d', strtotime('-7 days')),
    'monthly' => date('Y-m-01'),
    'yearly' => date('Y-01-01')
];

if (!$date_from) {
    $date_from = $default_dates[$report_type] ?? date('Y-m-d');
}
if (!$date_to) {
    $date_to = date('Y-m-d');
}

// Get sales data based on report type
$where_clause = "WHERE DATE(s.created_at) BETWEEN :date_from AND :date_to";
$params = [
    ':date_from' => $date_from,
    ':date_to' => $date_to
];

// Get summary statistics
$query = "SELECT 
    COUNT(*) as total_transactions,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as average_transaction,
    SUM(tax_amount) as total_tax
    FROM sales s 
    $where_clause";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get sales by payment method
$query = "SELECT payment_method, COUNT(*) as count, SUM(total_amount) as amount 
          FROM sales s 
          $where_clause 
          GROUP BY payment_method";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top selling products
$query = "SELECT p.name, SUM(si.quantity) as total_sold, SUM(si.total_price) as total_revenue
          FROM sales_items si
          LEFT JOIN products p ON si.product_id = p.id
          LEFT JOIN sales s ON si.sale_id = s.id
          $where_clause
          GROUP BY si.product_id, p.name
          ORDER BY total_sold DESC
          LIMIT 10";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily sales data for chart
$daily_sales = [];
if ($report_type === 'daily' || $report_type === 'weekly') {
    $query = "SELECT DATE(created_at) as date, COUNT(*) as transactions, SUM(total_amount) as revenue
              FROM sales 
              WHERE DATE(created_at) BETWEEN :date_from AND :date_to
              GROUP BY DATE(created_at)
              ORDER BY date";
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$settings = getSettings($db);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($_SESSION['theme']); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sales Report - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.5.0/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* Mobile-first responsive styles */
        @media (max-width: 640px) {
            .stats-card {
                padding: 1rem;
            }

            .stats-card .text-2xl {
                font-size: 1.25rem;
            }

            .chart-container {
                height: 250px;
            }

            .mobile-export-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }

            .mobile-export-buttons button {
                width: 100%;
            }

            .report-type-selector {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .report-type-selector a {
                flex: 1;
                min-width: 80px;
                text-align: center;
            }

            .date-form {
                flex-direction: column;
                gap: 0.5rem;
            }

            .date-form input {
                width: 100%;
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
                    <i class="fas fa-chart-bar text-primary text-2xl"></i>
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
                    <a href="sales_history.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-history mr-3"></i>Sales History
                    </a>
                    <a href="sales_report.php" class="flex items-center p-3 text-blue-600 bg-blue-50 rounded-lg">
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
                    <a href="sales_history.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-history mr-3"></i>Sales History
                    </a>
                    <a href="sales_report.php" class="flex items-center p-3 text-blue-600 bg-blue-50 rounded-lg">
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
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-8 gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-3xl font-bold text-gray-800 mb-1 sm:mb-2">Sales Report</h1>
                    <p class="text-sm sm:text-base text-gray-600">Analyze your business performance</p>
                </div>
                <div class="flex flex-wrap gap-2 sm:gap-4 mobile-export-buttons">
                    <button onclick="exportReport('pdf')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 sm:px-6 sm:py-3 rounded-lg font-semibold text-sm sm:text-base">
                        <i class="fas fa-file-pdf mr-2"></i>Export PDF
                    </button>
                    <button onclick="exportReport('csv')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 sm:px-6 sm:py-3 rounded-lg font-semibold text-sm sm:text-base">
                        <i class="fas fa-file-csv mr-2"></i>Export CSV
                    </button>
                </div>
            </div>

            <!-- Report Type Selector (Responsive) -->
            <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-8">
                <div class="flex flex-col md:flex-row gap-4 md:items-end">

                    <!-- Report Type Section -->
                    <div class="w-full md:flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                        <div class="flex flex-wrap gap-2 report-type-selector">
                            <a href="?type=daily"
                                class="flex-1 text-center px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base 
                   <?php echo $report_type === 'daily' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                Daily
                            </a>
                            <a href="?type=weekly"
                                class="flex-1 text-center px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base 
                   <?php echo $report_type === 'weekly' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                Weekly
                            </a>
                            <a href="?type=monthly"
                                class="flex-1 text-center px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base 
                   <?php echo $report_type === 'monthly' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                Monthly
                            </a>
                            <a href="?type=yearly"
                                class="flex-1 text-center px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base 
                   <?php echo $report_type === 'yearly' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                Yearly
                            </a>
                        </div>
                    </div>

                    <!-- Date Range Section -->
                    <div class="w-full md:w-auto">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <input type="hidden" name="type" value="<?php echo $report_type; ?>">

                            <input type="date" name="date_from" value="<?php echo $date_from; ?>"
                                class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">

                            <input type="date" name="date_to" value="<?php echo $date_to; ?>"
                                class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">

                            <button type="submit"
                                class="w-full sm:w-auto px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-4 sm:mb-8">
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 stats-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-shopping-cart text-xl sm:text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-xs sm:text-sm">Total Transactions</p>
                            <p class="text-lg sm:text-2xl font-bold text-gray-800"><?php echo number_format($summary['total_transactions']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 stats-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-peso-sign text-xl sm:text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-xs sm:text-sm">Total Revenue</p>
                            <p class="text-lg sm:text-2xl font-bold text-gray-800"><?php echo formatCurrency($summary['total_revenue']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 stats-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-chart-line text-xl sm:text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-xs sm:text-sm">Average Transaction</p>
                            <p class="text-lg sm:text-2xl font-bold text-gray-800"><?php echo formatCurrency($summary['average_transaction']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 stats-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-receipt text-xl sm:text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-xs sm:text-sm">Total Tax</p>
                            <p class="text-lg sm:text-2xl font-bold text-gray-800"><?php echo formatCurrency($summary['total_tax']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-8">
                <!-- Sales Chart -->
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-4">Sales Trend</h3>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <!-- Payment Methods Chart -->
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-4">Payment Methods</h3>
                    <div class="chart-container">
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-8">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-4">Top Selling Products</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Sold</th>
                                <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($top_products as $product): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo number_format($product['total_sold']); ?></div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900"><?php echo formatCurrency($product['total_revenue']); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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

        // Sales Chart
        <?php if (!empty($daily_sales)): ?>
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($daily_sales, 'date')); ?>,
                    datasets: [{
                        label: 'Revenue',
                        data: <?php echo json_encode(array_column($daily_sales, 'revenue')); ?>,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1
                    }, {
                        label: 'Transactions',
                        data: <?php echo json_encode(array_column($daily_sales, 'transactions')); ?>,
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Daily Sales'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        <?php else: ?>
            document.getElementById('salesChart').parentElement.innerHTML = '<div class="text-center text-gray-500 py-8">No data available for selected period</div>';
        <?php endif; ?>

        // Payment Methods Chart
        <?php if (!empty($payment_methods)): ?>
            const paymentCtx = document.getElementById('paymentChart').getContext('2d');
            const paymentChart = new Chart(paymentCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($payment_methods, 'payment_method')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($payment_methods, 'amount')); ?>,
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(16, 185, 129)',
                            'rgb(139, 92, 246)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        <?php else: ?>
            document.getElementById('paymentChart').parentElement.innerHTML = '<div class="text-center text-gray-500 py-8">No data available for selected period</div>';
        <?php endif; ?>

        function exportReport(format) {
            const url = `api/export_report.php?type=<?php echo $report_type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&format=${format}`;
            window.open(url, '_blank');
        }
    </script>
</body>

</html>