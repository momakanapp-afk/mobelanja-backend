<?php 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Tangani permintaan HTTP OPTIONS (Preflight)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$sendMethod = $_SERVER['REQUEST_METHOD'];
$allowed = explode(',',"GET,POST,PUT,DELETE,PATCH");
if (!in_array($sendMethod,$allowed)) {
    echo json_encode(['Error send method not allowed']);
    exit;
}

?>