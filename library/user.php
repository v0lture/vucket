<?php

  require_once "parse.php";

  class User {

    private $auth;
    private $dbc;
    private $telemetry;

    // get needed classes
    public function __construct($auth, $db, $telemetry) {
      $this->auth = $auth;
      $this->dbc = $db;
      $this->telemetry = $telemetry;
    }

    // get authenticated user
    public function getAuthUser($token) {

      // clean vars
      $token = filter($token);

      // check token status
      $ts = $this->auth->validateToken($token);
      if(isset($ts["token"])) {
        // valid token

        // check if tokentag submitted
        if(isset($ts["tokentag"])) {
          // proceed with data search

          if($rd = $this->dbc->query("SELECT * FROM `accounts` WHERE tokentag = '".$ts["tokentag"]."'")){

            if($rd->num_rows > 1) {
              // too many accounts, throw an error
              $t = $this->telemetry->error("overlapping_tokentags", "user.php > getAuthUser() > tokentag query");
              if($t == "success") {
                return Array("logged" => "yes", "error" => "overlapping_tokentags");
              } else {
                return Array("logged" => "no: ".$t, "error" => "overlapping_tokentags");
              }
            } elseif($rd->num_rows == 1) {
              // log'n'proceed
              $t = $this->telemetry->user("tokentag_association", $ts["tokentag"]);
              if($t == "success") {

                // parse data
                while($d = $rd->fetch_assoc()) {
                  return Array("logged" => "yes", "data" => Array("user_id" => $d["id"], "username" => $d["username"], "email" => $d["email"]));
                }

              } else {
                // fail due to insecure validation
                $tt = $this->telemetry->error("insecure_tokentag_validation", "user.php > getAuthUser() > user assocation");
                if($t == "success") {
                  return Array ("logged" => "yes", "error" => "insecure_tokentag_validation:".$tt);
                } else {
                  return Array ("logged" => "no: ".$tt, "error" => "insecure_tokentag_validation:".$tt);
                }
              }
            }

          } else {
            // query error
            if($t = $this->telemetry->error("user_tokentag_query_failed", "user.php > getAuthUser() > tokentag query", $this->dbc->error) == "success") {
              return Array("logged" => "yes", "error" => "user_tokentag_query_failed");
            } else {
              return Array("logged" => "no: ".$t, "error" => "user_tokentag_query_failed");
            }
          }
        } else {
          // return insecure Event
          $t = $this->telemetry->error("insecure_token_validation", $token);
          if($t == "success") {
            return Array ("logged" => "yes", "error" => "associate_invalid_token:".$ts['error']);
          } else {
            return Array ("logged" => "no: ".$t, "error" => "associate_invalid_token:".$ts['error']);
          }
        }
      } else {
        // invalid token
        $t = $this->telemetry->user("associate_invalid_token", $token);
        if($t == "success") {
          return Array ("logged" => "yes", "error" => "associate_invalid_token:".$ts['error']);
        } else {
          return Array ("logged" => "no: ".$t, "error" => "associate_invalid_token:".$ts['error']);
        }
      }
    }

    // is user
    public function isUser($user) {
      $user = filter($user);


      // query db
      if($rd = $this->dbc->query("SELECT * FROM `accounts` WHERE username = '".$user."'")) {

        // count results
        if($rd->num_rows == 1) {
          $t = $this->telemetry->user("isuser", $user);
          if($t == "success") {
            return Array("logged" => "yes", "isuser" => true);
          } else {
            return Array("logged" => "no: ".$t, "isuser" => true);
          }
        } elseif($rd->num_rows > 1) {
          // duplicate accounts
          $t = $this->telemetry->error("account_dupe", "user.php > isUser()", "Queried '".$user."'");
          if($t == "success") {
            return Array("logged" => "yes", "error" => "account_dupe");
          } else {
            return Array("logged" => "no: ".$t, "error" => "account_dupe");
          }
        } else {
          // :( we couldn't find anything boss
          $t = $this->telemetry->user("isuser_failed", $user);
          if($t == "success") {
            return Array("logged" => "yes", "isuser" => false);
          } else {
            return Array("logged" => "no: ".$t, "isuser" => false);
          }
        }

      } else {
        // log error and respond back
        $t = $this->telemetry->error("isuser_query", "user.php > isUser() > query", $this->dbc->query);
        if($t == "success") {
          return Array("logged" => "yes", "error" => "isuser_query");
        } else {
          return Array("logged" => "no: ".$t, "error" => "isuser_query");
        }
      }
    }

    // create user
    public function register($user, $pass, $email) {

      // clean vars
      $user = filter($user);
      $pass = filter(password_hash($pass, PASSWORD_DEFAULT));
      $email = filter($email);

      // check if isUser
      $iU = $this->isUser($user);
      if($iU["isuser"] == true) {
        // respond username taken
        $t = $this->telemetry->error("register_usertaken", "user.php > register() > isUser()");
        if($t == "success") {
          return Array("logged" => "yes", "error" => "registerusertaken");
        } else {
          return Array("logged" => "no: ".$t, "error" => "registerusertaken");
        }
      } elseif(isset($iU["error"])) {
        // an error occurred
        $t = $this->telemetry->error("register_isuser:".$iU["error"], "user.php > register() > isUser()");
        if($t == "success") {
          return Array("logged" => "yes", "error" => "register_isuser:".$iU["error"]);
        } else {
          return Array("logged" => "no: ".$t, "error" => "register_isuser:".$iU["error"]);
        }
      } else {
        // proceed with register

        // create tags
        $logtag = filter($this->createTag());
        $tokentag = filter($this->createTag());

        // insert
        if($this->dbc->query("INSERT INTO `accounts` (`id`, `username`, `email`, `password`, `frozen`, `2fa`, `secret`, `logtag`, `tokentag`) VALUES (NULL, '".$user."', '".$email."', '".$pass."', 0, 0, '', '".$logtag."', '".$tokentag."')")) {
          // creation successful
          $t = $this->telemetry->user("create", $user);
          if($t == "success") {
            return Array("logged" => "yes", "created" => true);
          } else {
            return Array("logged" => "no: ".$t, "created" => true);
          }
        } else {
          // error
          $t = $this->telemetry->error("register_failed", "user.php > register() > isUser() > create query", $this->dbc->error);
          if($t == "success") {
            return Array("logged" => "yes", "error" => "register_failed");
          } else {
            return Array("logged" => "no: ".$t, "error" => "register_failed");
          }
        }
      }

    }

    // tag creation
    private function createTag() {
      $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
      $str = '';
      $max = mb_strlen($keyspace, '8bit') - 1;
      for ($i = 0; $i < 50; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
      }
      return $str;
    }

  }


 ?>
