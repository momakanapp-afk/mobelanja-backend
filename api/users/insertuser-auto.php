<?php 
/**
 * INSERT USER KE BACKEND DARI CLERK DATA OTOMATIS
 * Diaktifkan di event save new Address dan update profil user
 * Tidak untuk dipanggil secara langsung, membutuhkan variabel $db, $userToken
 */


  // Cari apakah clerkId telah ditambahkan di tabel user 
  $hs = $db->execute_query("
  SELECT _id FROM user WHERE clerkId = ? ",[$clerkUserId]);

  if ($hs->num_rows > 0) {
    return false;
  }

  $lastname = (isset($userToken->lastname)) ? $userToken->lastname : '';

  // Add user 
  $qry = "
  INSERT INTO user (clerkId,email,name,imageUrl) 
  VALUES (?,?,?,?)
  ";
  $ival = [
    $clerkUserId,
    $userToken->email,
    $userToken->firstname.' '.$lastname,
    $userToken->image
  ];
  $db->execute_query($qry,$ival);


?>