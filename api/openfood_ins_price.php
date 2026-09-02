<?php

include_once __DIR__ . '/class/class.globalinfo.php';

$gi = new globalInfo();
$db = $gi->getMysqli();

$barcode = explode(',', '8992802512114,8997014021850,8993110003646,0089686060744,8997004306103,8992933237115');

$harga = [
  4900.00,
  1300.00,
  7500.00,
  18500.00,
  1000.00,
  7500.00,
];

$hargabarang = [];
foreach ($barcode as $ky=>$vl)
  {
    $hargabarang[$vl] = $harga[$ky];
  }

$hs = $db->query("SELECT * FROM Product WHERE price IS NULL");
while ($row = $hs->fetch_assoc()) {
  $harga = $hargabarang[$row['barcode']];
  $rating = random_int(1, 5);
  $jmlrev = random_int(100, 200);
  $db->query("UPDATE product 
  SET price = '$harga' , averageRating = '$rating', totalReviews = '$jmlrev',
   stock = '1' 
  WHERE barcode = '{$row['barcode']}'

  ");
}

?>