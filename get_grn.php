<?php
include "db.php";

$po_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("
    SELECT 
        description,
        qty,
        received_qty,
        pending_qty,
        status,
        remarks,
        updated_by,
        supplier_invoice_number,
        created_at
    FROM grn_entries
    WHERE po_id = ?
");

$stmt->bind_param("i", $po_id);
$stmt->execute();

$result = $stmt->get_result();

$rows = [];

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);
?>