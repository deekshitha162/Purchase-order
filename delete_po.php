<?php
include "db.php";
header("Content-Type: application/json; charset=UTF-8");

$id = $_GET['id'] ?? 0;

if ($id == 0) {
    echo json_encode(["success" => false, "error" => "Invalid PO ID"]);
    exit;
}

// Delete items first
$conn->query("DELETE FROM purchase_order_items WHERE po_id = $id");

// Delete PO header
if ($conn->query("DELETE FROM purchase_orders WHERE id = $id")) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>
