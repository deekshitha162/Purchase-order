<?php
include "db.php";

header('Content-Type: application/json');

$po_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($po_id <= 0) {
    echo json_encode(["error" => "Invalid PO ID"]);
    exit;
}

/* 1️⃣ Get PO Main Details */
$poQuery = mysqli_query($conn, "
    SELECT voucher_no, po_date, supplier_name
    FROM purchase_orders
    WHERE id = $po_id
");

if (mysqli_num_rows($poQuery) == 0) {
    echo json_encode(["error" => "PO not found"]);
    exit;
}

$poData = mysqli_fetch_assoc($poQuery);

/* 2️⃣ Get PO Items */
$itemQuery = mysqli_query($conn, "
    SELECT description, quantity, per
    FROM purchase_order_items
    WHERE po_id = $po_id
");

$items = [];

while ($row = mysqli_fetch_assoc($itemQuery)) {
    $items[] = $row;
}

/* 3️⃣ Return JSON */
echo json_encode([
    "voucher" => $poData['voucher_no'],
    "po_date" => $poData['po_date'],
    "supplier" => $poData['supplier_name'],
    "items" => $items
]);