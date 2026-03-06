<?php
include "db.php";
header("Content-Type: application/json");

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$voucher = trim($data['voucher'] ?? '');

if ($voucher == '') {
    echo json_encode(["status" => "error"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM purchase_orders WHERE voucher_no = ?");
$stmt->bind_param("s", $voucher);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["status" => "duplicate"]);
} else {
    echo json_encode(["status" => "ok"]);
}

$stmt->close();
$conn->close();
?>
