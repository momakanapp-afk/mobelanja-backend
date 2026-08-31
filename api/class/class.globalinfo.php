<?php
/**
 * Global Variable Info class
 * @ 2021-09-27
 */

class globalInfo
{
  public $APPID;
  private $vi_conn;

  function __construct()
  {
    $fappid = __DIR__."/appid";
    $appID = file_get_contents($fappid);
    $this->APPID = trim($appID);
  }

  public function getMysqli() {

    // Biar nggak error debug
    $serverhost=$serveruser=$serverpww=$dbname="";
    
    // Optimisasi pengambilan koneksi menggunakan 1 query sekali jalan
    // tidak menggunakan $this->getVar() pd masing2 variabel
    $db = $this->getviconn(1);
    $db->busyTimeout(5000);

    $qry = "SELECT * FROM varinfo WHERE varname IN('serverhost','serveruser','serverpww','dbname')";
    $pqr = $db->query($qry);
    while ($R = $pqr->fetchArray(SQLITE3_ASSOC))
    {
      $nmvar = $R['varname'];
      $$nmvar = openssl_decrypt($R['val'],'aes-128-cfb',$this->APPID,0,$R['iv']);
    }
    $db->close();

    // Matikan warning error connection 
    error_reporting(E_ERROR);
    $cnn = new mysqli($serverhost, $serveruser, $serverpww, $dbname);
    if ($cnn->connect_errno) {
      exit("Koneksi Database Error");
    } else {
      $cnn->set_charset("utf8mb4");
      return $cnn;
    }
  }

  private function getviconn($mode)
  {
    if ($mode==1) {
      return new SQLite3(__DIR__.'/varinfo.db',SQLITE3_OPEN_READONLY);
    }
    else if ($mode==2) {
      return new SQLite3(__DIR__.'/varinfo.db');
    }
  }

  private function buatIV()
  {
    # Buat initialization vector
    $ivlen = openssl_cipher_iv_length('aes-128-cfb');
    return openssl_random_pseudo_bytes($ivlen);
  }

  public function addVar($nmvar,$val,$scuser=null,$mode="ADD")
  {
    $db = $this->getviconn(2);
    $db->busyTimeout(5000);

    $iv = $this->buatIV();

    if (empty($val)) {
      $e_val = "";
    } else {
      // Enkripsi value
      $e_val = openssl_encrypt($val,'aes-128-cfb',$this->APPID,0,$iv);
    }

    // Global Scoupe
    if (!isset($scuser)) {
      $scuser = "_GLOBAL_";
    }

    if ($mode==="ADD") {
      $pqr = $db->prepare(
        "INSERT INTO varinfo(varname,val,iv,dibuat,scuser) VALUES(?,?,?,?,?)"
      );
      $pqr->bindValue(1,$nmvar,SQLITE3_TEXT);
      $pqr->bindValue(2,$e_val,SQLITE3_BLOB);
      $pqr->bindValue(3,$iv,SQLITE3_BLOB);
      $pqr->bindValue(4,time(),SQLITE3_TEXT);
      $pqr->bindValue(5,$scuser,SQLITE3_TEXT);
    }
    else if ($mode==="UPDATE") {
      $pqr = $db->prepare(
        "UPDATE varinfo SET val=?,iv=?,dibuat=? WHERE scuser=? AND varname=?"
      );
      $pqr->bindValue(1,$e_val,SQLITE3_BLOB);
      $pqr->bindValue(2,$iv,SQLITE3_BLOB);
      $pqr->bindValue(3,time(),SQLITE3_TEXT);
      $pqr->bindValue(4,$scuser,SQLITE3_TEXT);
      $pqr->bindValue(5,$nmvar,SQLITE3_TEXT);
    }

    if (!$pqr->execute()) {
      $reo = "ERROR_VAR_EXIST";
    } else {
      $reo = "_SUCCESS_";
    }
    $db->close();
    return $reo;
  }

  public function getVar($nmvar,$scuser=null)
  {
    $db = $this->getviconn(1);
    $db->busyTimeout(5000);


    if (!isset($scuser)) {
      $scuser = "_GLOBAL_";
    }

    // Use Regular Query (no prepared query) untuk mengurangi beban concurrency
    $qry = "SELECT * FROM varinfo WHERE varname='%s' AND scuser='%s' LIMIT 1";
    $pqr = $db->query(sprintf($qry,$nmvar,$scuser));
    $R = $pqr->fetchArray(SQLITE3_ASSOC);

    # Var not found
    if (!$R) {
      $db->close();
      return "VAR_NOT_FOUND";
    }

    # Empty handler (JANGAN DIUBAH ! empty val dipakai di root password)
    if (empty($R['val'])) {
      $ou_d = "";
    }
    else {
      # Decrypt value
      $ou_d = openssl_decrypt($R['val'],'aes-128-cfb',$this->APPID,0,$R['iv']);
    }

    $db->close();

    return $ou_d;
  }

  public function delVar($nmvar,$scuser=null) {

    # Credential DB tidak dapat dihapus
    $sysvar = array("serverhost","serveruser","serverpww","dbname");
    if (in_array($nmvar,$sysvar)) {
      return "ERROR_SYSVAR";
    }

    $db = $this->getviconn(2);

    // Anti DB Lock Problem
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode = wal;');

    if (!isset($scuser)) {
      $scuser = "_GLOBAL_";
    }
    $pqr = $db->prepare(
      "DELETE FROM varinfo WHERE varname=? AND scuser=? ");

    $pqr->bindValue(1,$nmvar,SQLITE3_TEXT);
    $pqr->bindValue(2,$scuser,SQLITE3_TEXT);

    if (!$pqr->execute()) {
      $reo = "ERROR_DEL";
    } else {
      $reo = "_SUCCESS_";
    }
    $db->close();
    return $reo;
  }



  /**  
   * MULTI DATABASE SUPPORTING 2022-07-18 
   * Penambahan koneksi mengacu pada modul EXTRA > DB Connection
   */
  public function getMysqli_ID($iddb) 
  {
    $db = $this->getviconn(1);
    $db->busyTimeout(5000);

    $qry = "SELECT * FROM varinfo WHERE varname IN(
      'serverhost_$iddb','serveruser_$iddb','serverpww_$iddb','dbname_$iddb')";
    $pqr = $db->query($qry); $mycred=[];
    while ($R = $pqr->fetchArray(SQLITE3_ASSOC))
    {
      $mycred[$R['varname']] = openssl_decrypt($R['val'],'aes-128-cfb',$this->APPID,0,$R['iv']);
    }
    $db->close();

    // Matikan warning error connection 
    error_reporting(E_ERROR); $pref = '_'.$iddb;
    $cnn = new mysqli(
      $mycred['serverhost_'.$iddb], 
      $mycred['serveruser_'.$iddb], 
      $mycred['serverpww_'.$iddb], 
      $mycred['dbname_'.$iddb]
    );
    if ($cnn->connect_errno) {
      exit("Koneksi Database Error");
    } else {
      return $cnn;
    }
  }
}

?>
