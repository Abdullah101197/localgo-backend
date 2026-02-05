<?php
// Standalone CORS Test File
// Upload this to main public folder as 'test_cors.php'

// 1. Set Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 2. Handle Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo "CORS Preflight OK";
    exit;
}

// 3. Normal Response
header("Content-Type: application/json");
echo json_encode([
    "status" => "success",
    "message" => "CORS headers are working on this server.",
    "server_time" => date("Y-m-d H:i:s")
]);
