<?php 

// Tentukan Origin yang Diizinkan (Origin yang berasal dari Client)
$allowed_origins = [
    'null', // Untuk file:// akses
    'https://localhost',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? ''; 
// Cek apakah Origin yang meminta diizinkan
if (in_array($origin, $allowed_origins) || $origin === 'null') 
{
    // 1. Tentukan Origin yang Diterima (WAJIB spesifik, tidak boleh '*')
    header("Access-Control-Allow-Origin: " . $origin);

    // 2. Izinkan Kredensial (WAJIB untuk cookies)
    header("Access-Control-Allow-Credentials: true");

    // 3. Izinkan Metode dan Header
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");

    // Tangani permintaan OPTIONS (preflight)
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
}


?>