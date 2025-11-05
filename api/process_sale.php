<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['items']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    $db->beginTransaction();

    // Generate transaction ID
    $transaction_id = generateTransactionId();

    // Calculate amounts
    $subtotal = $data['subtotal'];
    $tax = $data['tax'];
    $total = $data['total'];
    $payment_method = $data['payment_method'];
    $amount_paid = $data['amount_paid'];
    $change_amount = $data['change_amount'];

    // Insert sale record
    $query = "INSERT INTO sales (transaction_id, user_id, total_amount, tax_amount, payment_method, amount_paid, change_amount) 
              VALUES (:transaction_id, :user_id, :total_amount, :tax_amount, :payment_method, :amount_paid, :change_amount)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':transaction_id', $transaction_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':total_amount', $total);
    $stmt->bindParam(':tax_amount', $tax);
    $stmt->bindParam(':payment_method', $payment_method);
    $stmt->bindParam(':amount_paid', $amount_paid);
    $stmt->bindParam(':change_amount', $change_amount);
    $stmt->execute();

    $sale_id = $db->lastInsertId();

    // Insert sale items and update stock
    foreach ($data['items'] as $item) {
        // Insert sale item
        $query = "INSERT INTO sales_items (sale_id, product_id, quantity, unit_price, total_price) 
                  VALUES (:sale_id, :product_id, :quantity, :unit_price, :total_price)";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':sale_id', $sale_id);
        $stmt->bindParam(':product_id', $item['id']);
        $stmt->bindParam(':quantity', $item['quantity']);
        $stmt->bindParam(':unit_price', $item['price']);
        $total_price = $item['price'] * $item['quantity'];
        $stmt->bindParam(':total_price', $total_price);
        $stmt->execute();

        // Update product stock
        $query = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':quantity', $item['quantity']);
        $stmt->bindParam(':product_id', $item['id']);
        $stmt->execute();
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'transaction_id' => $transaction_id,
        'message' => 'Sale processed successfully',
        'formatted' => [
            'subtotal' => '₱' . number_format($subtotal, 2),
            'tax' => '₱' . number_format($tax, 2),
            'total' => '₱' . number_format($total, 2),
            'amount_paid' => '₱' . number_format($amount_paid, 2),
            'change_amount' => '₱' . number_format($change_amount, 2),
        ]
    ]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error processing sale: ' . $e->getMessage()
    ]);
}
