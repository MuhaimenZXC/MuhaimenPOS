<?php
require_once __DIR__ . '/../config/config.php';
$database = new Database();
$db = $database->getConnection();
$barcode = $_GET['barcode'] ?? '';
$stmt = $db->prepare('SELECT id,name,price,image_path FROM products WHERE barcode = :barcode LIMIT 1');
$stmt->execute(['barcode' => $barcode]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($product ? ['success' => true, 'product' => $product] : ['success' => false]);
