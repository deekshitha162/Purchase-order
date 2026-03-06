<?php
include "db.php";
header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

$po_id = $data['po_id'];
$supplier = $data['supplier'];
$po_date = $data['po_date'];
$items = $data['items'];

/* UPDATE HEADER */
$h = $conn->prepare(
    "UPDATE purchase_orders
     SET supplier_name=?, po_date=?
     WHERE id=?"
);
$h->bind_param("ssi", $supplier, $po_date, $po_id);
$h->execute();

/* UPDATE ITEMS */
$i = $conn->prepare(
    "UPDATE purchase_order_items
     SET description=?, quantity=?
     WHERE po_id=? AND serial_no=?"
);

foreach ($items as $item) {
    $i->bind_param(
        "siii",
        $item['description'],
        $item['quantity'],
        $po_id,
        $item['serial_no']
    );
    $i->execute();
}

echo json_encode(["status" => "success"]);
