<?php

require_once "parse.php";

class Auth {

  private $telemetry;
  private $dbc;

  public function __construct($db, $telemetry) {
    $this->dbc = $db;
    $this->telemetry = $telemetry;
  }

  // login with user and pass
  public function login($u, $p){

    // validate
    if($u == "" || $p == "") {
      return Array("logged" => "ignored", "error" => "Username/password cannot be blank");
    } else {
      // check if account exists;
      if($d = $this->dbc->query("SELECT * FROM `accounts` WHERE username = '".filter($u). "';")) {

        if($d->num_rows > 1) {
          // check if weve got duplicate rows
          $t = $this->telemetry->auth("account_dupe", $u);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "error" => "account_dupe");
          } else {
            return Array("logged" => "no: ".$t["d"], "error" => "account_dupe");
          }
        } elseif($d->num_rows == 0) {
          // no results
          $t = $this->telemetry->auth("account_none", $u);
          if($t["d"] == "success") {
            return Array("logged" => "yes", "error" => "account_none");
          } else {
            return Array("logged" => "no: ".$t["d"], "error" => "account_none");
          }
        } else {
          // validate password
          while($rd = $d->fetch_assoc()) {

            $dp = $rd["password"];
            if(password_verify($p, $dp)) {
              // witty comment
              // correct pass

              // is frozen?
              if($rd["frozen"] == 0) {

                // melted, proceed with token
                $token = $this->token($rd["tokentag"]);

                $t = $this->telemetry->auth("account_login", $u);
                if($t["d"] == "success") {
                  return Array("logged" => "yes", "token" => $token["token"]);
                } else {
                  return Array("logged" => "no: ".$t["d"], "token" => $token["token"]);
                }
              } else {
                // frozen, bounce back with frozen error
                $t = $this->telemetry->auth("account_login_frozen", $u);
                if($t["d"] == "success") {
                  return Array("logged" => "yes", "error" => "account_frozen");
                } else {
                  return Array("logged" => "no: ".$t["d"], "error" => "account_frozen");
                }
              }

            } else {
              // wrong pass, log and return with error
              $t = $this->telemetry->auth("account_login_fail", $u);
              if($t["d"] == "success") {
                return Array("logged" => "yes", "error" => "invalid_credentials");
              } else {
                return Array("logged" => "no: ".$t["d"], "error" => "invalid_credentials");
              }
            }
          }
        }

      } else {
        // error, log it, and respond back with details
        $t = $this->telemetry->error("account_filter", "auth.php > Auth > UP() > account validation", $this->dbc->error_list);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "account_filter", "ctx" => $this->dbc->error_list);
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "account_filter", "ctx" => $this->dbc->error_list);
        }
      }
    }
  }

  // token creation
  private function token($tag, $length = "week"){

    // code by: http://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
    // generate a random token at the length of 50
    $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < 50; ++$i) {
      $str .= $keyspace[random_int(0, $max)];
    }

    // token activation time
    $activate = microtime(true);

    // set expiration based on token length
    if($length == "week") {
      $l = 605000000;
    } elseif($length == "day") {
      $l = 86400000;
    } elseif($length == "hour") {
      $l = 360000;
    } else {
      // 2 years
      $l = 63000000000;
    }

    // token expiration time, activation + length
    $expire = $activate+$l;

    // clean everything
    $data[0] = filter($tag);
    $data[1] = filter($str);
    $data[2] = number_format(filter($activate), 0, '', '');
    $data[3] = number_format(filter($expire), 0, '', '');


    // insert token
    if($this->dbc->query("INSERT INTO `tokens` (`id`, `tokentag`, `token`, `activation`, `expiration`) VALUES (NULL, '".$data[0]."', '".$data[1]."', '".$data[2]."', '".$data[3]."');")) {
      // success, log and return token
      $t = $this->telemetry->token("generate", $data[1]);
      if($t["d"] == "success") {
        return Array("logged" => "yes", "token" => $str);
      } else {
        return Array("logged" => "no: ".$t["d"], "token" => $str);
      }
    } else {
      // log error and return
      $t = $this->telemetry->error("token_creation", "auth.php > Auth > token() > token creation", $this->dbc->error_list);
      if($t["d"] == "success") {
        return Array("logged" => "yes", "error" => "token_creation", "ctx" => $this->dbc->error_list);
      } else {
        return Array("logged" => "no: ".$t["d"], "error" => "token_creation", "ctx" => $this->dbc->error_list);
      }
    }

  }

  // token validation
  public function validateToken($token) {

    // vars
    $token = filter($token);

    // query
    if($rd = $this->dbc->query("SELECT * FROM `tokens` WHERE token = '".$token."'")) {
      // only one result
      if($rd->num_rows == 1) {
        // yes, token exists

        // check expiration
        $current = microtime(true);
        while($d = $rd->fetch_assoc()) {
          if($d["expiration"] > $current) {
            // not expired
            $t = $this->telemetry->token("use", $token);
            if($t["d"] == "success") {
              // only reply with tokentag additionally IF the event was logged
              return Array("logged" => "yes", "token" => "is_valid", "tokentag" => $d["tokentag"]);
            } else {
              return Array("logged" => "no: ".$t["d"], "token" => "is_valid");
            }
          } else {
            // expired, like the yogurt in my fridge
            $t = $this->telemetry->token("expired", $token);
            if($t["d"] == "success") {
              return Array("logged" => "yes", "error" => "token_expired");
            } else {
              return Array("logged" => "no: ".$t["d"], "error" => "token_expired");
            }
          }
        }

      } elseif($rd->num_rows > 1) {
        // multiple results
        $t = $this->telemetry->error("overlapping_token", "auth.php > Auth > validateToken() > query", $this->dbc->error);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "token_filter");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "token_filter");
        }
      } else {
        // no results
        $t = $this->telemetry->auth("token_invalid", $token);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "token_invalid");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "token_invalid");
        }
      }

    } else {
      // can't query this (https://www.youtube.com/watch?v=otCpCn0l4Wo)
      $t = $this->telemetry->error("token_filter", "auth.php > Auth > validateToken() > query", $this->dbc->error);
      if($t["d"] == "success") {
        return Array("logged" => "yes", "error" => "token_filter");
      } else {
        return Array("logged" => "no: ".$t["d"], "error" => "token_filter");
      }
    }

  }

}

 ?>
