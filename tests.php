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
      // VARIABLES
      //
      $username = preg_replace('/[0-9]+/', '', substr(md5(rand()), 0, 15))."-test";
      $password = substr(md5(rand()), 0, 7);
      echo "\r\n\r\nUsing '".$username."' @ '".$password."' for authentication.\r\n\r\n";


      //
      // AUTHENTICATON
      //

      // Register
      $reg = $user->register($username, $password, "email@email.email");
      if(isset($reg["error"])) {
        trigger_error("Register failed (logged: ".$reg["logged"]."): ".$reg["error"], E_USER_WARNING);
      } else {
        echo "\r\nRegistered correctly! (logged: ".$reg["logged"].")\r\n";
      }

      // Login
      $login = $auth->login($username, $password);
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

      // Login again
      $log2 = $auth->login($username, $password);
      if(isset($log2["error"])) {
        trigger_error("Login [again] (logged: ".$log2["logged"]."): ".$log2["error"], E_USER_WARNING);
      } else {
        $t = $log2["token"];
        $eT = $auth->expireToken($t, $token);
        if(isset($eT["error"])) {
          trigger_error("Failed to expire token (logged: ".$eT["logged"]."): ".$eT["error"], E_USER_WARNING);
        } else {
          echo "Token expired successfully.\r\n";
        }
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
      $isuser = $user->isUser($usertokendata["data"]["username"]);
      if(isset($isuser["error"])) {
        trigger_error("Is User (logged: ".$isuser["logged"]."): ".$isuser["error"], E_USER_WARNING);
      } else {
        echo "Is user? (Logged: ".$isuser["logged"].") \r\n";
        var_dump($isuser["isuser"]);
      }

      // Read User property with USERNAME + ID
      $readpropU = $user->readUser($usertokendata["data"]["username"], "username");
      $readpropI = $user->readUser($usertokendata["data"]["user_id"], "username");

      if(isset($readpropU["error"])) {
        // an error occurred
        trigger_error("Read user property (via USERNAME) (logged: ".$readpropU["logged"]."): ".$readpropU["error"], E_USER_WARNING);
      }

      if(isset($readpropI["error"])) {
        // an error occurred
        trigger_error("Read user property (via ID) (logged: ".$readpropI["logged"]."): ".$readpropI["error"], E_USER_WARNING);
      }

      if(!isset($readpropU["error"]) && !isset($readpropI["error"])) {
        // proceed
        if($readpropU["data"]["username"] == $readpropI["data"]["username"]) {
          echo "Read user property 'username' and was matched via 2 differing identifiers.\r\n";
        } else {
          trigger_error("Read user property 'username' did not match via 2 differing identifers.\r\nUSERNAME: ".$readpropU["data"]["username"]."\r\nID: ".$readpropI["data"]["username"], E_USER_WARNING);
        }
      }

      // Modify user email
      $modifyprop = $user->modifyUser($usertokendata["data"]["username"], "email", "newemail@email.com");
      if(isset($modifyprop["error"])) {
        trigger_error("Modify user property (via USERNAME) (logged: ".$modifyprop["logged"]."): ".$modifyprop["error"], E_USER_WARNING);
      } else {
        echo "Modify user property (via USERNAME) (logged: ".$modifyprop["logged"]."): ".$modifyprop["data"]["old"]." => ".$modifyprop["data"]["new"]."\r\n";
      }


      // Finalize dump of MySQL logs
      var_dump($db->error_list);
    }

  }

 ?>
