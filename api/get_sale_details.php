<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Sale ID required']);
    exit();
}

$sale_id = intval($_GET['id']);

$database = new Database();
$db = $database->getConnection();

try {
    // Get sale details
    $query = "SELECT s.*, u.username FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $sale_id);
    $stmt->execute();
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        exit();
    }

    // Get sale items
    $query = "SELECT si.*, p.name FROM sales_items si 
              LEFT JOIN products p ON si.product_id = p.id 
              WHERE si.sale_id = :sale_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':sale_id', $sale_id);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format currency with peso sign
    foreach ($items as &$item) {
        $item['total_price_formatted'] = '₱' . number_format($item['total_price'], 2);
    }

    $sale['total_amount_formatted'] = '₱' . number_format($sale['total_amount'], 2);
    $sale['tax_amount_formatted'] = '₱' . number_format($sale['tax_amount'], 2);
    $sale['amount_paid_formatted'] = '₱' . number_format($sale['amount_paid'], 2);
    $sale['change_amount_formatted'] = '₱' . number_format($sale['change_amount'], 2);
    $sale['items'] = $items;

    echo json_encode(['success' => true, 'sale' => $sale]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
