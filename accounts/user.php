<?php

  // set output type
  header("Content-type: application/json");

  // grab secrets
  require_once "../library/auth.php";
  require_once "../library/telemetry.php";
  require_once "../library/user.php";
  require_once "../secrets.php";
  global $db;

  // class inits
  $telemetry = new Telemetry($db);
  $auth = new Auth($db, $telemetry);
  $user = new User($auth, $db, $telemetry);

  // run
  if(isset($_POST["token"])) {
    $t = $_POST["token"];
    $resp = $user->getAuthUser($t);
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
