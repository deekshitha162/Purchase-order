<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";

$q = $_GET['q'] ?? $_GET['term'] ?? '';

if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT DISTINCT voucher_no
    FROM purchase_orders
    WHERE CAST(voucher_no AS CHAR) LIKE ?
    ORDER BY voucher_no
    LIMIT 10
");

$search = "%{$q}%";   // 🔥 THIS IS THE KEY
$stmt->bind_param("s", $search);
$stmt->execute();

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row['voucher_no'];
}

echo json_encode($data);
