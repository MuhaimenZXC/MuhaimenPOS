<?php
require_once 'config/config.php';

if (!isset($_GET['transaction_id'])) {
    die('Transaction ID required');
}

$transaction_id = $_GET['transaction_id'];

$database = new Database();
$db = $database->getConnection();

// Get sale details
$query = "SELECT s.*, u.username FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.transaction_id = :transaction_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':transaction_id', $transaction_id);
$stmt->execute();
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die('Sale not found');
}

// Get sale items
$query = "SELECT si.*, p.name FROM sales_items si 
          LEFT JOIN products p ON si.product_id = p.id 
          WHERE si.sale_id = :sale_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':sale_id', $sale['id']);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get settings
$settings = getSettings($db);

// Calculate subtotal
$subtotal = $sale['total_amount'] - $sale['tax_amount'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $transaction_id; ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            max-width: 300px;
            margin: 0 auto;
            padding: 20px;
            font-size: 12px;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 20px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .dashed-line {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        @media print {
            body {
                margin: 0;
                padding: 10px;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-header">
        <h2 style="margin: 0;"><?php echo htmlspecialchars($settings['store_name']); ?></h2>
        <?php if ($settings['store_address']): ?>
            <p style="margin: 5px 0;"><?php echo htmlspecialchars($settings['store_address']); ?></p>
        <?php endif; ?>
        <?php if ($settings['store_phone']): ?>
            <p style="margin: 5px 0;">Tel: <?php echo htmlspecialchars($settings['store_phone']); ?></p>
        <?php endif; ?>
        <?php if ($settings['receipt_header']): ?>
            <p style="margin: 10px 0;"><?php echo nl2br(htmlspecialchars($settings['receipt_header'])); ?></p>
        <?php endif; ?>
    </div>

    <div class="dashed-line"></div>

    <div class="center">
        <p><strong>TRANSACTION: <?php echo $transaction_id; ?></strong></p>
        <p>Date: <?php echo date('M d, Y H:i', strtotime($sale['created_at'])); ?></p>
        <p>Cashier: <?php echo htmlspecialchars($sale['username'] ?? 'Unknown'); ?></p>
    </div>

    <div class="dashed-line"></div>

    <div>
        <?php foreach ($items as $item): ?>
            <div class="item-row">
                <span><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                <span>₱<?php echo number_format($item['total_price'], 2); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="dashed-line"></div>

    <div class="right">
        <div class="item-row">
            <span>Subtotal:</span>
            <span>₱<?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="item-row">
            <span>Tax:</span>
            <span>₱<?php echo number_format($sale['tax_amount'], 2); ?></span>
        </div>
        <div class="total-row">
            <span>TOTAL:</span>
            <span>₱<?php echo number_format($sale['total_amount'], 2); ?></span>
        </div>
        <div class="item-row">
            <span>Payment:</span>
            <span><?php echo strtoupper($sale['payment_method']); ?></span>
        </div>
        <?php if ($sale['payment_method'] === 'cash'): ?>
            <div class="item-row">
                <span>Tendered:</span>
                <span>₱<?php echo number_format($sale['amount_paid'], 2); ?></span>
            </div>
            <div class="item-row">
                <span>Change:</span>
                <span>₱<?php echo number_format($sale['change_amount'], 2); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashed-line"></div>

    <div class="receipt-footer">
        <?php if ($settings['receipt_footer']): ?>
            <p><?php echo nl2br(htmlspecialchars($settings['receipt_footer'])); ?></p>
        <?php endif; ?>
        <p style="margin-top: 10px;">Thank you for your purchase!</p>
        <p style="margin-top: 5px;">Please come again</p>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button onclick="window.print()" style="background: #3B82F6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <button onclick="window.close()" style="background: #6B7280; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            <i class="fas fa-times"></i> Close
        </button>
    </div>
</body>

</html>