<?php 

include __DIR__.'/header_cors.php';
// $sendMethod = "POST,GET,PUT,DELETE"

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/ClerkAuth.php';
include_once __DIR__ . '/class/class.globalinfo.php';


$gi = new globalInfo();
$db = $gi->getMysqli();

use Sqids\Sqids;
$sqids = new Sqids(minLength: 10, alphabet:"iwf2UnDlRmsKSI0XTxqMvZ84y1EWkVrejzGhNAY9pFPbCQ37gJodOtcLH56aBu");

$userToken = ClerkAuth::authenticate();

$clerkUserId = $userToken->sub;  // Subject ID unik dari Clerk

$rawInput = file_get_contents('php://input');
$_POST = json_decode($rawInput, true);

$getOutputData = function() use ($db,$sqids,$clerkUserId) {
  // Fungsi Muktahir JSON dari mariaDB
  // JSON_ARRAYAGG,JSON_ARRAY,JSON_OBJECT
  $qry = "
  SELECT c._id, 
  JSON_ARRAYAGG(
    JSON_OBJECT(
      '_id', ci._id,
      'quantity', ci.quantity,
      'product', JSON_OBJECT(
        '_id', p._id,
        'name', p.name,
        'price', p.price,
        'image', p.images
      )
    )
  ) AS items
  FROM cart AS c
  JOIN cartitem AS ci ON ci.Cart_id = c._id 
  JOIN product AS p ON ci.Product_id = p._id
  WHERE c._id = (
    SELECT _id FROM cart WHERE clerkId = ?
  )
  ";
  $stmt = $db->prepare($qry);
  $stmt->bind_param('s',$clerkUserId);
  $stmt->execute();
  $stmt->bind_result($idCart,$itemsCart);
  $stmt->fetch();

  $outputCart = [];
  if ($itemsCart!==null) {
    $items = json_decode($itemsCart,true);
    foreach ($items as $ki => $vi) {
      $items[$ki]['product']['_id'] = $sqids->encode([$vi['product']['_id']]);
    }
    $outputCart = [
      '_id'=>$sqids->encode([$idCart]),
      'clerkId'=> $clerkUserId,
      'items'=>$items,
    ];
  }

  return $outputCart;
};

// Handle payloads
if (in_array($sendMethod,['POST','PUT','DELETE'])) {
  $rawInput = file_get_contents('php://input');
  $_POST = json_decode($rawInput, true);
  $decId = $sqids->decode($_POST['id']);
  if (empty($decId)) {
    echo json_encode(['cart'=>$getOutputData()]);
    exit;
  }
  $productId = $decId[0];
}

// Hanya ambil data
if ($sendMethod==="GET") 
{
  echo json_encode(['cart'=>$getOutputData()]);
}

// Save Item to Cart
if ($sendMethod==="POST") 
{
  // Cek di cart 
  $stmt = $db->prepare("
  SELECT _id FROM cart WHERE clerkId = ?
  ");
  $stmt->bind_param('s',$clerkUserId);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows > 0) {
    $stmt->bind_result($idCart);
    $stmt->fetch();
    $stmt->close();
  } else {
    // Insert ke cart, ambil insert_id
    $stmt = $db->prepare("
      INSERT INTO cart (clerkId) VALUES (?)
    ");
    $stmt->bind_param('s',$clerkUserId);
    $stmt->execute();
    $idCart = $stmt->insert_id;
    $stmt->close();
  }

  // delete dulu jika data sudah ada
  $stmt = $db->prepare("
  DELETE FROM cartitem WHERE Cart_id = ? AND Product_id = ?
  ");
  $stmt->bind_param('ii',$idCart,$productId);
  $stmt->execute();
  $stmt->close();

  // insert ke cartitem 
  $stmt = $db->prepare("
  INSERT INTO cartitem (Cart_id,Product_id,quantity) 
  VALUES (?,?,'1')
  ");
  $stmt->bind_param('ii',$idCart,$productId);
  $stmt->execute();
  $stmt->close();

  echo json_encode(['cart'=>$getOutputData()]);

}

// Delete cart item
if ($sendMethod==="DELETE") 
{
  // Hapus dengan link tabel 
  $qry = "
  DELETE FROM cartItem 
  WHERE Product_id = ? AND Cart_id = (
    SELECT _id FROM cart WHERE clerkId = ?
  )
  ";
  $stmt = $db->prepare($qry);
  $stmt->bind_param('is',$productId,$clerkUserId);
  $stmt->execute();
  $stmt->close();

  echo json_encode(['cart'=>$getOutputData()]);
}

if ($sendMethod==="DELETE") 
{
  $qry = "
  UPDATE cartitem SET quantity = ? 
  WHERE Product_id = ? AND Cart_id = (
    SELECT _id FROM cart WHERE clerkId = ?
  )
  ";
  $stmt = $db->prepare($qry);
  $stmt->bind_param('is',$productId,$clerkUserId);
  $stmt->execute();
  $stmt->close();
}


?>