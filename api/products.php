<?php

include __DIR__.'/header_cors.php';

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/ClerkAuth.php';
include_once __DIR__ . '/class/class.globalinfo.php';


$gi = new globalInfo();
$db = $gi->getMysqli();

use Sqids\Sqids;
$sqids = new Sqids(minLength: 10, alphabet:"iwf2UnDlRmsKSI0XTxqMvZ84y1EWkVrejzGhNAY9pFPbCQ37gJodOtcLH56aBu");

// 1. Jalankan verifikasi
// Jika token invalid/missing, script langsung mati dan me-return HTTP 401 otomatis
$userToken = ClerkAuth::authenticate();

// // 2. Ambil data dari payload JWT jika token VALID
$clerkUserId = $userToken->sub;  // Subject ID unik dari Clerk (contoh: "user_2N...")

// SUCCESS TEST !
// user_3IZkCyaT8suoAKJYva3hEOsPlsA

$formatRp = function($nomi) {
  $nfo = 'Rp.'. number_format($nomi,0,',','.');
  return $nfo;
};

// Dipanggil dengan POST 
$rawInput = file_get_contents('php://input');
$_POST = json_decode($rawInput, true);

$colSel = "_id,name,description,price,stock,category,images,averageRating,totalReviews";

if (!isset($_POST['searchq'])) {
  // Ambil 100 data terbaru
  $qry = "
    SELECT $colSel 
    FROM product 
    ORDER BY _id DESC LIMIT 100
  ";
  $stmt = $db->prepare($qry);
  $stmt->execute();
  $stmt->bind_result(
    $idprod,$name,$desc,$price,$stock,$kateg,$img,$rating,$review
  );
}
else {
  // Pencarian fulltext index 
  $qry = "
  SELECT $colSel , 
    MATCH(name,description,category) AGAINST(? IN BOOLEAN MODE) AS skorcari 
  FROM product 
  WHERE MATCH(name,description,category) AGAINST(? IN BOOLEAN MODE) 
  ORDER BY skorcari DESC LIMIT 100
  ";
  $stmt = $db->prepare($qry);
  $stmt->bind_param('ss',$pq1,$pq2);
  $pq1 = $gi->fullTextQuery($_POST['searchq'],true);
  $pq2 = $gi->fullTextQuery($_POST['searchq']);
  $stmt->execute();
  $stmt->bind_result(
    $idprod,$name,$desc,$price,$stock,$kateg,$img,$rating,$review,$skor
  );
}


$produk = [];

while ($stmt->fetch()) {
  $produk[] = [
    '_id' => $sqids->encode([$idprod]),
    'name' => $name,
    'description' => $desc,
    'price' => $formatRp($price),
    'stock' => $stock,
    'category' => $kateg,
    'images' => [$img],
    'averageRating' => $rating,
    'totalReviews' => $review,
  ];
}
$stmt->close();

echo json_encode($produk);
exit();

?>