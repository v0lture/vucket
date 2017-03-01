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

  // check if vars are both submitted
  if(isset($_POST["username"]) && isset($_POST["password"])) {
    $u = $_POST["username"];
    $p = $_POST["password"];

    $resp = $auth->login($u, $p);
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
    $error = null;
    $result = Array("token" => $resp["token"], "username" => $u);
  }

  // process into json
  $json = Array(
    "status" => $state,
    "error" => $error,
    "result" => $result
  );

  echo json_encode($json);

 ?>
