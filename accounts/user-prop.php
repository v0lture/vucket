<?php

  // set output type
  header("Content-type: application/json");

  // grab secrets
  require_once "../library/auth.php";
  require_once "../library/telemetry.php";
  require_once "../library/user.php";
  require_once "../secrets.php";
  global $db;

  // we handle logging in or continuing to 2FA
  $telemetry = new Telemetry($db);
  $auth = new Auth($db, $telemetry);
  $user = new User($auth, $db, $telemetry);

  if(isset($_POST["property"]) && isset($_POST["identifier"]) && isset($_POST["mode"]) && isset($_POST["token"])) {
    $p = $_POST["property"];
    $i = $_POST["identifier"];
    $m = $_POST["mode"];
    $t = $_POST["token"];
    if($m == "r") {
      $resp = $user->readUser($i, $p, $t);
    } else if($m == "w") {
      if(isset($_POST["value"])) {
        $v = $_POST["value"];
        $resp = $user->modifyUser($i, $p, $v, $t);
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
  } elseif(isset($error)) {
    $state = "error";
    $error = $error;
  } else {
    $state = "success";
    $result = $resp["data"];
  }

  // process into json
  $json = Array(
    "status" => $state,
    "error" => $error,
    "result" => $result
  );

  echo json_encode($json);

 ?>
