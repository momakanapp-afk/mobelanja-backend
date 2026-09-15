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

// Decode ID 
$DATA_ID = (isset($_POST['id'])) ? $_POST['id'] : '';
if (!empty($DATA_ID)) {
  $decId = $sqids->decode($DATA_ID);
  $DATA_ID = $decId[0];
}

$getJsonData = function($limit=25,$idprod='') use ($db,$sqids,$clerkUserId) {

  // Deret kolom ini hanya untuk GET API (tidak dipakai oleh POST)
  $postcol = "_id,name,description,price,category,images";
  $postcol_a = explode(",",$postcol);

  // Ambil spesifik id
  $qid = "";
  if (!empty($idprod)) {
    $idprod_d = $sqids->decode($idprod);
    if (is_array($idprod_d)) {
      $idprod = $idprod_d[0];
      $qid = "AND _id = '{$idprod}' ";
    }
  }
  $qry = "
    SELECT * FROM product 
    WHERE Toko_id = (
      SELECT _id FROM toko WHERE clerkId = ?
    ) AND aktif = TRUE $qid 
    ORDER BY _id DESC
    LIMIT $limit
  ";
  $hs = $db->execute_query($qry,[$clerkUserId]);
  $prodData = [];
  if ($hs->num_rows > 0) {
    while ($row = $hs->fetch_assoc()) {
      foreach ($postcol_a as $vc) {
        // Encode id
        if ($vc==='_id') {
          $item['_id'] = $sqids->encode([$row['_id']]);
          continue;
        }
        // json images
        if ($vc==='images') {
          $item['images'] = json_decode($row['images'],true);
          continue;
        }

        $item[$vc] = $row[$vc];
      }
      $prodData[] = $item;
    }
  }
  return $prodData;
};

# ALWAYS GET DATA
if ($sendMethod==="GET")
{
  $mdata = $getJsonData();
  echo json_encode(['mdata'=>$mdata]);
}

# DELETE LIST PRODUCT (NON-AKTIF)
if ($sendMethod==="POST" && $_POST['act']==='delete') {
  $qry = "
  UPDATE product SET aktif = FALSE 
  WHERE `_id` = ? ";
  $db->execute_query($qry,[$DATA_ID]);
  $sukses = ($db->affected_rows > 0) ? true : false;

  echo json_encode(['isSuccess'=>$sukses]);
}

# SAVE / UPDATE PRODUCT
if ($sendMethod==="POST" && $_POST['act']==='saveData') {
  $ProductData = $_POST['data'];
  $idForm = $ProductData['_id'];
  // Hapus _id 
  unset($ProductData['_id']);
  // Ekstrak kolom tambahkan aphostrope
  $postcol_a = array_map(function($item) {
    return "`{$item}`";
  },array_keys($ProductData));
  $postcol = implode(',',$postcol_a);

  // Proses Toko_id
  $decTokoId = $sqids->decode($_POST['id_toko']);
  if (!isset($decTokoId[0])) {
    echo json_encode(['isSuccess'=>false]);
    exit();
  }
  $TokoID = $decTokoId[0];

  # ++++++++ INSERT NEW -> Jika _id di payload kosong 
  if ($idForm==='') 
  {
    $ttny = str_repeat('?,',count($postcol_a)) .'?'; // last '?' untuk toko_id
    // Produk baru images hanya dari imgUpl
    $jsonImages = json_encode($_POST['imgUpl']);
    // replace images dengan string json (kolom tipe json)
    $ProductData['images'] = $jsonImages;
    // Ekstrak values untuk query
    $valQry = array_values($ProductData);
    // Tambahkan id toko
    $postcol .= ',`Toko_id`';
    $valQry[] = $TokoID;

    $qry = "
    INSERT INTO product ($postcol) 
    VALUES ($ttny)
    ";
    $db->execute_query($qry,$valQry);
    $sukses = ($db->affected_rows > 0) ? true : false;

    echo json_encode(['isSuccess'=>$sukses]);

  }

    # ++++++++ UPDATE ITEM
  if ($idForm!=='') 
  {
    // Decode id
    $decId = $sqids->decode($idForm);
    if (!is_array($decId)) {
      echo json_encode(['isSuccess'=>false]);
    }
    $IDPROD = $decId[0];
    // Susun SET 
    $setQry_a=[];
    foreach($postcol_a as $col) {
      $setQry_a[] = "{$col}=?";
    }
    $setQry = implode(",",$setQry_a);
    // Terapkan deleted images terhadap prev images
    foreach ($ProductData['images'] as $ked=>$vde) {
      if (in_array($vde,$_POST['imgDeleted'])) {
        unset($ProductData['images'][$ked]);
      }
    }
    // Gabungkan array prev dengan uploaded image 
    $JoinImage = array_merge($ProductData['images'],$_POST['imgUpl']);
    $JJoinImage = json_encode($JoinImage);
    // ubah images ke json
    $ProductData['images'] = $JJoinImage;
    // Ekstrak values
    $valQry = array_values($ProductData);
    // Tambahkan Where product._id 
    $valQry[] = $IDPROD;

    $qry = "
    UPDATE product SET $setQry 
    WHERE _id = ?
    ";
    $db->execute_query($qry,$valQry);
    $sukses = ($db->affected_rows > 0) ? true : false;

    echo json_encode(['isSuccess'=>$sukses]);
  }
}


?>