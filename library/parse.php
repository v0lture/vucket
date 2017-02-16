<?php

  if(!isset($TEST)) {
    require_once "../secrets.php";
  } else {
    $db = new mysqli("localhost", "travis", "", "vucket");
  }

  // Misc parse tools
  function filter($s) {
    // Travis CI
    if(!isset($TEST)) {
      global $db;
    }
    $s = trim($s);
    $s = mysqli_real_escape_string($db, $s);
    $s = htmlspecialchars_decode($s);
    $s = strip_tags($s);
    return $s;
  }

  // error reporting
  error_reporting(E_ALL);

?>
