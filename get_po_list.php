<?php
include "db.php";
header("Content-Type: application/json; charset=UTF-8");

$from    = $_GET['from']    ?? '';
$to      = $_GET['to']      ?? '';
$name    = $_GET['name']    ?? '';
$voucher = $_GET['voucher'] ?? '';

$sql = "
SELECT 
    po.id,
    po.voucher_no,
    po.po_date,
    po.supplier_name,

    CASE
        WHEN COUNT(poi.id) = COUNT(
            CASE 
                WHEN IFNULL(grn.total_received,0) >= poi.quantity 
                THEN 1 
            END
        )
        THEN 'FINISHED'
        ELSE 'PENDING'
    END AS po_status

FROM purchase_orders po

JOIN purchase_order_items poi 
    ON poi.po_id = po.id

LEFT JOIN (
    SELECT 
        po_id,
        description,
        SUM(received_qty) AS total_received
    FROM grn_entries
    WHERE status = 'Accepted'
    GROUP BY po_id, description
) grn
    ON grn.po_id = po.id
    AND grn.description = poi.description

WHERE 1=1
";

$params = [];
$types  = "";

/* DATE FILTER */
if ($from && $to) {
    $sql .= " AND DATE(po.po_date) BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
    $types   .= "ss";
}

/* SUPPLIER FILTER */
if ($name) {
    $sql .= " AND po.supplier_name LIKE ?";
    $params[] = "%$name%";
    $types   .= "s";
}

/* VOUCHER FILTER */
if ($voucher) {
    $sql .= " AND po.voucher_no LIKE ?";
    $params[] = "%$voucher%";
    $types   .= "s";
}

$sql .= " GROUP BY po.id ORDER BY po.po_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);