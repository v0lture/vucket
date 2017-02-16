<?php

  // set output type
  header("Content-type: application/json");

  // grab secrets
  require_once "../library/auth.php";
  require_once "../library/telemetry.php";
  require_once "../library/user.php";
  require_once "../secrets.php";
  global $db;
  
  // clear empty vars
  $result = NULL;
  $error = NULL;

  // class inits
  $telemetry = new Telemetry($db);
  $auth = new Auth($db, $telemetry);
  $user = new User($auth, $db, $telemetry);

  // get mode
  if(isset($_POST["mode"])) {
    $m = $_POST["mode"];

    // get user based on token
    if($m == "getUserAuth") {
      // run
      if(isset($_POST["token"])) {
        $t = $_POST["token"];
        $resp = $user->getAuthUser($t);
      } else {
        $error = "missing_vars";
      }

    // is user
    } elseif($m == "isUser") {
      if(isset($_POST["username"])) {
        $u = $_POST["username"];
        $resp2 = $user->isUser($u);
        if(isset($resp2["error"])) {
          $resp = $resp2;
        } else {
          $resp["data"] = $resp2["isuser"];
        }
      } else {
        $error = "missing_vars";
      }

    // register user
    } elseif($m == "register") {
      if(isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["email"])) {
        $u = $_POST["username"];
        $p = $_POST["password"];
        $e = $_POST["email"];
        $resp2 = $user->register($u, $p, $e);
        if(isset($resp2["error"])) {
          $resp = $resp2;
        } else {
          $resp["data"] = $resp2["created"];
        }
      }
    }
  } else {
    $error = "missing_mode";
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
