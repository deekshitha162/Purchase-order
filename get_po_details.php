<?php
include "db.php";
header("Content-Type: application/json; charset=UTF-8");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ================= PO HEADER ================= */
$headerSql = "
    SELECT voucher_no, po_date, supplier_name
    FROM purchase_orders
    WHERE id = ?
";

$headerStmt = $conn->prepare($headerSql);
$headerStmt->bind_param("i", $id);
$headerStmt->execute();
$header = $headerStmt->get_result()->fetch_assoc();
$headerStmt->close();

if (!$header) {
    echo json_encode(["error" => "Purchase order not found"]);
    exit;
}

/* ================= PO ITEMS ================= */
$itemSql = "
    SELECT 
        serial_no,
        description,
        quantity
    FROM purchase_order_items
    WHERE po_id = ?
";

$itemStmt = $conn->prepare($itemSql);
$itemStmt->bind_param("i", $id);
$itemStmt->execute();
$result = $itemStmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        "serial_no"  => $row["serial_no"],
        "description"=> $row["description"],
        "quantity"        => $row["quantity"]   // rename for frontend
    ];
}

$itemStmt->close();

/* ================= OUTPUT ================= */
echo json_encode([
    "header" => $header,
    "items"  => $items
]);
