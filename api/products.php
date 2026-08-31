<?php 

require_once __DIR__ . '/ClerkAuth.php';

// 1. Jalankan verifikasi
// Jika token invalid/missing, script langsung mati dan me-return HTTP 401 otomatis
$userToken = ClerkAuth::authenticate();

// 2. Ambil data dari payload JWT jika token VALID
$clerkUserId = $userToken->sub; // Subject ID unik dari Clerk (contoh: "user_2N...")

// 3. Lanjutkan ke logika bisnis Anda (Query DB, dll)
$userData = [
    'user_id' => $clerkUserId,
    'issued_at' => date('Y-m-d H:i:s', $userToken->iat),
    'expires_at' => date('Y-m-d H:i:s', $userToken->exp),
];

echo json_encode([
    'user' => $userData
]);

?>