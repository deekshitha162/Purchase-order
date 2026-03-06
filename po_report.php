<?php
include "db.php";

/* Filters */
$fromDate = $_GET['fromDate'] ?? '';
$toDate   = $_GET['toDate'] ?? '';
$supplier = $_GET['supplier'] ?? '';

$where = [];
$params = [];
$types = "";

/* Date filter */
if ($fromDate && $toDate) {
    $where[] = "po.po_date BETWEEN ? AND ?";
    $params[] = $fromDate;
    $params[] = $toDate;
    $types .= "ss";
}

/* Supplier search */
if ($supplier) {
    $where[] = "po.supplier LIKE ?";
    $params[] = "%$supplier%";
    $types .= "s";
}

$sql = "
    SELECT 
        po.id,
        po.voucher,
        po.po_date,
        po.supplier,
        COUNT(poi.id) AS total_items
    FROM purchase_orders po
    LEFT JOIN purchase_order_items poi 
        ON poi.purchase_order_id = po.id
";

/* Apply filters */
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY po.id ORDER BY po.po_date DESC";

$stmt = $conn->prepare($sql);

/* Bind params dynamically */
if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order Report</title>
    <link rel="stylesheet" href="css/po_report.css">
</head>
<body>

<div class="page">

    <!-- HEADER -->
    <div class="header">
        <h1>Purchase Order Report</h1>
        <img src="images/CEPL.jpg" class="logo">
    </div>

    <!-- FILTERS -->
    <div class="date-filters">
        <div>
            <label>From Date</label>
            <input type="date" id="fromDate">
        </div>
        <div>
            <label>To Date</label>
            <input type="date" id="toDate">
        </div>
    </div>

    <div class="search-section">
        <input type="text" id="supplierSearch" placeholder="Search by supplier">
        <button onclick="loadPO()">Search</button>
    </div>

    <!-- TABLE -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>PO No</th>
                    <th>PO Date</th>
                    <th>Supplier</th>
                    <th>Total Items</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['voucher']) ?></td>
                            <td><?= date("d-m-Y", strtotime($row['po_date'])) ?></td>
                            <td><?= htmlspecialchars($row['supplier']) ?></td>
                            <td><?= $row['total_items'] ?></td>
                            <td>
                                <button onclick="viewPO(<?= $row['id'] ?>)">View</button>
                                <button onclick="updatePO(<?= $row['id'] ?>)">Update</button>
                                <button onclick="deletePO(<?= $row['id'] ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
<script src="js/po_report.js"></script>
</body>
</html>
