<?php

  // set output type
  header("Content-type: application/json");

  // grab secrets
  require_once "../library/auth.php";
  require_once "../library/telemetry.php";
  require_once "../library/user.php";
  require_once "../library/vat.php";
  require_once "../secrets.php";
  global $db;

  // vAT subscription status
  $telemetry = new Telemetry($db);
  $auth = new Auth($db, $telemetry);
  $user = new User($auth, $db, $telemetry);
  $vat = new vAT($auth, $db, $telemetry, $user);

  if(isset($_POST["token"])) {
    $t = $_POST["token"];
    $resp = $vat->subscriptions($t);
  } else {
    $error = "missing_vars";
  }

  // conditionals with error status
  if(isset($resp["error"])){
    $state = "error";
    $error = $resp["error"];
  } elseif(isset($error)) {
    $state = "error";
    $error = $error;
  } else {
    $state = "success";
    $result = $resp["data"];
    $error = null;
  }

  // process into json
  $json = Array(
    "status" => $state,
    "error" => $error,
    "result" => $result
  );

  echo json_encode($json);

 ?>
