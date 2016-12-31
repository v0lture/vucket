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


      //
      // AUTHENTICATON
      //

      // Register
      $reg = $user->register("username", "password", "email@email.email");
      if(isset($reg["error"])) {
        trigger_error("Register failed (logged: ".$reg["logged"]."): ".$reg["error"], E_USER_WARNING);
      } else {
        echo "\r\nRegistered correctly! (logged: ".$reg["logged"].")\r\n";
      }

      // Login
      $login = $auth->login("username", "password");
      if(isset($login["error"])) {
        trigger_error("Login (logged: ".$login["logged"]."): ".$login["error"], E_USER_WARNING);
      } else {
        $token = $login["token"];
        echo "Logged in! (logged: ".$login["logged"].")\r\n";
      }

      // Validate token
      $tok = $auth->validateToken($token);
      if(isset($tok["error"])) {
        trigger_error("Token Validation (logged: ".$tok["logged"]."): ".$tok["error"], E_USER_WARNING);
      } else {
        echo "Token valid! (logged: ".$tok["logged"].")\r\n";
      }

      //
      // USER
      //

      // Get user data with token
      $usertokendata = $user->getAuthUser($token);
      if(isset($usertokendata["error"])) {
        trigger_error("Get user data with token (logged: ".$usertokendata["logged"]."): ".$usertokendata["error"], E_USER_WARNING);
      } else {
        echo "Fetched user data with token (".$token.") which was logged as [".$usertokendata["logged"]."] and responded with:\r\n";
        var_dump($usertokendata["data"]);
      }

      // Is User
      $isuser = $user->isUser("username");
      if(isset($isuser["error"])) {
        trigger_error("Is User (logged: ".$isuser["logged"]."): ".$isuser["error"], E_USER_WARNING);
      } else {
        echo "Is user? (Logged: ".$isuser["logged"].") \r\n";
        var_dump($isuser["isuser"]);
      }

      // Finalize dump of MySQL logs
      var_dump($db->error_list);
    }

  }

 ?>
