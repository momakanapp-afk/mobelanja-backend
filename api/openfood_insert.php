<?php 

include "openfood_code.php";
include_once __DIR__."/class/class.globalinfo.php";

$bulkService = new OpenFoodGetBarcode('id');
$gi = new globalInfo();
$db = $gi->getMysqli();

// Daftar barcode yang ingin di insert
$barcodes = [
  "8994171102477",
  "8992933236118",
  "8992741906265",
  "8997014450216",
  "8993200668861",
  "8992772586016",
];

$products = $bulkService->getProductsByBarcodes($barcodes);

if (count($products) === 0) {
  echo "{#] Tidak ada respon dari server, silahkan ulangi \n";
  return false;
}

// $fdata = file_get_contents('format_output.txt');
// $products = json_decode($fdata,true);

  $ins_brg = $db->prepare("
  INSERT INTO Product (barcode,name,description,category,images) 
  VALUES (?,?,?,'FOODS',?)
  ");
  $ins_brg->bind_param('ssss',$pbrc,$pnma,$pdsc,$pimg);

foreach ($products as $item) {
  $pbrc = $item['code'];
  $pnma = $item['product_name'] . ' ' .$item['brands'];
  $pdsc = $item['product_name'] . ' produk dari ' .$item['brands'];
  $pimg = $item['images']['front'];
  $ins_brg->execute();
}




?>