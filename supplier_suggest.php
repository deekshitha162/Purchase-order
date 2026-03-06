<?php
// supplier_suggest.php
header("Content-Type: application/json; charset=UTF-8");
include "db.php";   // must provide $conn (mysqli)

// --------------------
// Read & sanitize input
// --------------------
$q = '';

if (isset($_GET['q'])) {
    $q = trim($_GET['q']);
} elseif (isset($_GET['term'])) {
    $q = trim($_GET['term']);
}

// Empty query → empty JSON array
if ($q === '') {
    echo json_encode([]);
    exit;
}

// --------------------
// Prepare SQL
// --------------------
$sql = "
    SELECT DISTINCT supplier_name
    FROM purchase_orders
    WHERE supplier_name LIKE ?
    ORDER BY supplier_name ASC
    LIMIT 10
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Prevent broken JSON if SQL fails
    echo json_encode([]);
    exit;
}

// --------------------
// Bind & execute
// --------------------
$like = "%{$q}%";
$stmt->bind_param("s", $like);
$stmt->execute();

$result = $stmt->get_result();

// --------------------
// Fetch results
// --------------------
$data = [];

while ($row = $result->fetch_assoc()) {
    // trim avoids invisible spaces that break arrow selection
    $data[] = trim($row['supplier_name']);
}

// --------------------
// Cleanup
// --------------------
$stmt->close();
$conn->close();

// --------------------
// Output JSON
// --------------------
echo json_encode($data);
