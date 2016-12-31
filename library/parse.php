<?php

  if(!isset($TEST)) {
    require_once "../secrets.php";
  }

  // Misc parse tools
  function filter($s) {
    global $db;
    $s = trim($s);
    $s = mysqli_real_escape_string($db, $s);
    $s = htmlspecialchars_decode($s);
    $s = strip_tags($s);
    return $s;
  }

?>
