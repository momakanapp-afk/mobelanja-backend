<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Tangani permintaan HTTP OPTIONS (Preflight)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/ClerkAuth.php';
include_once __DIR__ . '/class/class.globalinfo.php';

$gi = new globalInfo();
$db = $gi->getMysqli();

// 1. Jalankan verifikasi
// Jika token invalid/missing, script langsung mati dan me-return HTTP 401 otomatis
$userToken = ClerkAuth::authenticate();

// // 2. Ambil data dari payload JWT jika token VALID
$clerkUserId = $userToken->sub;  // Subject ID unik dari Clerk (contoh: "user_2N...")

// SUCCESS TEST !
// user_3IZkCyaT8suoAKJYva3hEOsPlsA

// Dipanggil dengan POST 
$rawInput = file_get_contents('php://input');
$_POST = json_decode($rawInput, true);

if (!isset($_POST['searchq'])) {
  // Ambil 100 data terbaru
  $qry = 'SELECT * FROM product ORDER BY _id DESC LIMIT 100';
  $hs = $db->query($qry);
}
else {
  // Pencarian fulltext index 
  $qry = "
  SELECT p.*, 
  MATCH(name,description,category) AGAINST(? IN BOOLEAN MODE) AS skorcari 
  FROM product AS p 
  WHERE MATCH(name,description,category) AGAINST(? IN BOOLEAN MODE) 
  ORDER BY skorcari DESC LIMIT 100
  ";
  $stmt = $db->prepare($qry);
  $stmt->bind_param('ss',$pq1,$pq2);
  $pq1 = $gi->fullTextQuery($_POST['searchq'],true);
  $pq2 = $gi->fullTextQuery($_POST['searchq']);
  $stmt->execute();
  $hs = $stmt->get_result();
}


$produk = [];

$formatRp = function($nomi) {
  $nfo = 'Rp.'. number_format($nomi,0,',','.');
  return $nfo;
};

while ($row = $hs->fetch_assoc()) {
  $produk[] = [
    '_id' => $row['_id'],
    'name' => $row['name'],
    'description' => $row['description'],
    'price' => $formatRp($row['price']),
    'stock' => $row['stock'],
    'category' => $row['category'],
    'images' => [$row['images']],
    'averageRating' => $row['averageRating'],
    'totalReviews' => $row['totalReviews'],
  ];
}

echo json_encode($produk);
exit();

?>