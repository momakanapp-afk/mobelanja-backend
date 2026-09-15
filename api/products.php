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
$clerkUserId = $userToken->sub; 

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

# ++++ ALL DATA TANPA FILTER +++++++
if (!isset($_POST['searchq'])) {
  $qry = "
  SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
      '_id', p._id,
      'toko_id', p.Toko_id,
      'toko_nama', t.name,
      'name', p.name,
      'description', p.description,
      'price', p.price,
      'stock', p.stock, 
      'category', p.category,
      'images', p.images,
      'averageRating', p.averageRating,
      'totalReviews', p.totalReviews
    )
    ORDER BY p._id DESC 
  ) AS items 
   FROM product AS p 
   LEFT JOIN toko AS t ON p.Toko_id = t._id 
   WHERE p.aktif = TRUE 
  LIMIT 25
  ";
  $hs = $db->query($qry);
  $plist = [];
  if ($hs->num_rows > 0) {
    $row = $hs->fetch_assoc();
    $plist = json_decode($row['items'],true);
    $Toko_id = [];
    // Encode id 
    foreach ($plist as $kp => $vi) {
      $encId = $sqids->encode([$vi['_id']]);
      $plist[$kp]['_id'] = $encId;
      // Filter id toko
      if (!in_array($vi['toko_id'],$Toko_id)) {
        $Toko_id[] = $vi['toko_id'];
      }
      // decode json images 
      $plist[$kp]['images'] = json_decode($vi['images']);
    }
  }
  // Grup Toko 
  $ttny = str_repeat('?,',count($Toko_id));
  $ttny = rtrim($ttny,',');
  $qry = "
  SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
      '_id', t._id,
      'name', t.name,
      'image', t.imageurl,
      'kotakab', t.kotakab,
      'desc', t.desc
    )
  ) AS lstoko
  FROM toko AS t
  WHERE t._id IN ({$ttny})
  ";
  $hs = $db->execute_query($qry,$Toko_id);
  $tlist = [];
  if ($hs->num_rows > 0) {
    $row = $hs->fetch_assoc();
    $tlist = json_decode($row['lstoko'],true);
    // encode id
    foreach($tlist as $kl=>$vl) {
      $encId = $sqids->encode([$vl['_id']]);
      $tlist[$kl]['_id'] = $encId;
    }
  }

  echo json_encode(['plist'=>$plist,'tlist'=>$tlist]);
}

if (isset($_POST['searchq'])) {
  // Pencarian fulltext index 
  $qry = "
  SELECT $colSel , 
    MATCH(name,description,category) AGAINST(? IN BOOLEAN MODE) AS skorcari 
  FROM product 
  WHERE MATCH(name,description,category) AGAINST(? IN BOOLEAN MODE) 
    AND aktif = TRUE
  ORDER BY skorcari DESC LIMIT 25
  ";
  $stmt = $db->prepare($qry);
  $stmt->bind_param('ss',$pq1,$pq2);
  $pq1 = $gi->fullTextQuery($_POST['searchq'],true);
  $pq2 = $gi->fullTextQuery($_POST['searchq']);
  $stmt->execute();
  $stmt->bind_result(
    $idprod,$name,$desc,$price,$stock,$kateg,$img,$rating,$review,$skor
  );
  $produk = [];

  while ($stmt->fetch()) {
    // Tipe kolom json
    $e_img = json_decode($img,true);
    // Ambil hanya image pertama untuk thumbnail
    $img_a = [$e_img[0]];
    $produk[] = [
      '_id' => $sqids->encode([$idprod]),
      'name' => $name,
      'description' => $desc,
      'price' => $formatRp($price),
      'stock' => $stock,
      'category' => $kateg,
      'images' => $img_a,
      'averageRating' => $rating,
      'totalReviews' => $review,
    ];
  }
  $stmt->close();

  echo json_encode(['plist'=>$produk]);
  exit();
}

?>