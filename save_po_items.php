<?php
include "db.php";
header("Content-Type: application/json");

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Validate data
if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

$po_id        = intval($data['po_id'] ?? 0);
$invoice_no   = $data['invoice_no'] ?? '';
$submitted_by = $data['submitted_by'] ?? '';
$submitted_at = date("Y-m-d H:i:s");
$items        = $data['items'] ?? [];

if ($po_id <= 0 || empty($items)) {
    echo json_encode(["status" => "error", "message" => "Invalid PO data"]);
    exit;
}

$conn->begin_transaction();

try {

    // 1️⃣ Update purchase_orders table
    $stmt = $conn->prepare("
        UPDATE purchase_orders
        SET supplier_invoice_no = ?, 
            submitted_by = ?, 
            submitted_at = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        throw new Exception("PO Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssi", $invoice_no, $submitted_by, $submitted_at, $po_id);
    $stmt->execute();
    $stmt->close();

    // 2️⃣ Update purchase_order_items
    foreach ($items as $item) {

        $item_id  = intval($item['item_id'] ?? 0);
        $accepted = intval($item['accepted'] ?? 0);
        $rejected = intval($item['rejected'] ?? 0);
        $pending  = intval($item['pending'] ?? 0);
        $condition = $item['condition'] ?? '';
        $remarks   = $item['remarks'] ?? '';

        if ($item_id <= 0) {
            throw new Exception("Invalid item ID");
        }

        // 🔍 First check if item exists with this po_id
        $check = $conn->prepare("
            SELECT id FROM purchase_order_items 
            WHERE id = ? AND po_id = ?
        ");

        if (!$check) {
            throw new Exception("Check Prepare failed: " . $conn->error);
        }

        $check->bind_param("ii", $item_id, $po_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Item ID $item_id does not belong to PO ID $po_id");
        }

        $check->close();

        // ✅ Now update
        $stmt2 = $conn->prepare("
            UPDATE purchase_order_items
            SET accepted_qty = ?,
                rejected_qty = ?,
                pending_qty  = ?,
                item_condition = ?,
                remarks = ?
            WHERE id = ? AND po_id = ?
        ");

        if (!$stmt2) {
            throw new Exception("Item Update Prepare failed: " . $conn->error);
        }

        $stmt2->bind_param(
            "iiissii",
            $accepted,
            $rejected,
            $pending,
            $condition,
            $remarks,
            $item_id,
            $po_id
        );

        $stmt2->execute();
        $stmt2->close();
    }

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Purchase Order Saved Successfully"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
