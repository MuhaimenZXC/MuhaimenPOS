<?php
require_once 'config/config.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get all products
$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$settings = getSettings($db);
$currentTheme = getCurrentTheme();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($_SESSION['theme']); ?>">
<!-- Rest of the HTML remains the same -->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - <?php echo SITE_NAME; ?></title>

    <!-- Tailwind CSS with DaisyUI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.5.0/dist/full.css" rel="stylesheet" type="text/css" />

    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .pos-container {
            max-height: calc(100vh - 80px);
        }

        .product-grid {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .cart-container {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
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
                    <a href="dashboard.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                    </a>
                    <a href="pos.php" class="flex items-center p-3 text-primary bg-primary/10 rounded-lg">
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
                    <a href="dashboard.php" class="flex items-center p-3 text-base-content hover:bg-base-200 rounded-lg">
                        <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                    </a>
                    <a href="pos.php" class="flex items-center p-3 text-primary bg-primary/10 rounded-lg">
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
        <div class="flex-1 p-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
                <div class="lg:col-span-2 bg-base-100 rounded-xl shadow-md p-6">
                    <div class="mb-6">
                        <div class="flex flex-col sm:flex-row gap-4 mb-4">
                            <div class="flex-1 flex gap-2">
                                <input type="text" id="searchInput" placeholder="Search products..." class="flex-1 px-4 py-3 border border-base-300 rounded-lg focus:ring-2 focus:ring-primary bg-base-100 text-base-content">
                                <button onclick="openBarcodeModal()" class="px-4 py-3 bg-success hover:bg-success/90 text-success-content rounded-lg font-semibold"><i class="fas fa-camera mr-2"></i>Scan Barcode</button>
                            </div>
                            <select id="categoryFilter" class="px-4 py-3 border border-base-300 rounded-lg focus:ring-2 focus:ring-primary bg-base-100 text-base-content">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="product-grid">
                        <div id="productsGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php foreach ($products as $p): ?>
                                <div class="product-card bg-base-200 rounded-lg p-4 cursor-pointer hover:shadow-lg transition transform hover:scale-105" data-category="<?php echo $p['category_id']; ?>" data-name="<?php echo strtolower(htmlspecialchars($p['name'])); ?>" onclick="addToCart(<?php echo $p['id']; ?>,'<?php echo htmlspecialchars($p['name']); ?>',<?php echo $p['price']; ?>,'<?php echo $p['image_path']; ?>')">
                                    <div class="aspect-square bg-base-300 rounded-lg mb-3 flex items-center justify-center">
                                        <?php if ($p['image_path']): ?><img src="<?php echo $p['image_path']; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="w-full h-full object-cover rounded-lg"><?php else: ?><i class="fas fa-box text-base-content/50 text-3xl"></i><?php endif; ?>
                                    </div>
                                    <h3 class="font-semibold text-base-content text-sm mb-1 truncate"><?php echo htmlspecialchars($p['name']); ?></h3>
                                    <p class="text-primary font-bold text-lg">₱<?php echo number_format($p['price'], 2); ?></p>
                                    <p class="text-xs text-base-content/70">Stock: <?php echo $p['stock_quantity']; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- CART -->
                <div class="bg-base-100 rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-semibold text-base-content mb-4"><i class="fas fa-shopping-cart mr-2 text-primary"></i>Shopping Cart</h3>
                    <div class="cart-container mb-6">
                        <div id="cartItems" class="space-y-3">
                            <div class="text-center text-base-content/70 py-8"><i class="fas fa-shopping-cart text-4xl mb-2"></i>
                                <p>No items in cart</p>
                            </div>
                        </div>
                    </div>
                    <div class="border-t border-base-300 pt-4">
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between"><span class="text-base-content">Subtotal:</span><span id="subtotal" class="text-base-content">₱0.00</span></div>
                            <div class="flex justify-between"><span class="text-base-content">Tax (<?php echo $settings['tax_rate']; ?>%):</span><span id="tax" class="text-base-content">₱0.00</span></div>
                            <div class="flex justify-between text-xl font-bold text-base-content"><span>Total:</span><span id="total" class="text-base-content">₱0.00</span></div>
                        </div>
                        <div id="paymentSection" class="hidden">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-base-content mb-2">Payment Method</label>
                                <select id="paymentMethod" class="w-full px-3 py-2 border border-base-300 rounded-lg bg-base-100 text-base-content">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="digital">Digital Payment</option>
                                </select>
                            </div>
                            <div class="mb-4" id="cashSection">
                                <label class="block text-sm font-medium text-base-content mb-2">Amount Paid</label>
                                <input type="number" id="amountPaid" step="0.01" min="0" class="w-full px-3 py-2 border border-base-300 rounded-lg bg-base-100 text-base-content">
                            </div>
                            <div class="mb-4" id="changeSection">
                                <div class="flex justify-between font-semibold text-base-content"><span>Change:</span><span id="change" class="text-base-content">₱0.00</span></div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <button id="checkoutBtn" onclick="proceedToPayment()" class="w-full bg-primary hover:bg-primary/90 text-primary-content py-3 rounded-lg font-semibold disabled:opacity-50" disabled><i class="fas fa-credit-card mr-2"></i>Proceed to Payment</button>
                            <button id="completeBtn" onclick="completeSale()" class="w-full bg-success hover:bg-success/90 text-success-content py-3 rounded-lg font-semibold hidden"><i class="fas fa-check mr-2"></i>Complete Sale</button>
                            <button onclick="clearCart()" class="w-full bg-error hover:bg-error/90 text-error-content py-2 rounded-lg"><i class="fas fa-trash mr-2"></i>Clear Cart</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BARCODE MODAL -->
            <div id="barcodeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-base-100 p-4 rounded-lg">
                    <video id="video" class="w-96 h-64 border border-base-300 rounded-lg"></video>
                    <button onclick="closeBarcodeModal()" class="mt-2 px-4 py-2 bg-error text-error-content rounded">Close Scanner</button>
                </div>
            </div>

            <audio id="beepSound" src="https://freesound.org/data/previews/331/331912_3248244-lq.mp3"></audio>
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

        let cart = [];
        let taxRate = <?php echo $settings['tax_rate']; ?>;
        const products = <?php echo json_encode($products); ?>;
        let lastScanTime = 0;
        let currentStream = null;

        // CART FUNCTIONS
        function addToCart(id, name, price, image) {
            const exist = cart.find(i => i.id === id);
            exist ? exist.quantity++ : cart.push({
                id,
                name,
                price,
                quantity: 1,
                image
            });
            updateCartDisplay();
        }

        function removeFromCart(id) {
            cart = cart.filter(i => i.id !== id);
            updateCartDisplay();
        }

        function updateQuantity(id, qty) {
            const i = cart.find(x => x.id === id);
            if (i) {
                i.quantity = Math.max(1, qty);
                updateCartDisplay();
            }
        }

        function clearCart() {
            cart = [];
            updateCartDisplay();
            document.getElementById('paymentSection').classList.add('hidden');
            document.getElementById('checkoutBtn').classList.remove('hidden');
            document.getElementById('completeBtn').classList.add('hidden');
        }

        function updateCartDisplay() {
            const ci = document.getElementById('cartItems'),
                st = document.getElementById('subtotal'),
                tx = document.getElementById('tax'),
                tl = document.getElementById('total');
            if (!cart.length) {
                ci.innerHTML = '<div class="text-center text-base-content/70 py-8"><i class="fas fa-shopping-cart text-4xl mb-2"></i><p>No items in cart</p></div>';
                st.textContent = tx.textContent = tl.textContent = '₱0.00';
                document.getElementById('checkoutBtn').disabled = true;
                return;
            }
            let sub = 0;
            ci.innerHTML = cart.map(it => {
                const t = it.price * it.quantity;
                sub += t;
                return `<div class="flex items-center justify-between p-3 bg-base-200 rounded-lg"><div class="flex-1"><h4 class="font-medium text-base-content text-sm">${it.name}</h4><p class="text-primary font-semibold">₱${it.price.toFixed(2)}</p></div><div class="flex items-center space-x-2"><button onclick="updateQuantity(${it.id},${it.quantity-1})" class="w-8 h-8 bg-base-300 rounded-full">-</button><span class="w-8 text-center text-base-content">${it.quantity}</span><button onclick="updateQuantity(${it.id},${it.quantity+1})" class="w-8 h-8 bg-base-300 rounded-full">+</button><button onclick="removeFromCart(${it.id})" class="ml-2 text-error"><i class="fas fa-trash"></i></button></div></div>`
            }).join('');
            const tax = sub * (taxRate / 100),
                total = sub + tax;
            st.textContent = `₱${sub.toFixed(2)}`;
            tx.textContent = `₱${tax.toFixed(2)}`;
            tl.textContent = `₱${total.toFixed(2)}`;
            document.getElementById('checkoutBtn').disabled = false;
        }

        // PAYMENT
        function proceedToPayment() {
            if (!cart.length) return;
            document.getElementById('paymentSection').classList.remove('hidden');
            document.getElementById('checkoutBtn').classList.add('hidden');
            document.getElementById('completeBtn').classList.remove('hidden');
            const total = parseFloat(document.getElementById('total').textContent.replace('₱', ''));
            document.getElementById('amountPaid').value = total.toFixed(2);
            calculateChange();
        }

        function calculateChange() {
            const paid = parseFloat(document.getElementById('amountPaid').value) || 0,
                total = parseFloat(document.getElementById('total').textContent.replace('₱', ''));
            document.getElementById('change').textContent = `₱${Math.max(0,paid-total).toFixed(2)}`;
        }

        function completeSale() {
            if (!cart.length) return;

            // Show loading state
            const completeBtn = document.getElementById('completeBtn');
            const originalText = completeBtn.innerHTML;
            completeBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            completeBtn.disabled = true;

            const method = document.getElementById('paymentMethod').value,
                paid = parseFloat(document.getElementById('amountPaid').value) || 0,
                total = parseFloat(document.getElementById('total').textContent.replace('₱', ''));
            if (method === 'cash' && paid < total) {
                showNotification('Amount paid must be greater than or equal to total', 'error');
                completeBtn.innerHTML = originalText;
                completeBtn.disabled = false;
                return;
            }
            const saleData = {
                items: cart,
                subtotal: parseFloat(document.getElementById('subtotal').textContent.replace('₱', '')),
                tax: parseFloat(document.getElementById('tax').textContent.replace('₱', '')),
                total,
                payment_method: method,
                amount_paid: paid,
                change_amount: Math.max(0, paid - total)
            };
            fetch('api/process_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(saleData)
            }).then(r => {
                if (!r.ok) throw new Error('Network response was not ok');
                return r.json();
            }).then(d => {
                if (d.success) {
                    showNotification('Sale completed successfully!', 'success');
                    // Automatically open receipt in new tab
                    openReceipt(d.transaction_id);
                    clearCart();
                    // Reset button state
                    completeBtn.innerHTML = originalText;
                    completeBtn.disabled = false;
                } else {
                    throw new Error(d.message || 'Unknown error occurred');
                }
            }).catch(error => {
                showNotification('Error: ' + error.message, 'error');
                completeBtn.innerHTML = originalText;
                completeBtn.disabled = false;
            });
        }

        // RECEIPT FUNCTION
        function openReceipt(transactionId) {
            const w = window.open(`receipt.php?transaction_id=${transactionId}`, '_blank');
            if (!w) {
                showNotification('Popup blocked! Please allow popups for this site.', 'error');
                return;
            }
            w.focus();
        }

        // SEARCH / FILTER
        document.getElementById('searchInput').addEventListener('input', function() {
            const t = this.value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(p => p.style.display = p.dataset.name.includes(t) ? 'block' : 'none');
        });
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const id = this.value;
            document.querySelectorAll('.product-card').forEach(p => p.style.display = !id || p.dataset.category === id ? 'block' : 'none');
        });
        document.getElementById('paymentMethod').addEventListener('change', function() {
            const cash = document.getElementById('cashSection'),
                chg = document.getElementById('changeSection');
            if (this.value === 'cash') {
                cash.classList.remove('hidden');
                chg.classList.remove('hidden');
            } else {
                cash.classList.add('hidden');
                chg.classList.add('hidden');
            }
        });
        document.getElementById('amountPaid').addEventListener('input', calculateChange);

        // BARCODE SCANNER
        function openBarcodeModal() {
            const modal = document.getElementById('barcodeModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            const video = document.getElementById('video');
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment'
                    }
                }).then(stream => {
                    video.srcObject = stream;
                    video.play();
                    currentStream = stream;
                    Quagga.init({
                        inputStream: {
                            name: "Live",
                            type: "LiveStream",
                            target: video,
                            constraints: {
                                facingMode: "environment"
                            }
                        },
                        decoder: {
                            readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader", "upc_reader", "upc_e_reader"]
                        },
                        locate: true
                    }, function(err) {
                        if (err) {
                            console.error(err);
                            return;
                        }
                        Quagga.start();
                    });
                    Quagga.onDetected(data => {
                        const now = Date.now();
                        if (now - lastScanTime < 1000) return; // 1s cooldown
                        lastScanTime = now;
                        const code = data.codeResult.code;
                        const product = products.find(p => p.barcode === code);
                        if (product) {
                            addToCart(product.id, product.name, parseFloat(product.price), product.image_path);
                            document.getElementById('beepSound').play();
                            showNotification(`Added to cart: ${product.name}`, 'success');
                        } else showNotification('Product not found', 'error');
                    });
                }).catch(err => {
                    alert('Camera access denied or not available');
                    console.error(err);
                });
            } else {
                alert('Camera not supported');
            }
        }

        function closeBarcodeModal() {
            Quagga.stop();
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
                currentStream = null;
            }
            document.getElementById('barcodeModal').classList.add('hidden');
        }

        // TOAST NOTIFICATION
        function showNotification(msg, type = 'success') {
            const colors = {
                success: 'bg-success',
                error: 'bg-error',
                info: 'bg-info',
                warning: 'bg-warning'
            };
            const color = colors[type] || colors.success;
            const toast = document.createElement('div');
            toast.className = `fixed top-5 right-5 ${color} text-base-content px-5 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2`;

            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle',
                warning: 'fa-exclamation-triangle'
            };
            const icon = icons[type] || icons.success;

            toast.innerHTML = `<i class="fas ${icon}"></i><span>${msg}</span>`;
            document.body.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            }, 10);

            // Remove after 3 seconds
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>

</html>