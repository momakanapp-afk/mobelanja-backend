<?php 
include __DIR__.'/../header_cors.php';

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../ClerkAuth.php';
include_once __DIR__ . '/../class/class.globalinfo.php';


$gi = new globalInfo();
$db = $gi->getMysqli();

use Sqids\Sqids;
$sqids = new Sqids(minLength: 10, alphabet:"iwf2UnDlRmsKSI0XTxqMvZ84y1EWkVrejzGhNAY9pFPbCQ37gJodOtcLH56aBu");

$userToken = ClerkAuth::authenticate();

$clerkUserId = $userToken->sub; 

// Dipanggil dengan POST 
$rawInput = file_get_contents('php://input');
$_POST = json_decode($rawInput, true);

$postcol = "imageUrl,name,desc,kotakab,alamat,kodepos,kontak,geolokasi";
$postcol_a = explode(",",$postcol);

// Always Call GET 
$qry = "
SELECT * FROM toko WHERE clerkId = ?";
$hs = $db->execute_query($qry,[$clerkUserId]);
$merdata = [];
if ($hs->num_rows > 0) {
  $row = $hs->fetch_assoc();
    foreach ($postcol_a as $col) {
      $merdata[$col] = $row[strtolower($col)];
    }
  $geo = $row['geo_lat'] > 0 ? "" : $row['geo_lat'].', '.$row['geo_long'];
  $merdata['geolokasi'] = $geo;
  // Proses timestamp 
  $date = new DateTime($row['timestamps'], new DateTimeZone('UTC'));
  $date->setTimezone(new DateTimeZone('Asia/Jakarta'));
  $merdata['waktu'] = $date->format('Y-m-d');
}

if ($sendMethod==="GET")
{
  echo json_encode(['mdata'=>$merdata]);
}

if ($sendMethod==="POST" && $_POST['act']==='saveImageUrl') 
{
  $newUrl = $_POST['urlimg'];

  # Update image url 
  $qry = "
  UPDATE toko SET imageUrl = ? 
  WHERE clerkId = ?
  ";
  $db->execute_query($qry,[$newUrl,$clerkUserId]);
  $success = ($db->affected_rows > 0) ? true : false;

  echo json_encode(['isSuccess'=>$success,'imgUrl'=>$newUrl]);
  exit();
}

if ($sendMethod==="POST") 
{
  $formData = $_POST['data'];
  $imageURL = $_POST['urlimg'];

  // Proses Geolokasi
  $geo_lat = $geo_long = 0;
  if (!empty($formData['geolokasi'])) {
    $geo_post = explode(', ', $formData['geolokasi']);
    $geo_lat = $geo_post[0];
    $geo_long = $geo_post[1];
  }
  unset($formData['geolokasi']);

  $postcol = array_keys($formData); 
  $postcol = array_merge($postcol,["imageurl","clerkId","geo_lat","geo_long"]);
  // Tambahkan aphostrope 
  $postcol_fix = array_map(function($item){return '`'.$item.'`';},$postcol);
  $inscol = implode(",",$postcol_fix);
  $postdata = array_values($formData); 
  $postdata = array_merge($postdata,[$imageURL,$clerkUserId,$geo_lat,$geo_long]);
  $ttny = implode(',', array_fill(0, count($postcol), '?'));

  if ($_POST['act']==='newForm') {
    $qry = "INSERT INTO toko ({$inscol}) VALUES ($ttny)";
    $db->execute_query($qry,$postdata);
  }
  else if ($_POST['act']==='editForm') {
    // Susun SET
    $setupd = [];
    foreach ($postcol_fix as $kyc => $col) {
      if ($col==='`clerkId`') { // exclude
        $id_ar_clerk = $kyc;
        continue;
      }
      // Jika user tidak mengubah gambar
      if ($col==='`imageurl`') {
        if ($imageURL==='') {
          $id_ar_imgu = $kyc;
          continue;
        }
      }
      $setupd[] = "$col = ?";
    }
    $setupd_c = implode(",",$setupd);

    // Susun Values 
    unset($postdata[$id_ar_clerk]); // unset clerk id
    if (isset($id_ar_imgu)) {
      unset($postdata[$id_ar_imgu]);
    }
    $postdata[] = $clerkUserId; // append di akhir 

    $qry = "UPDATE toko SET $setupd_c WHERE clerkId=? ";
    $postdata = array_values($postdata); // ekstrak kembali (non-urut index membuat error)
    $db->execute_query($qry,$postdata);

  }


  $sukses = ($db->affected_rows > 0) ? true : false;
  echo json_encode(['isSuccess'=>$sukses]);
}




?>