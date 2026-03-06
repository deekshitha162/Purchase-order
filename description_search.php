<?php
// description_search.php
include "db.php";   // must create $conn (mysqli)

header("Content-Type: application/json; charset=UTF-8");

// --------------------
// Get query safely
// --------------------
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// If query empty → return empty array
if ($q === '') {
    echo json_encode([]);
    exit;
}

// --------------------
// Prepare SQL
// --------------------
$sql = "
    SELECT DISTINCT description
    FROM purchase_order_items
    WHERE description LIKE ?
    ORDER BY description ASC
    LIMIT 10
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // SQL error → return empty array (avoid breaking JS)
    echo json_encode([]);
    exit;
}

// --------------------
// Bind & Execute
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
    // Trim to avoid hidden spaces breaking selection
    $data[] = trim($row['description']);
}

// --------------------
// Cleanup
// --------------------
$stmt->close();
$conn->close();

// --------------------
// Return JSON
// --------------------
echo json_encode($data);
