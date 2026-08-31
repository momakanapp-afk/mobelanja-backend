<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client();
$barcode = '8992775315057';

// Tentukan field spesifik yang HANYA ingin diunduh dari server
$afl = [
    "code",
    "product_name",
    "_keywords",
    "image_front_url",
    "image_ingredients_url",
    "image_nutrition_url",
    "selected_images"
];

$fields = implode(',',$afl);

try {
    // Request langsung ke endpoint API v2 dengan filter `fields`
    $response = $client->get("https://id.openfoodfacts.org/api/v2/product/{$barcode}.json", [
        'query' => [
            'fields' => $fields
        ]
    ]);

    $data = json_decode($response->getBody(), true);

    if (isset($data['product'])) {
        $product = $data['product'];

        $result = [
            'code'         => $product['code'] ?? null,
            'product_name' => $product['product_name'] ?? null,
            'keywords'     => $product['_keywords'] ?? [],
            'images'       => [
                'front'       => $product['image_front_url'] ?? null,
                'ingredients' => $product['image_ingredients_url'] ?? null,
                'nutrition'   => $product['image_nutrition_url'] ?? null,
                'gallery'     => $product['selected_images'] ?? []
            ]
        ];

        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        echo "Produk tidak ditemukan.";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}