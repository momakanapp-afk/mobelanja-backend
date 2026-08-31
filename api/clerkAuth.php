<?php
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ClerkAuth
{
    // String Public Key PEM dari Clerk Dashboard
private static string $publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAw/s5RFkEZaASld5zPjWw
qNVVshVBVMA5L1OgShQxOEn3ZgtmkIu5q7Kv1ur6ciEIf3kt8A256+cNcDf4QgNV
9QD4dWNJWFDzh5byoMDegVjCMQymzgA5ZRSZRSrflROHlHKJ+Hh7LGl4pfYpxktJ
iFWqMuDmQu0Ydl0fJFFrELb2L1z/pyzEOm195+XbGk3xzGQPrwh0K0ZnVN2ipCku
3CCkOKHAhZojF7X5HPl8g797ati7h1QEHnmm49dJ2mDPGB5Pp03U/LASp+4A7gnY
sefhes00ppdSHuzMzi8/7Y4RGjcY2dixq9NO93ZrdoDd+dXSiI1j5E3/PS4+EBab
7wIDAQAB
-----END PUBLIC KEY-----
EOD;

    /**
     * Memverifikasi token JWT dari header Authorization.
     * Mengembalikan object payload decoded jika sukses.
     * Menghentikan script dan return HTTP 401 jika gagal.
     */
    public static function authenticate(): stdClass
    {
        // 1. Set Header CORS & Content-Type
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Authorization, Content-Type");
        header("Content-Type: application/json");

        // Tangani CORS Preflight Request dari Axios
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // 2. Tangkap Header Authorization
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] 
                   ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] 
                   ?? $_SERVER['HTTP_X_AUTHORIZATION'] 
                   ?? null;

        // Jika tidak ada header Authorization atau format bukan 'Bearer <token>'
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            self::sendError(401, 'Authorization token tidak ditemukan');
        }

        $jwt = $matches[1];

        try {
            // 3. Verifikasi & Decode JWT menggunakan Public Key (Algoritma RS256)
            $decodedPayload = JWT::decode($jwt, new Key(self::$publicKey, 'RS256'));

            return $decodedPayload;

        } catch (Exception $e) {
            // Token kedaluwarsa, tidak valid, atau signature mismatch
            self::sendError(401, 'Token tidak valid atau telah kedaluwarsa', $e->getMessage());
        }
    }

    /**
     * Helper untuk mengirimkan response error JSON dan menghentikan eksekusi
     */
    private static function sendError(int $statusCode, string $message, ?string $details = null): void
    {
        http_response_code($statusCode);
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if ($details) {
            $response['details'] = $details;
        }

        echo json_encode($response);
        exit; // Menghentikan eksekusi skrip PHP selanjutnya
    }
}

?>