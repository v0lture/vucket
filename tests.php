<?php

  $TEST = true;

  // load files
  require_once "library/parse.php";
  require_once "library/auth.php";
  require_once "library/telemetry.php";
  require_once "library/user.php";
  require_once "library/vat.php";
  require_once "library/cycles.php";

  class tests extends PHPUnit_Framework_TestCase {

    public function test() {

      // create db connection
      $db = new mysqli("localhost", "travis", "", "vucket");
      $telemetry = new Telemetry($db);
      $auth = new Auth($db, $telemetry);
      $user = new User($auth, $db, $telemetry);
      $vat = new vAT($auth, $db, $telemetry, $user);
      $cycles = new BillingCycle($auth, $db, $telemetry, $user);

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
      $readpropU = $user->readUser($usertokendata["data"]["username"], "email", $token);

      if(isset($readpropU["error"])) {
        // an error occurred
        trigger_error("Read user property (via USERNAME) (logged: ".$readpropU["logged"]."): ".$readpropU["error"], E_USER_WARNING);
      } else {
        echo "Read user property 'email' and got ".$readpropU["data"]["email"]."\r\n";
      }

      // Modify user email
      $modifyprop = $user->modifyUser($usertokendata["data"]["username"], "email", "newemail@email.com", $token);
      if(isset($modifyprop["error"])) {
        trigger_error("Modify user property (via USERNAME) (logged: ".$modifyprop["logged"]."): ".$modifyprop["error"], E_USER_WARNING);
      } else {
        echo "Modify user property (via USERNAME) (logged: ".$modifyprop["logged"]."): ".$modifyprop["data"]["old"]." => ".$modifyprop["data"]["new"]."\r\n";
      }

      // 
      // vAT
      //


      // subscribe
      $vat_sub = $vat->subscribe($token, "ex app", 1);
      if(isset($vat_sub["error"])){

        // :(
        trigger_error("Subscribe to vAT (via TOKEN) (logged: ".$vat_sub["logged"]."): ".$vat_sub["error"], E_USER_WARNING);
      } else {
        // :)
        echo "vAT subscription succcessful (via TOKEN) (logged: ".$vat_sub["logged"].")\r\n";
      }

      // get all
      $vat_subs = $vat->subscriptions($token);
      if(isset($vat_subs["error"])){

        // :(
        trigger_error("Get vAT subscriptions (via TOKEN) (logged: ".$vat_subs["logged"]."): ".$vat_subs["error"], E_USER_WARNING);
      } else {
        // :)
        echo "vAT subscription fetch succcessful (via TOKEN) (logged: ".$vat_subs["logged"]."). Echoing...\r\n";
        var_dump($vat_subs);
      }

      //
      // BILLING
      //


      // assign
      $cycles_assign = $cycles->assign($token, 30);
      if(isset($cycles_assign["error"])) {
        // errored
        trigger_error("Assign billing date (via TOKEN) (logged: ".$cycles_assign["logged"]."): ".$cycles_assign["error"], E_USER_WARNING);
      } else {
        echo "Billing date assigned (via TOKEN) (logged: ".$cycles_assign["logged"].")";
      }

      // get remaining
      // (utilizes both fetch functions for double test)
      $cycles_fetch = $cycles->assign($token, 30);
      if(isset($cycles_fetch["error"])) {
        // errored
        trigger_error("Fetched billing dates and remaining (via TOKEN) (logged: ".$cycles_fetch["logged"]."): ".$cycles_fetch["error"], E_USER_WARNING);
      } else {
        echo "Billing dates and remaining fetch (via TOKEN) (logged: ".$cycles_fetch["logged"]."). Echoing...\r\n";
        var_dump($cycles_fetch);
      }


      // Finalize dump of MySQL logs
      var_dump($db->error_list);
    }

  }

 ?>
