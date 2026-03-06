<?php
include "db.php"; // provides $conn

header("Content-Type: application/json; charset=UTF-8");

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search === '') {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT DISTINCT supplier_name
    FROM purchase_orders
    WHERE supplier_name LIKE ?
    ORDER BY supplier_name ASC
    LIMIT 10
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([]);
    exit;
}

$like = "%" . $search . "%";
$stmt->bind_param("s", $like);
$stmt->execute();

$result = $stmt->get_result();

$suppliers = [];
while ($row = $result->fetch_assoc()) {
    $suppliers[] = $row['supplier_name'];
}

$stmt->close();
$conn->close();

echo json_encode($suppliers);

