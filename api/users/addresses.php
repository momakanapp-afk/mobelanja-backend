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

$formatRp = function($nomi) {
  $nfo = 'Rp.'. number_format($nomi,0,',','.');
  return $nfo;
};

// Dipanggil dengan POST 
$rawInput = file_get_contents('php://input');
$_POST = json_decode($rawInput, true);

$get_json_data = function() use($db,$sqids,$clerkUserId) {
  $qry = "
    SELECT * FROM address 
    WHERE clerkId = ? 
  ";
  $stmt = $db->prepare($qry);
  $stmt->bind_param('s',$clerkUserId);
  $stmt->execute();
  // mySqlnd feature
  $res = $stmt->get_result();

  $addre=[];
  while($item = $res->fetch_assoc()) {
    $addre[] = $item;
  }
  if (count($addre) > 0) {
    $addre = array_map(
      function ($item) use($sqids) {
        $item['_id'] = $sqids->encode([$item['_id']]);
        $item['isDefault'] = (bool)$item['isDefault']; // ubah ke true/false
        return $item;
      },$addre);
  }
  return $addre;
};

if ($sendMethod==="GET") 
{
  $ou = ['addresses'=>$get_json_data()];
  echo json_encode($ou);
  exit;
}

if ($sendMethod==="POST") 
{
  $postcol = "label,fullName,streetAddress,city,state,zipCode,phoneNumber,geolokasi,isDefault";

  // Proses geolokasi
  $geo_lat = $geo_long = '';
  if (!empty($_POST['geolokasi'])) {
    $geo_post = explode(', ', $_POST['geolokasi']);
    $geo_lat = $geo_post[0];
    $geo_long = $geo_post[1];
  }
  // pecah geolokasi jadi 2 
  $postcol = str_replace('geolokasi,','',$postcol);
  $postcol .= ",geo_lat,geo_long,clerkId";
  $postcol_a = explode(',',$postcol);
  $ttny = rtrim(str_repeat('?,',count($postcol_a)),',');

  // ganti post geolokasi  (PERHATIKAN URUTAN INSERT !)
  unset($_POST['geolokasi']);
  $_POST['geo_lat'] = $geo_lat;
  $_POST['geo_long'] = $geo_long;
  // masukkan clerkId
  $_POST['clerkId'] = $clerkUserId;
  // Ekstrak value post 
  $postdata = array_values($_POST);
  
  $qry = "INSERT INTO address ($postcol) VALUES ($ttny)";
  $hsl = $db->execute_query($qry,$postdata);

  $isInserted = false;
  if ($db->affected_rows > 0) {
    $isInserted = true;
  }

  $ou = ['addresses'=>$get_json_data(),'isInserted'=>$isInserted];
  echo json_encode($ou);

}



?>