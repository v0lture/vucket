<?php

  // set output type
  header("Content-type: application/json");

  // grab secrets
  require_once "../library/auth.php";
  require_once "../library/telemetry.php";
  require_once "../secrets.php";
  global $db;

  // we handle logging in or continuing to 2FA
  $telemetry = new Telemetry($db);
  $auth = new Auth($db, $telemetry);

  if(isset($_POST["token"]) && isset($_POST["mode"])) {
    $t = $_POST["token"];
    $m = $_POST["mode"];

    if($m == "validate") {
      $resp = $auth->validateToken($t);
    } elseif($m == "expire") {
      if(isset($_POST["authtoken"])) {
        $at = $_POST["authtoken"];
        $resp = $auth->expireToken($t, $at);
      } else {
        $error = "missing_vars";
      }
    }
  } else {
    $error = "missing_vars";
  }

  // conditionals with error status
  if(isset($resp["error"])){
    $state = "error";
    $error = $resp["error"];
    $result = null;
  } elseif(isset($error)) {
    $state = "error";
    $error = $error;
    $result = null;
  } else {
    $state = "success";
    if(isset($resp["data"])){
      $result = $resp["data"];
    } else {
      $result = "valid";
    }
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
