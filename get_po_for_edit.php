<?php
include "db.php";
header("Content-Type: application/json; charset=UTF-8");

$id = $_GET['id'] ?? 0;

/* ===== HEADER ===== */
$h = $conn->prepare(
    "SELECT voucher_no, po_date, supplier_name
     FROM purchase_orders
     WHERE id = ?"
);
$h->bind_param("i", $id);
$h->execute();
$header = $h->get_result()->fetch_assoc();

/* ===== ITEMS ===== */
$i = $conn->prepare(
    "SELECT serial_no, description, quantity
     FROM purchase_order_items
     WHERE po_id = ?"
);
$i->bind_param("i", $id);
$i->execute();
$res = $i->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode([
    "header" => $header,
    "items" => $items
]);
