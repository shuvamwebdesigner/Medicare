<?php
include 'config/db.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? 'Personal Care';

$stmt = $conn->prepare("SELECT DISTINCT subcategory FROM products WHERE category = ? AND subcategory IS NOT NULL AND subcategory != ''");
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();

$subcategories = [];
while ($row = $result->fetch_assoc()) {
    $subcategories[] = $row['subcategory'];
}

echo json_encode($subcategories);

$stmt->close();
$conn->close();
?>