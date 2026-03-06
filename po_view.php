<?php
include "db.php";

$po_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($po_id <= 0){
    die("Invalid PO ID");
}

/* ---------- FETCH PO DETAILS ---------- */
$po_sql = "SELECT * FROM purchase_orders WHERE id = $po_id";
$po_result = mysqli_query($conn, $po_sql);
$po = mysqli_fetch_assoc($po_result);

if(!$po){
    die("PO not found");
}

/* ---------- FETCH PO ITEMS ---------- */
$item_sql = "SELECT * FROM purchase_order_items WHERE po_id = $po_id";
$item_result = mysqli_query($conn, $item_sql);

/* ---------- FETCH GRN RECORDS ---------- */
$grn_sql = "SELECT * FROM grn_entries WHERE po_id = $po_id ORDER BY created_at ASC";
$grn_result = mysqli_query($conn, $grn_sql);

$grnData = [];
while($row = mysqli_fetch_assoc($grn_result)){
    $grnData[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Purchase Order View</title>

<style>
body { font-family: Arial; background:#f4f6f8; padding:20px; }
.section-box {
    background:#fff;
    padding:15px;
    margin-bottom:15px;
    border-radius:5px;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
}
.po-table {
    width:100%;
    border-collapse:collapse;
}
.po-table th, .po-table td {
    border:1px solid #ccc;
    padding:8px;
    text-align:center;
}
.po-table th { background:#f2f2f2; }
.blue-btn {
    background:#007bff;
    color:#fff;
    padding:10px 25px;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
.blue-btn:hover { background:#0056b3; }
</style>
</head>

<body>

<div class="section-box">
    <h2 style="text-align:center;">Purchase Order View</h2>
</div>

<div class="section-box">
    <p><b>Voucher No:</b> <?= $po['voucher_no'] ?></p>
    <p><b>Date:</b> <?= $po['po_date'] ?></p>
    <p><b>Supplier:</b> <?= $po['supplier'] ?></p>
</div>

<div class="section-box">
    <h3>PO Items</h3>
    <table class="po-table">
        <tr>
            <th>Sl No</th>
            <th>Description</th>
            <th>Qty</th>
            <th>Per</th>
            <th>Pending</th>
        </tr>

        <?php
        $sl = 1;

        while($item = mysqli_fetch_assoc($item_result)){

            $actualQty = $item['quantity'];

            // calculate total received for this item
            $totalReceived = 0;
            foreach($grnData as $grn){
                if($grn['description'] == $item['description']){
                    $totalReceived += $grn['received_qty'];
                }
            }

            $pending = $actualQty - $totalReceived;
            if($pending < 0) $pending = 0;

            echo "<tr>
                <td>{$sl}</td>
                <td>{$item['description']}</td>
                <td>{$actualQty}</td>
                <td>{$item['per']}</td>
                <td>{$pending}</td>
            </tr>";

            $sl++;
        }
        ?>
    </table>
</div>

<div class="section-box">
    <h3>GRN Submitted Details</h3>

    <table class="po-table">
        <tr>
            <th>Sl No</th>
            <th>Description</th>
            <th>PO Qty</th>
            <th>Received Qty</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Submitted By</th>
            <th>Date & Time</th>
            <th>Completed</th>
        </tr>

        <?php
        $sl = 1;

        // create total map
        $totalMap = [];
        foreach($grnData as $g){
            $desc = $g['description'];
            if(!isset($totalMap[$desc])){
                $totalMap[$desc] = 0;
            }
            $totalMap[$desc] += $g['received_qty'];
        }

        foreach($grnData as $index => $grn){

            $completion = "";

            // get actual PO qty
            $actualSql = "SELECT quantity FROM purchase_order_items 
                          WHERE po_id=$po_id AND description='".$grn['description']."'";
            $actualRes = mysqli_query($conn, $actualSql);
            $actualRow = mysqli_fetch_assoc($actualRes);

            if($actualRow){
                $actualQty = $actualRow['quantity'];

                if($totalMap[$grn['description']] >= $actualQty){
                    $completion = "<span style='color:green;font-weight:bold;'>Completed</span>";
                }
            }

            $statusColor = ($grn['status'] == "Accepted") ? "green" : "red";

            echo "<tr>
                <td>{$sl}</td>
                <td>{$grn['description']}</td>
                <td>{$grn['qty']}</td>
                <td>{$grn['received_qty']}</td>
                <td style='color:$statusColor;'>{$grn['status']}</td>
                <td>{$grn['remarks']}</td>
                <td>{$grn['updated_by']}</td>
                <td>{$grn['created_at']}</td>
                <td>{$completion}</td>
            </tr>";

            $sl++;
        }
        ?>
    </table>
</div>

<div style="text-align:center;margin-top:15px;">
    <button class="blue-btn"
        onclick="window.location.href='invoice_entry.php?id=<?= $po_id ?>'">
        Enter New Invoice Entry
    </button>
</div>

</body>
</html>