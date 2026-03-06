<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "purchase_order_db";

// Enable mysqli error reporting (development only)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (!$conn) {
    // For production, do not display detailed error
    die(json_encode(["status" => "error", "msg" => "Database connection failed"]));
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");
?>
