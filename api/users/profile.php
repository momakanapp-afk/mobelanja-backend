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

// Always Call GET 
$qry = "
SELECT * FROM user WHERE clerkId = ?";
$hs = $db->execute_query($qry,[$clerkUserId]);
$userdata = [];
if ($hs->num_rows > 0) {
  while ($row = $hs->fetch_assoc()) {
    $userdata = [
      'email'=>$row['email'],
      'name'=> $row['name'],
      'facebook'=> $row['facebook'],
      'kontak'=> $row['kontak'],
      'kotakab'=> $row['kotakab'],
      'imageUrl'=>$row['imageUrl']
    ];
  }
}

if ($sendMethod==="GET")
{
  echo json_encode(['userdata'=>$userdata]);
}

if ($sendMethod==="POST" && $_POST['act']==='saveImageUrl') 
{
  # CEK + INSERT NEW USER Otomatis 
  include __DIR__.'/insertuser-auto.php';

  $newUrl = $_POST['urlimg'];

  # Update image url 
  $qry = "
  UPDATE user SET imageUrl = ? 
  WHERE clerkId = ?
  ";
  $db->execute_query($qry,[$newUrl,$clerkUserId]);
  $success = false;
  if ($db->affected_rows > 0) {
    $success = true;
  }

  echo json_encode(['isSuccess'=>$success,'imgUrl'=>$newUrl]);
  exit();
}
if ($sendMethod==="POST" && $_POST['act']==='saveForm') 
{
  # CEK + INSERT NEW USER Otomatis 
  include __DIR__.'/insertuser-auto.php';

  $formData = $_POST['data'];
  $postcol = array_keys($formData);

  // Susun SET 
  $setArr = [];
  foreach ($postcol as $item) {
    $setArr[] = "$item = ?";
  }
  $setString = implode(",",$setArr);
  // Ekstrak value
  $setValues = array_values($formData);
  $setValues[] = $clerkUserId;

  # Update user
  $qry = "
  UPDATE user SET $setString WHERE clerkId = ?
  ";
  $db->execute_query($qry,$setValues);
  $success = false;
  if ($db->affected_rows > 0) {
    $success = true;
  }

  echo json_encode(['isSuccess'=>$success]);
  exit();
}




?>