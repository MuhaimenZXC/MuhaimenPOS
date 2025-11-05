<?php
require_once 'config/config.php';
requireLogin();
requireAdmin(); // Only admin can access settings

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_store':
                // Update store settings
                $store_name = $_POST['store_name'];
                $store_address = $_POST['store_address'];
                $store_phone = $_POST['store_phone'];
                $store_email = $_POST['store_email'];
                $tax_rate = $_POST['tax_rate'];
                $currency = $_POST['currency'];

                $query = "UPDATE settings SET 
                         store_name = :store_name, 
                         store_address = :store_address, 
                         store_phone = :store_phone, 
                         store_email = :store_email, 
                         tax_rate = :tax_rate, 
                         currency = :currency
                         WHERE id = 1";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':store_name', $store_name);
                $stmt->bindParam(':store_address', $store_address);
                $stmt->bindParam(':store_phone', $store_phone);
                $stmt->bindParam(':store_email', $store_email);
                $stmt->bindParam(':tax_rate', $tax_rate);
                $stmt->bindParam(':currency', $currency);

                if ($stmt->execute()) {
                    $message = 'Store settings updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating store settings';
                    $message_type = 'error';
                }
                break;

            case 'update_receipt':
                // Update receipt settings
                $receipt_header = $_POST['receipt_header'];
                $receipt_footer = $_POST['receipt_footer'];

                $query = "UPDATE settings SET 
                         receipt_header = :receipt_header, 
                         receipt_footer = :receipt_footer 
                         WHERE id = 1";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':receipt_header', $receipt_header);
                $stmt->bindParam(':receipt_footer', $receipt_footer);

                if ($stmt->execute()) {
                    $message = 'Receipt settings updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating receipt settings';
                    $message_type = 'error';
                }
                break;

            case 'update_theme':
                // Update theme settings
                $theme = $_POST['theme'];
                $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;

                $query = "UPDATE settings SET theme = :theme, dark_mode = :dark_mode WHERE id = 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':theme', $theme);
                $stmt->bindParam(':dark_mode', $dark_mode);

                if ($stmt->execute()) {
                    $_SESSION['theme'] = $theme;
                    $_SESSION['dark_mode'] = $dark_mode;
                    $message = 'Theme settings updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating theme settings';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get current settings
$settings = getSettings($db);

// Theme options with DaisyUI theme names
$themes = [
    'light' => 'Light',
    'dark' => 'Dark',
    'cupcake' => 'Cupcake',
    'bumblebee' => 'Bumblebee',
    'emerald' => 'Emerald',
    'corporate' => 'Corporate',
    'synthwave' => 'Synthwave',
    'retro' => 'Retro',
    'cyberpunk' => 'Cyberpunk',
    'valentine' => 'Valentine',
    'halloween' => 'Halloween',
    'garden' => 'Garden',
    'forest' => 'Forest',
    'aqua' => 'Aqua',
    'lofi' => 'Lofi',
    'pastel' => 'Pastel',
    'fantasy' => 'Fantasy',
    'wireframe' => 'Wireframe',
    'black' => 'Black',
    'luxury' => 'Luxury',
    'dracula' => 'Dracula',
    'cmyk' => 'CMYK',
    'autumn' => 'Autumn',
    'business' => 'Business',
    'acid' => 'Acid',
    'lemonade' => 'Lemonade',
    'night' => 'Night',
    'coffee' => 'Coffee',
    'winter' => 'Winter',
    'dim' => 'Dim',
    'nord' => 'Nord',
    'sunset' => 'Sunset',
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($settings['theme']); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Settings - <?php echo SITE_NAME; ?></title>

    <!-- Tailwind CSS with DaisyUI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.5.0/dist/full.css" rel="stylesheet" type="text/css" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        .theme-option {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .theme-option:hover {
            transform: scale(1.05);
        }

        .theme-option.selected {
            transform: scale(1.1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }

        /* Mobile-first responsive styles */
        @media (max-width: 640px) {
            .settings-card {
                padding: 1rem;
            }

            .settings-form input,
            .settings-form select,
            .settings-form textarea {
                font-size: 16px;
                /* Prevents zoom on iOS */
            }

            .theme-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .system-info-grid {
                grid-template-columns: 1fr;
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
                    <i class="fas fa-cog text-primary text-2xl"></i>
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
                    <a href="dashboard.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
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
                    <a href="settings.php" class="flex items-center p-3 text-primary bg-primary/10 rounded-lg">
                        <i class="fas fa-cog mr-3"></i>Settings
                    </a>
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
                    <a href="dashboard.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
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
                    <a href="settings.php" class="flex items-center p-3 text-primary bg-primary/10 rounded-lg">
                        <i class="fas fa-cog mr-3"></i>Settings
                    </a>
                    <a href="auth/logout.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-sign-out-alt mr-3 text-error"></i>Logout
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4 sm:p-6 md:p-8">
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-8 gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-3xl font-bold text-base-content mb-1 sm:mb-2">System Settings</h1>
                    <p class="text-sm sm:text-base text-base-content/70">Configure your POS system</p>
                </div>
            </div>

            <!-- Message -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-success/20 text-success' : 'bg-error/20 text-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-8">
                <!-- Store Settings -->
                <div class="bg-base-100 rounded-xl shadow-md p-4 sm:p-6 settings-card">
                    <h3 class="text-lg sm:text-xl font-semibold text-base-content mb-4 sm:mb-6">
                        <i class="fas fa-store mr-2 text-primary"></i>Store Settings
                    </h3>

                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="update_store">

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-base-content mb-2">Store Name</label>
                                <input type="text" name="store_name" value="<?php echo htmlspecialchars($settings['store_name']); ?>"
                                    class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-base-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-base-100 text-base-content">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-base-content mb-2">Store Address</label>
                                <textarea name="store_address" rows="3"
                                    class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-base-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-base-100 text-base-content"
                                    placeholder="Enter store address..."><?php echo htmlspecialchars($settings['store_address']); ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-base-content mb-2">Phone Number</label>
                                <input type="text" name="store_phone" value="<?php echo htmlspecialchars($settings['store_phone']); ?>"
                                    class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-base-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-base-100 text-base-content">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-base-content mb-2">Email</label>
                                <input type="email" name="store_email" value="<?php echo htmlspecialchars($settings['store_email']); ?>"
                                    class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-base-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-base-100 text-base-content">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-base-content mb-2">Tax Rate (%)</label>
                                    <input type="number" name="tax_rate" step="0.01" min="0" value="<?php echo htmlspecialchars($settings['tax_rate']); ?>"
                                        class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-base-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-base-100 text-base-content">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-base-content mb-2">Currency</label>
                                    <input type="text" name="currency" value="<?php echo htmlspecialchars($settings['currency']); ?>"
                                        class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-base-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-base-100 text-base-content">
                                </div>
                            </div>

                            <button type="submit"
                                class="w-full bg-primary hover:bg-primary/90 text-primary-content py-2 sm:py-3 rounded-lg font-semibold text-sm sm:text-base">
                                <i class="fas fa-save mr-2"></i>Save Store Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Theme Settings -->
                <div class="bg-base-100 rounded-xl shadow-md p-4 sm:p-6 settings-card">
                    <h3 class="text-lg sm:text-xl font-semibold text-base-content mb-4 sm:mb-6">
                        <i class="fas fa-palette mr-2 text-primary"></i>Theme Settings
                    </h3>

                    <form method="POST" id="themeForm">
                        <input type="hidden" name="action" value="update_theme">
                        <input type="hidden" name="theme" id="selectedTheme" value="<?php echo $settings['theme']; ?>">

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-base-content mb-4">Choose Theme</label>
                                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 theme-grid gap-3">
                                    <?php foreach ($themes as $key => $name): ?>
                                        <div class="theme-option <?php echo $settings['theme'] === $key ? 'selected' : ''; ?>"
                                            data-theme="<?php echo $key; ?>"
                                            onclick="selectTheme('<?php echo $key; ?>')">
                                            <div class="w-full h-12 rounded-lg bg-gradient-to-r from-primary to-secondary cursor-pointer"></div>
                                            <p class="text-xs text-center mt-2 font-medium text-base-content"><?php echo $name; ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="dark_mode" id="darkMode" value="1" <?php echo $settings['dark_mode'] ? 'checked' : ''; ?>
                                    class="w-5 h-5 text-primary border-base-300 rounded focus:ring-primary">
                                <label for="darkMode" class="ml-3 text-sm font-medium text-base-content">
                                    Enable Dark Mode
                                </label>
                            </div>

                            <button type="submit"
                                class="w-full bg-primary hover:bg-primary/90 text-primary-content py-2 sm:py-3 rounded-lg font-semibold text-sm sm:text-base">
                                <i class="fas fa-paint-brush mr-2"></i>Apply Theme
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Receipt Settings -->
            <div class="bg-base-100 rounded-xl shadow-md p-4 sm:p-6 settings-card mt-4 sm:mt-8">
                <h3 class="text-lg sm:text-xl font-semibold text-base-content mb-4 sm:mb-6">
                    <i class="fas fa-receipt mr-2 text-primary"></i>Receipt Settings
                </h3>

                <form method="POST">
                    <input type="hidden" name="action" value="update_receipt">

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-base-content mb-2">Receipt Header</label>
                            <textarea name="receipt_header" rows="4"
                                class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-base-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-base-100 text-base-content"
                                placeholder="Enter receipt header text..."><?php echo htmlspecialchars($settings['receipt_header']); ?></textarea>
                            <p class="text-sm text-base-content/70 mt-1">This text will appear at the top of receipts</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-base-content mb-2">Receipt Footer</label>
                            <textarea name="receipt_footer" rows="4"
                                class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-base-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-base-100 text-base-content"
                                placeholder="Enter receipt footer text..."><?php echo htmlspecialchars($settings['receipt_footer']); ?></textarea>
                            <p class="text-sm text-base-content/70 mt-1">This text will appear at the bottom of receipts</p>
                        </div>
                    </div>

                    <div class="mt-4 sm:mt-6">
                        <button type="submit"
                            class="bg-primary hover:bg-primary/90 text-primary-content px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold text-sm sm:text-base">
                            <i class="fas fa-save mr-2"></i>Save Receipt Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Information -->
            <div class="bg-base-100 rounded-xl shadow-md p-4 sm:p-6 settings-card mt-4 sm:mt-8">
                <h3 class="text-lg sm:text-xl font-semibold text-base-content mb-4 sm:mb-6">
                    <i class="fas fa-info-circle mr-2 text-primary"></i>System Information
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 system-info-grid">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-cash-register text-primary text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-base-content mb-1">POS ni Muhaimen</h4>
                        <p class="text-sm text-base-content/70">Version 1.0.0</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-code text-primary text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-base-content mb-1">Developed By</h4>
                        <p class="text-sm text-base-content/70">Muhaimen Esmail</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-calendar text-primary text-2xl"></i>
                        </div>
                        <h4 class="font-semibold text-base-content mb-1">Last Updated</h4>
                        <p class="text-sm text-base-content/70"><?php echo date('M d, Y', strtotime($settings['updated_at'])); ?></p>
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

        // Theme selection
        function selectTheme(theme) {
            // Remove selected class from all theme options
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Add selected class to clicked option
            const selectedOption = document.querySelector(`[data-theme="${theme}"]`);
            if (selectedOption) {
                selectedOption.classList.add('selected');
            }

            // Update hidden input
            document.getElementById('selectedTheme').value = theme;

            // Apply theme immediately
            document.documentElement.setAttribute('data-theme', theme);

            // Store in session for immediate effect
            sessionStorage.setItem('theme', theme);
        }

        // Apply theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = sessionStorage.getItem('theme') || '<?php echo $settings['theme']; ?>';
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
                selectTheme(savedTheme);
            }

            // Handle dark mode toggle
            const darkModeToggle = document.getElementById('darkMode');
            darkModeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    sessionStorage.setItem('theme', 'dark');
                } else {
                    const currentTheme = document.getElementById('selectedTheme').value;
                    document.documentElement.setAttribute('data-theme', currentTheme);
                    sessionStorage.setItem('theme', currentTheme);
                }
            });
        });
    </script>
</body>

</html>