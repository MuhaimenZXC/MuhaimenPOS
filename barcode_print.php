<?php
require_once 'config/config.php';
requireLogin();

$product_id = $_GET['id'] ?? 0;

$database = new Database();
$db = $database->getConnection();

// Get product details
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die('Product not found');
}

$settings = getSettings($db);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode - <?php echo htmlspecialchars($product['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none;
            }

            .barcode-label {
                page-break-inside: avoid;
                width: 2.25in;
                /* Standard thermal label width */
                height: 1.25in;
                /* Standard thermal label height */
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                border: 1px dashed #ccc;
                margin: 0.125in;
                padding: 0.125in;
            }
        }

        .barcode-label {
            width: 2.25in;
            height: 1.25in;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid #e5e7eb;
            margin: 10px;
            padding: 10px;
            background: white;
        }

        .tape-printer {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>

<head>
    <meta charset="UTF-8">
    <title>Print Barcode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <!-- ===== BARCODE-PRINTER STYLES ===== -->
    <style>
        @media print {

            /* Thermal / tape-printer optimisations */
            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .barcode-label {
                page-break-inside: avoid;
                width: 2.25in;
                height: 1.25in;
                display: flex !important;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                border: 1px solid #000;
                margin: 0.125in;
                padding: 0.125in;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* on-screen preview stays the same */
        .barcode-label {
            width: 2.25in;
            height: 1.25in;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid #e5e7eb;
            margin: 10px;
            padding: 10px;
            background: white;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto p-6">
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Barcode Label Printing</h1>
                <div class="no-print">
                    <button onclick="window.print()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold mr-2">
                        <i class="fas fa-print mr-2"></i>Print Labels
                    </button>
                    <button onclick="window.close()"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg">
                        <i class="fas fa-times mr-2"></i>Close
                    </button>
                </div>
            </div>

            <!-- Print Settings -->
            <div class="no-print mb-6 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Label Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Label Size</label>
                        <select id="labelSize" onchange="updateLabelSize()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="2.25x1.25">2.25" x 1.25" (Standard)</option>
                            <option value="2x1">2" x 1"</option>
                            <option value="3x2">3" x 2"</option>
                            <option value="4x2">4" x 2"</option>
                            <option value="4x6">4" x 6" (Shipping)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" id="labelQuantity" value="1" min="1" max="100"
                            onchange="updateLabels()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Printer Type</label>
                        <select id="printerType" onchange="updatePrinterSettings()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="thermal">Thermal Printer</option>
                            <option value="laser">Laser/Inkjet</option>
                            <option value="tape">Tape Label Printer</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="includePrice" onchange="updateLabels()" checked
                            class="mr-2">
                        <span class="text-sm text-gray-700">Include Price</span>
                    </label>
                    <label class="flex items-center mt-2">
                        <input type="checkbox" id="includeName" onchange="updateLabels()" checked
                            class="mr-2">
                        <span class="text-sm text-gray-700">Include Product Name</span>
                    </label>
                    <label class="flex items-center mt-2">
                        <input type="checkbox" id="includeDate" onchange="updateLabels()"
                            class="mr-2">
                        <span class="text-sm text-gray-700">Include Date</span>
                    </label>
                </div>
            </div>

            <!-- Printer Instructions -->
            <div class="no-print tape-printer">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">
                    <i class="fas fa-info-circle mr-2"></i>Thermal/Tape Printer Instructions
                </h3>
                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                    <li>Ensure your thermal printer is connected and loaded with label tape</li>
                    <li>Set your printer properties to match the label size selected above</li>
                    <li>For tape printers (like DYMO or Brother), use the appropriate tape cassette</li>
                    <li>Print a test page first to verify alignment</li>
                    <li>Use 300 DPI or higher for best barcode scanning results</li>
                </ol>
            </div>

            <!-- Preview Area -->
            <div id="labelsContainer" class="flex flex-wrap justify-center">
                <!-- Labels will be generated here -->
            </div>
        </div>
    </div>

    <script>
        const product = <?php echo json_encode($product); ?>;

        function generateBarcode(labelDiv, product) {
            const barcodeValue = product.barcode || product.id.toString().padStart(8, '0');

            const barcodeSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            barcodeSvg.classList.add('barcode');
            barcodeSvg.setAttribute('jsbarcode-format', 'CODE128');
            barcodeSvg.setAttribute('jsbarcode-value', barcodeValue);
            barcodeSvg.setAttribute('jsbarcode-textmargin', '0');
            barcodeSvg.setAttribute('jsbarcode-fontoptions', 'bold');
            barcodeSvg.setAttribute('jsbarcode-height', '60'); // mas mataas
            barcodeSvg.setAttribute('jsbarcode-width', '2'); // mas kapal lines
            barcodeSvg.setAttribute('jsbarcode-fontsize', '16'); // mas malaking numbers

            labelDiv.appendChild(barcodeSvg);

            // Generate the barcode
            JsBarcode(barcodeSvg).init();
        }

        function createLabel(product, index) {
            const labelDiv = document.createElement('div');
            labelDiv.className = 'barcode-label';
            labelDiv.id = `label-${index}`;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'text-center w-full';

            // Product Name
            if (document.getElementById('includeName').checked) {
                const nameDiv = document.createElement('div');
                nameDiv.className = 'text-xs font-bold text-gray-800 mb-1 truncate';
                nameDiv.textContent = product.name;
                contentDiv.appendChild(nameDiv);
            }

            // Barcode
            generateBarcode(contentDiv, product);

            // Price
            if (document.getElementById('includePrice').checked) {
                const priceDiv = document.createElement('div');
                priceDiv.className = 'text-xs text-gray-600 mt-1';
                priceDiv.textContent = `<?php echo $settings['currency']; ?>${parseFloat(product.price).toFixed(2)}`;
                contentDiv.appendChild(priceDiv);
            }

            // Date
            if (document.getElementById('includeDate').checked) {
                const dateDiv = document.createElement('div');
                dateDiv.className = 'text-xs text-gray-500 mt-1';
                dateDiv.textContent = new Date().toLocaleDateString();
                contentDiv.appendChild(dateDiv);
            }

            labelDiv.appendChild(contentDiv);
            return labelDiv;
        }

        function updateLabels() {
            const container = document.getElementById('labelsContainer');
            const quantity = parseInt(document.getElementById('labelQuantity').value) || 1;

            container.innerHTML = '';

            for (let i = 0; i < quantity; i++) {
                container.appendChild(createLabel(product, i));
            }
        }

        function updateLabelSize() {
            const size = document.getElementById('labelSize').value;
            const [width, height] = size.split('x').map(s => parseFloat(s));

            const labels = document.querySelectorAll('.barcode-label');
            labels.forEach(label => {
                label.style.width = `${width}in`;
                label.style.height = `${height}in`;
            });
        }

        function updatePrinterSettings() {
            const printerType = document.getElementById('printerType').value;
            const labels = document.querySelectorAll('.barcode-label');

            if (printerType === 'thermal' || printerType === 'tape') {
                // Optimize for thermal printers
                labels.forEach(label => {
                    label.style.border = '1px solid #000';
                    label.style.background = 'white';
                });
            } else {
                // Optimize for laser/inkjet
                labels.forEach(label => {
                    label.style.border = '2px solid #e5e7eb';
                    label.style.background = 'white';
                });
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateLabels();
        });
    </script>
</body>

</html>