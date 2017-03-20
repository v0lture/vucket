<?php

  // set output type
  header("Content-type: application/json");

  // grab secrets
  require_once "../library/auth.php";
  require_once "../library/telemetry.php";
  require_once "../library/user.php";
  require_once "../library/cycles.php";
  require_once "../secrets.php";
  global $db;

  // vAT subscription status
  $telemetry = new Telemetry($db);
  $auth = new Auth($db, $telemetry);
  $user = new User($auth, $db, $telemetry);
  $cycle = new BillingCycle($auth, $db, $telemetry, $user);

  if(isset($_POST["token"]) && isset($_POST["mode"])) {
    $t = $_POST["token"];
    $m = $_POST["mode"];

    if($m == "fetch") {
      $resp = $cycle->fetch($t);
    } elseif($m == "remaining") {
      $resp = $cycle->remaining($t);
    } elseif($m == "assign") {
      if(isset($_POST["day"])) {
        $d = $_POST["day"];
        $resp = $cycle->assign($t, $d);
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
