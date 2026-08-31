<?php

require 'vendor/autoload.php';

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

class OpenFoodGetBarcode
{
  private Client $client;

  public function __construct(string $countryCode = 'id')
  {
    $this->client = new Client([
      'base_uri' => "https://{$countryCode}.openfoodfacts.org",
      'timeout' => 10.0,
      'headers' => [
        // Identifikasi aplikasi Anda (Wajib untuk Open Food Facts)
        'User-Agent' => 'MoBelanjaApp - PHP/' . PHP_VERSION . ' - Contact: mobelanjaapp@gmail.com'
      ]
    ]);
  }

  public function getProductsByBarcodes(array $barcodes): array
  {
    if (empty($barcodes)) {
      return [];
    }

    // 1. Tentukan field yang HANYA ingin Anda ambil (Hemat Bandwidth)
    $fields = implode(',', [
      'code',
      'product_name',
      'brands',
      '_keywords',
      'image_front_url',
      'selected_images'
    ]);

    try {
      // 2. Kirim request ke endpoint /api/v2/search
      $response = $this->client->get('/api/v2/search', [
        'query' => [
          'code' => implode(',', $barcodes),  // Barcode dipisahkan koma
          'fields' => $fields,  // Filter field spesifik
          'page_size' => count($barcodes)  // Jumlah produk yang dikembalikan per halaman
        ]
      ]);

      $data = json_decode($response->getBody()->getContents(), true);

      $Products = [];

      // 3. Format hasil ke bentuk array yang bersih
      if (isset($data['products']) && is_array($data['products'])) {
        foreach ($data['products'] as $p) {
          $Products[] = [
            'code' => $p['code'] ?? null,
            'product_name' => $p['product_name'] ?? 'Tidak Ada Nama',
            'brands' => $p['brands'] ?? null,
            'keywords' => $p['_keywords'] ?? [],
            'images' => [
              'front' => $p['image_front_url'] ?? null,
              'gallery' => $p['selected_images'] ?? []
            ]
          ];
        }
      }

      return $Products;
    } catch (RequestException $e) {
      error_log('API Error: ' . $e->getMessage());
      return [];
    }
  }
}
