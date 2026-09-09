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
$DATA_ID =$_POST['addrID'];
if (!empty($DATA_ID)) {
  $decId = $sqids->decode($DATA_ID);
  $DATA_ID = $decId[0];
}
$DATA_ADDR = $_POST['addData'];

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
    // Pre output
    $addre = array_map(
      function ($item) use($sqids) {
        $item['_id'] = $sqids->encode([$item['_id']]);
        $item['isDefault'] = (bool)$item['isDefault']; // ubah ke true/false
        if ($item['geo_lat']!='0.00000000') {
          $item['geolokasi'] = $item['geo_lat'].', '.$item['geo_long'];
        } else {
          $item['geolokasi'] = '';
        }
        return $item;
      },$addre);
      unset($addre['lat'],$addre['long']);
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

  // translate isDefault 
  $DATA_ADDR['isDefault'] = ($DATA_ADDR['isDefault']) ? 1 : 0;

  // Proses geolokasi
  $geo_lat = $geo_long = 0;
  if (!empty($DATA_ADDR['geolokasi'])) {
    $geo_post = explode(', ', $DATA_ADDR['geolokasi']);
    $geo_lat = $geo_post[0];
    $geo_long = $geo_post[1];
  }
  // pecah geolokasi jadi 2 
  $postcol = str_replace('geolokasi,','',$postcol);
  $postcol .= ",geo_lat,geo_long,clerkId";
  $postcol_a = explode(',',$postcol);
  $ttny = rtrim(str_repeat('?,',count($postcol_a)),',');
  // translate isDefault 
  $DATA_ADDR['isDefault'] = ($DATA_ADDR['isDefault']) ? 1 : 0;

  // ganti post geolokasi  (PERHATIKAN URUTAN INSERT !)
  unset($DATA_ADDR['geolokasi']);
  $DATA_ADDR['geo_lat'] = $geo_lat;
  $DATA_ADDR['geo_long'] = $geo_long;
  // masukkan clerkId
  $DATA_ADDR['clerkId'] = $clerkUserId;
  // Ekstrak value post 
  $postdata = array_values($DATA_ADDR);

  // Jika disetel default non-aktifkan dulu default yang lama
  if ($DATA_ADDR['isDefault']) {
    $db->query("UPDATE address SET isDefault=FALSE WHERE isDefault");
  }
  $db->query("
  CREATE TEMPORARY TABLE tmp_address LIKE address");

  // MASUKKAN DATA KE TEMPORARY (Bisa diisi multi data untuk bulk insert)
  $qry = "
  INSERT INTO tmp_address ($postcol) VALUES ($ttny)";
  $hsl = $db->execute_query($qry,$postdata);

  // INSERT NEW ADDRESS
  // Purpose for Demo Bulk Insert
  if ($DATA_ID==='') {
    # CEK + INSERT NEW USER Otomatis 
    include __DIR__.'/insertuser-auto.php';
    $qry = "
      INSERT INTO address ($postcol)
      SELECT $postcol
      FROM tmp_address
    ";
    $db->query($qry);
  } 
  else { // Update Data
    //Susun : label = ?, fullname=?
    $setSql = [];
    foreach(explode(',',$postcol) as $col) {
      $setSql[] = $col.'= ?';
    }
    $isetSql = implode(',',$setSql);
    $qry = "
      UPDATE address SET $isetSql 
      WHERE _id = ? ";
    // Inject id untuk WHERE
    $postdata[] = $DATA_ID;

    $db->execute_query($qry,$postdata);
  }

  $isInserted = false;
  if ($db->affected_rows > 0) {
    $isInserted = true;
  }

  $db->query("DROP TEMPORARY TABLE tmp_address");

  // Tidak perlu mengembalikan json tabel record
  $ou = ['isInserted'=>$isInserted];
  echo json_encode($ou);
}

if ($sendMethod==="DELETE") 
{
  $qry = "
  DELETE FROM address WHERE _id = ? ";
  $db->execute_query($qry,[$DATA_ID]);
  $isDeleted = false;
  if ($db->affected_rows > 0) {
    $isDeleted = true;
  }
  echo json_encode(['isDeleted'=>$isDeleted]);
}




?>