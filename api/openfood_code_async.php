<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Exception\RequestException;

class OpenFoodFactsBulkService
{
    private Client $client;

    public function __construct(string $countryCode = 'id')
    {
        $this->client = new Client([
            'base_uri' => "https://{$countryCode}.openfoodfacts.org",
            'timeout'  => 5.0,
            'headers'  => [
                'User-Agent' => 'MoBelanjaApp - PHP/' . PHP_VERSION . ' - Contact: mobelanjaapp@gmail.com'
            ]
        ]);
    }

    /**
     * Mengambil banyak produk secara eksekusi paralel (Concurrent Requests)
     */
    public function getProductsByBarcodes(array $barcodes): array
    {
        if (empty($barcodes)) {
            return [];
        }

        $fields = implode(',', [
            'code',
            'product_name',
            'brands',
            '_keywords',
            'image_front_url',
            'selected_images'
        ]);

        $promises = [];

        // 1. Buat daftar antrean request secara asinkron (Promise)
        foreach ($barcodes as $barcode) {
            $promises[$barcode] = $this->client->getAsync("/api/v2/product/{$barcode}.json", [
                'query' => ['fields' => $fields]
            ]);
        }

        // 2. Eksekusi semua request secara bersamaan (paralel)
        $responses = Utils::settle($promises)->wait();

        $formattedProducts = [];

        // 3. Olah respon per barcode
        foreach ($responses as $barcode => $responseInfo) {
            if ($responseInfo['state'] === 'fulfilled') {
                $response = $responseInfo['value'];
                $data = json_decode($response->getBody()->getContents(), true);

                if (isset($data['status']) && $data['status'] === 1) {
                    $p = $data['product'];
                    $formattedProducts[$barcode] = [
                        'code'         => $p['code'] ?? $barcode,
                        'product_name' => $p['product_name'] ?? 'Tidak Ada Nama',
                        'brands'       => $p['brands'] ?? null,
                        'keywords'     => $p['_keywords'] ?? [],
                        'images'       => [
                            'front'       => $p['image_front_url'] ?? null,
                            'gallery'     => $p['selected_images'] ?? []
                        ]
                    ];
                } else {
                    // Produk tidak ditemukan di database
                    $formattedProducts[$barcode] = null;
                }
            } else {
                // Request gagal (contoh: koneksi error)
                $formattedProducts[$barcode] = null;
            }
        }

        return $formattedProducts;
    }
}

// ==========================================
// CARA PENGGUNAAN
// ==========================================

$bulkService = new OpenFoodFactsBulkService('id');

$barcodes = [
  "8997004306103",
];

$products = $bulkService->getProductsByBarcodes($barcodes);

header('Content-Type: application/json');
echo json_encode([
    'total_requested' => count($barcodes),
    'data'            => $products
], JSON_PRETTY_PRINT);