<?php

require_once __DIR__ . '/vendor/autoload.php';
use Smalot\PdfParser\Parser;

header("Content-Type: application/json");

try {
    if (!isset($_FILES['pdf'])) {
        echo json_encode(["status" => "error", "message" => "No PDF uploaded"]);
        exit;
    }

    $tmp = $_FILES['pdf']['tmp_name'];

    /* PARSE PDF */
    $parser = new Parser();
    $pdf = $parser->parseFile($tmp);
    $text = $pdf->getText();

    /* CLEAN TEXT */
    // Normalize spaces but keep newlines for row processing
    $text = preg_replace('/[ \t]+/', ' ', $text); 
    
    /* DEBUG - Useful to see how the parser sees the table */
    file_put_contents("debug.txt", $text);

    /* ---------- VOUCHER ---------- */
    $voucher = "";
    if (preg_match('/POR\/\d{4}\/\d+/i', $text, $m)) {
        $voucher = $m[0];
    }

    /* ---------- DATE ---------- */
    $date = "";
    if (preg_match('/\d{1,2}-[A-Za-z]{3}-\d{2,4}/', $text, $m)) {
        $date = date("Y-m-d", strtotime($m[0]));
    }

    /* ---------- SUPPLIER ---------- */
    $supplier = "";
    if (preg_match('/Supplier\s*\(Bill\s*from\)\s*(.*?)\n/i', $text, $m)) {
        $supplier = trim($m[1]);
    }

    /* ---------- ITEMS ---------- */
    $items = [];
    $lines = explode("\n", $text);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // REGEX BREAKDOWN:
        // ^(\d+)          -> Matches S.No (e.g., 1) even if stuck to text
        // (.*?)           -> Matches Description (e.g., BRIDGE RECTIFIER)
        // [\d,]+\.\d{2}   -> Skips the first number (Amount: 5,250.00)
        // NO\'S           -> Skips the first NO'S
        // [\d,]+\.\d{2}   -> Skips the second number (Rate: 5.25)
        // ([\d,]+\.\d+)   -> CAPTURES THE THIRD NUMBER (Quantity: 1,000.0)
        // \s*NO\'S        -> Matches the NO'S after Quantity
        
                // Updated Pattern:
        // ([A-Z\']+) -> This captures NO'S, MTR, KG, G, etc.
        $pattern = '/^(\d+)(.*?)\s+[\d,]+\.\d{2}\s*([A-Z\']+)\s*[\d,]+\.\d{2}\s*([\d,]+\.\d+)\s*([A-Z\']+)/i';

        if (preg_match($pattern, $line, $matches)) {
            
            $sno         = $matches[1];
            $description = trim($matches[2]);
            $unit        = $matches[5]; // This captures MTR, KG, or NO'S from the Quantity part
            $qtyValue    = str_replace(',', '', $matches[4]); 

            $items[] = [
                "sno"         => $sno,
                "description" => $description,
                "qty"         => floatval($qtyValue),
                "per"         => $unit // Now it shows the actual unit from the PDF
            ];
        }

    }


    /* ---------- RETURN ---------- */
    echo json_encode([
        "status" => "success",
        "voucher" => $voucher,
        "date" => $date,
        "supplier" => $supplier,
        "items" => $items,
        "raw_count" => count($items)
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
