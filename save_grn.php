<?php
include "db.php";
header("Content-Type: application/json");

// stop PHP warnings breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid input"
    ]);
    exit;
}

function extractNumber($value){
    return floatval(preg_replace("/[^0-9.]/", "", (string)$value));
}

foreach ($data as $row) {

    // ✅ MATCH JS KEYS EXACTLY
    $po_id   = intval($row['po_id']);
    $desc    = mysqli_real_escape_string($conn, $row['description']);
    $status  = mysqli_real_escape_string($conn, $row['status']);
    $remarks = mysqli_real_escape_string($conn, $row['remarks'] ?? '');
    $by      = mysqli_real_escape_string($conn, $row['updated_by']);   // ✅ FIXED
    $inv     = mysqli_real_escape_string($conn, $row['supplier_invoice_number']);

    $qty = extractNumber($row['qty']);

    /* ✅ BUSINESS LOGIC */
    if ($status === "Rejected") {
        $recv = 0;
        $pend = $qty;
    } else {
        $recv = extractNumber($row['received_qty']);   // ✅ FIXED
        $pend = extractNumber($row['pending']);
    }

    $sql = "
        INSERT INTO grn_entries
        (po_id, description, qty, received_qty, pending_qty,
         status, remarks, updated_by, supplier_invoice_number, created_at)
        VALUES
        ($po_id, '$desc', $qty, $recv, $pend,
         '$status', '$remarks', '$by', '$inv', NOW())
    ";

    if (!mysqli_query($conn, $sql)) {
        echo json_encode([
            "status" => "error",
            "sql_error" => mysqli_error($conn)
        ]);
        exit;
    }
}

echo json_encode(["status" => "success"]);