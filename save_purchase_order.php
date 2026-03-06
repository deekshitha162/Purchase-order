<?php
include "db.php";
header("Content-Type: application/json; charset=UTF-8");

/* ================= READ JSON ================= */
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "msg" => "Invalid JSON received"
    ]);
    exit;
}

$voucher  = trim($data['voucher'] ?? '');
$date     = trim($data['date'] ?? '');
$supplier = trim($data['supplier'] ?? '');
$items    = $data['items'] ?? [];

/* ================= VALIDATION ================= */
if ($voucher === '' || $date === '' || $supplier === '' || empty($items)) {
    echo json_encode([
        "status" => "error",
        "msg" => "Missing required fields"
    ]);
    exit;
}

/* ================= CHECK DUPLICATE VOUCHER ================= */
$checkStmt = $conn->prepare(
    "SELECT id FROM purchase_orders WHERE voucher_no = ? LIMIT 1"
);

if (!$checkStmt) {
    echo json_encode([
        "status" => "error",
        "msg" => "Prepare failed (voucher check)"
    ]);
    exit;
}

$checkStmt->bind_param("s", $voucher);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {

    echo json_encode([
        "status" => "error",
        "msg" => "⚠ This voucher number is already used"
    ]);

    $checkStmt->close();
    $conn->close();
    exit;
}

$checkStmt->close();

/* ================= TRANSACTION ================= */
$conn->begin_transaction();

try {

    /* ================= INSERT PURCHASE ORDER ================= */
    $stmt = $conn->prepare(
        "INSERT INTO purchase_orders (voucher_no, po_date, supplier_name)
         VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception("Prepare failed (purchase_orders)");
    }

    $stmt->bind_param("sss", $voucher, $date, $supplier);
    $stmt->execute();

    $po_id = $stmt->insert_id;
    $stmt->close();

    /* ================= INSERT ITEMS ================= */
    $itemStmt = $conn->prepare(
        "INSERT INTO purchase_order_items
         (po_id, serial_no, description, quantity, per)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$itemStmt) {
        throw new Exception("Prepare failed (items)");
    }

    $serial_no = 1;

    foreach ($items as $item) {

        $desc = trim($item['description'] ?? '');
        $qty  = (int)($item['qty'] ?? 0);
        $per  = trim($item['per'] ?? '');

        if ($desc === '' || $qty <= 0 || $per === '') {
            continue;
        }

        $itemStmt->bind_param(
            "iisis",
            $po_id,
            $serial_no,
            $desc,
            $qty,
            $per
        );

        $itemStmt->execute();
        $serial_no++;
    }

    $itemStmt->close();
    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Purchase Order saved successfully"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "msg" => "Database error",
        "error" => $e->getMessage()
    ]);
}

$conn->close();
?>
