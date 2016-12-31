<?php

  $TEST = true;

  // load files
  require_once "library/parse.php";
  require_once "library/auth.php";
  require_once "library/telemetry.php";
  require_once "library/user.php";

  class tests extends PHPUnit_Framework_TestCase {

    public function test() {

      // create db connection
      $db = new mysqli("localhost", "travis", "", "vucket");
      $telemetry = new Telemetry($db);
      $auth = new Auth($db, $telemetry);
      $user = new User($auth, $db, $telemetry);

      // Register
      $reg = $user->register("username", "password", "email@email.email");
      if(isset($reg["error"])) {
        var_dump($db->error_list);
        trigger_error("Register failed (logged: ".$reg["logged"]."): ".$reg["error"], E_USER_WARNING);
      } else {
        echo "Registered correctly!\r\n";
      }

      // Login
      $login = $auth->login("username", "password");
      if(isset($login["error"])) {
        trigger_error("Login (logged: ".$login["logged"]."): ".$login["error"], E_USER_WARNING);
      } else {
        $token = $login["token"];
        echo "Logged in!\r\n";
      }

      // Validate token
      $tok = $auth->validateToken($token);
      if(isset($tok["error"])) {
        trigger_error("Token Validation (logged: ".$tok["logged"]."): ".$tok["error"], E_USER_WARNING);
      } else {
        echo "Token valid!\r\n";
      }
    }

  }

 ?>
