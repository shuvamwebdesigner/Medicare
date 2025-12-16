<?php
include 'config/db.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? 'Personal Care';
$subcategory = $_GET['subcategory'] ?? null;

$query = "SELECT id, name, description, price, image FROM products WHERE category = ?";
$params = [$category];
$types = "s";

if ($subcategory) {
    $query .= " AND subcategory = ?";
    $params[] = $subcategory;
    $types .= "s";
}

$query .= " LIMIT 6";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

if (empty($products)) {
    echo json_encode(['error' => 'No products found']);
} else {
    echo json_encode($products);
}

$stmt->close();
$conn->close();
?>