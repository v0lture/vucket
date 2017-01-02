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
              $this->telemetry->functionLog("degraded", "getAuthUser", $t["id"]);
              if($t["d"] == "success") {
                return Array("logged" => "yes", "error" => "overlapping_tokentags");
              } else {
                return Array("logged" => "no: ".$t["d"], "error" => "overlapping_tokentags");
              }
            } elseif($rd->num_rows == 1) {
              // log'n'proceed
              $t = $this->telemetry->user("tokentag_association", $ts["tokentag"]);
              $this->telemetry->functionLog("success", "getAuthUser", $t["id"]);
              if($t["d"] == "success") {

                // parse data
                while($d = $rd->fetch_assoc()) {
                  return Array("logged" => "yes", "data" => Array("user_id" => $d["id"], "username" => $d["username"], "email" => $d["email"]));
                }

              } else {
                // fail due to insecure validation
                $tt = $this->telemetry->error("insecure_tokentag_validation", "user.php > getAuthUser() > user assocation");
                $this->telemetry->functionLog("degraded", "getAuthUser", $tt["id"]);
                if($t["d"] == "success") {
                  return Array ("logged" => "yes", "error" => "insecure_tokentag_validation:".$tt);
                } else {
                  return Array ("logged" => "no: ".$tt, "error" => "insecure_tokentag_validation:".$tt);
                }
              }
            }

          } else {
            // query error
            if($t["d"] = $this->telemetry->error("user_tokentag_query_failed", "user.php > getAuthUser() > tokentag query", $this->dbc->error) == "success") {
              $this->telemetry->functionLog("success", "getAuthUser", $t["id"]);
              return Array("logged" => "yes", "error" => "user_tokentag_query_failed");
            } else {
              return Array("logged" => "no: ".$t["d"], "error" => "user_tokentag_query_failed");
            }
          }
        } else {
          // return insecure Event
          $t = $this->telemetry->error("insecure_token_validation", $token);
          $this->telemetry->functionLog("degraded", "getAuthUser", $t["id"]);
          if($t["d"] == "success") {
            return Array ("logged" => "yes", "error" => "associate_invalid_token:".$ts['error']);
          } else {
            return Array ("logged" => "no: ".$t["d"], "error" => "associate_invalid_token:".$ts['error']);
          }
        }
      } else {
        // invalid token
        $t = $this->telemetry->user("associate_invalid_token", $token);
        $this->telemetry->functionLog("success", "getAuthUser", $t["id"]);
        if($t["d"] == "success") {
          return Array ("logged" => "yes", "error" => "associate_invalid_token:".$ts['error']);
        } else {
          return Array ("logged" => "no: ".$t["d"], "error" => "associate_invalid_token:".$ts['error']);
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
          $this->telemetry->functionLog("success", "isUser", $t["id"]);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "isuser" => true);
          } else {
            return Array("logged" => "no: ".$t["d"], "isuser" => true);
          }
        } elseif($rd->num_rows > 1) {
          // duplicate accounts
          $t = $this->telemetry->error("account_dupe", "user.php > isUser()", "Queried '".$user."'");
          $this->telemetry->functionLog("degraded", "isUser", $t["id"]);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "error" => "account_dupe");
          } else {
            return Array("logged" => "no: ".$t["d"], "error" => "account_dupe");
          }
        } else {
          // :( we couldn't find anything boss
          $t = $this->telemetry->user("isuser_failed", $user);
          $this->telemetry->functionLog("success", "isUser", $t["id"]);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "isuser" => false);
          } else {
            return Array("logged" => "no: ".$t["d"], "isuser" => false);
          }
        }

      } else {
        // log error and respond back
        $t = $this->telemetry->error("isuser_query", "user.php > isUser() > query", $this->dbc->query);
        $this->telemetry->functionLog("error", "isUser", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "isuser_query");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "isuser_query");
        }
      }
    }

    // read user props
    public function readUser($id, $prop, $token) {
      $id = filter($id);
      $prop = filter($prop);
      $token = filter($token);

      // validate token
      $at = $this->getAuthUser($token);
      if(isset($at["error"])) {
        // invalid token
        $t = $this->telemetry->auth("unauthorized", "user.php > readUser() > getAuthUser()");
        $this->telemetry->functionLog("degraded", "readUser", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "unauthorized:".$at["error"]);
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "unauthorized:".$at["error"]);
        }

      } else {
        // validate user credentials
        $atID = $at["data"]["user_id"];
        $atUN = $at["data"]["username"];

        if($id != $atUN) {
          // Identifier is a username and does not match token
          $t = $this->telemetry->auth("unauthorized", "user.php > readUser() > getAuthUser()");
          $this->telemetry->functionLog("degraded", "readUser", $t["id"]);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "error" => "unauthorized");
          } else {
            return Array("logged" => "no: ".$t["d"], "error" => "unauthorized");
          }
        }

      }

      // check if exists
      $iU = $this->isUser($id);
      if($iU["isuser"] == true) {
        // set selector
        $selector = "WHERE username = '".$id."'";
      } elseif(isset($iU["error"])) {
        // error occurred, forward error from isUser
        if($iU["logged"] == "yes") {
          return Array("logged" => "yes", "error" => "isuser_failed");
        } else {
          return Array("logged" => $iU["logged"], "error" => "isuser_failed");
        }
      } else {
        // we don't exist
        $t = $this->telemetry->user("readUser_nonexistent", $id);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "readUser_nonexistent");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "readUser_nonexistent");
        }
      }

      // see if valid prop
      if($prop != "id" && $prop != "username" && $prop != "email" && $prop != "frozen" && $prop != "2fa") {
        $t = $this->telemetry->error("invalid_property", "user.php > readUser() > prop validation", $prop);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "invalid_property");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "invalid_property");
        }
      }

      // query
      if($rd = $this->dbc->query("SELECT * FROM `accounts` ".$selector." LIMIT 1")) {
        // process
        $t = $this->telemetry->user("readprop:".$prop, $id);
        $this->telemetry->functionLog("success", "readUser", $t["id"]);
        if($t["d"] == "success") {
          // actually process after we've logged the request
          while($d = $rd->fetch_assoc()) {
            return Array("logged" => "yes", "data" => Array($prop => $d[$prop]));
          }
        } else {
          $tt = $this->telemetry->error("insecure_userread", "user.php > readUser() > user query > query parse");
          $this->telemetry->functionLog("degraded", "readUser", $tt["id"]);
          return Array("logged" => "no: ".$t["d"], "error" => "insecure_userread");
        }
      } else {
        // error
        $t = $this->telemetry->error("userread_failed", "user.php > readUser() > user query", $this->dbc->error);
        $this->telemetry->functionLog("error", "readUser", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "userread_failed");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "userread_failed");
        }
      }

    }

    // create user
    public function register($user, $pass, $email) {

      // clean vars
      $user = filter($user);
      $pass = filter(password_hash($pass, PASSWORD_DEFAULT));
      $email = filter($email);

      // credential checks
      if(strlen($pass) < 5) {
        // weak password length
        $t = $this->telemetry->auth("weak_password", "user.php > register() > isUser()");
        $this->telemetry->functionLog("degraded", "register", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "weak_password");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "weak_password");
        }
      }

      // check if isUser
      $iU = $this->isUser($user);
      if($iU["isuser"] == true) {
        // respond username taken
        $t = $this->telemetry->error("register_usertaken", "user.php > register() > isUser()");
        $this->telemetry->functionLog("success", "register", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "registerusertaken");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "registerusertaken");
        }
      } elseif(isset($iU["error"])) {
        // an error occurred
        $t = $this->telemetry->error("register_isuser:".$iU["error"], "user.php > register() > isUser()");
        $this->telemetry->functionLog("success", "register", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "register_isuser:".$iU["error"]);
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "register_isuser:".$iU["error"]);
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
          $this->telemetry->functionLog("success", "register", $t["id"]);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "created" => true);
          } else {
            return Array("logged" => "no: ".$t["d"], "created" => true);
          }
        } else {
          // error
          $t = $this->telemetry->error("register_failed", "user.php > register() > isUser() > create query", $this->dbc->error);
          $this->telemetry->functionLog("error", "register", $t["id"]);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "error" => "register_failed");
          } else {
            return Array("logged" => "no: ".$t["d"], "error" => "register_failed");
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
      $this->telemetry->functionLog("success", "createTag", 0);
      return $str;
    }

    // modify user
    public function modifyUser($id, $prop, $value, $token) {
      // we gotta change these filters at some point boss, they sure are filtering a lot of things
      $prop = filter($prop);
      $id = filter($id);
      $value = filter($value);
      $token = filter($token);

      // validate token
      $at = $this->getAuthUser($token);
      if(isset($at["error"])) {
        // invalid token
        $t = $this->telemetry->auth("unauthorized", "user.php > readUser() > getAuthUser()");
        $this->telemetry->functionLog("degraded", "readUser", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "unauthorized:".$at["error"]);
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "unauthorized:".$at["error"]);
        }

      } else {
        // validate user credentials
        $atID = $at["data"]["user_id"];
        $atUN = $at["data"]["username"];

        if($id != $atUN) {
          // Identifier is a username and does not match token
          $t = $this->telemetry->auth("unauthorized", "user.php > readUser() > getAuthUser()");
          $this->telemetry->functionLog("degraded", "readUser", $t["id"]);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "error" => "unauthorized");
          } else {
            return Array("logged" => "no: ".$t["d"], "error" => "unauthorized");
          }
        }

      }

      $iU = $this->isUser($id);
      if($iU["isuser"] == true) {
        // set selector
        $selector = "WHERE username = '".$id."'";
      } elseif(isset($iU["error"])) {
        // error occurred, forward error from isUser
        if($iU["logged"] == "yes") {
          return Array("logged" => "yes", "error" => "isuser_failed");
        } else {
          return Array("logged" => $iU["logged"], "error" => "isuser_failed");
        }
      } else {
        // we don't exist
        $t = $this->telemetry->user("modifyUser_nonexistent", $id);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "modifyUser_nonexistent");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "modifyUser_nonexistent");
        }
      }


      // get old val
      $old = $this->readUser($id, $prop, $token);

      // check if errors returned
      if(isset($old["error"])) {
        return Array("logged" => $old["logged"], "error" => $old["error"]);
      } else {

        // query
        if($this->dbc->query("UPDATE `accounts` SET `".$prop."` = '".$value."'".$selector)){
          $t = $this->telemetry->user("modifyUser", $id);
          $this->telemetry->functionLog("success", "modifyUser", $t["id"]);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "data" => Array("result" => true, "old" => $old["data"][$prop], "new" => $value));
          } else {
            return Array("logged" => "no: ".$t["d"], "data" => Array("result" => true, "old" => $old["data"][$prop], "new" => $value));
          }
        } else {
          $t = $this->telemetry->error("modifyUser_query", $id);
          $this->telemetry->functionLog("error", "modifyUser", $t["id"]);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "error" => "modifyUser_nonexistent");
          } else {
            return Array("logged" => "no: ".$t["d"], "error" => "modifyUser_nonexistent");
          }
        }
      }
    }

  }


 ?>
