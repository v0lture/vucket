<?php

  // set output type
  header("Content-type: application/json");

  // grab secrets
  require_once "../library/telemetry.php";
  require_once "../library/health.php";
  require_once "../secrets.php";
  global $db;

  // vAT subscription status
  $telemetry = new Telemetry($db);
  $health = new Health($db, $telemetry);

  $resp = $health->fetch();

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
