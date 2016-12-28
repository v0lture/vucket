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
  public function UP($u, $p){

    // validate
    if($u == "" || $p == "") {
      return Array("logged" => "ignored", "error" => "Username/password cannot be blank");
    } else {
      // check if account exists;
      if($d = $this->dbc->query("SELECT * FROM `accounts` WHERE username = '".filter($u). "';")) {

        if($d->num_rows > 1) {
          // check if weve got duplicate rows
          $t = $this->telemetry->auth("account_dupe", $u);
          if($t == "success") {
            return Array("logged" => "yes", "error" => "account_dupe");
          } else {
            return Array("logged" => "no: ".$t, "error" => "account_dupe");
          }
        } elseif($d->num_rows == 0) {
          // no results
          $t = $this->telemetry->auth("account_none", $u);
          if($t == "success") {
            return Array("logged" => "yes", "error" => "account_none");
          } else {
            return Array("logged" => "no: ".$t, "error" => "account_none");
          }
        } else {
          // validate password
          while($rd = $d->fetch_assoc()) {

            $dp = $rd["password"];
            if(password_verify($p, $dp)) {
              // witty comment
              // correct pass

              // set session and generate token

              // submit login to logs
              $token = $this->token($rd["tokentag"]);

              $t = $this->telemetry->auth("account_login", $u);
              if($t == "success") {
                return Array("logged" => "yes", "token" => $token["token"]);
              } else {
                return Array("logged" => "no: ".$t, "token" => $token["token"]);
              }

            } else {
              // wrong pass, log and return with error
              $t = $this->telemetry->auth("account_login_fail", $u);
              if($t == "success") {
                return Array("logged" => "yes", "error" => "invalid_credentials");
              } else {
                return Array("logged" => "no: ".$t, "error" => "invalid_credentials");
              }
            }
          }
        }

      } else {
        // error, log it, and respond back with details
        $t = $this->telemetry->error("account_filter", "auth.php > Auth > UP() > account validation", $this->dbc->error_list);
        if($t == "success") {
          return Array("logged" => "yes", "error" => "account_filter", "ctx" => $this->dbc->error_list);
        } else {
          return Array("logged" => "no: ".$t, "error" => "account_filter", "ctx" => $this->dbc->error_list);
        }
      }
    }
  }

  // token creation
  private function token($tag, $length = "week"){

    // code by: http://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
    // generate a random token at the length of 50
    $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%&*(){}[]-=';
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
      $t = $this->telemetry->auth("token", $data[1]);
      if($t == "success") {
        return Array("logged" => "yes", "token" => $str);
      } else {
        return Array("logged" => "no: ".$t, "token" => $str);
      }
    } else {
      // log error and return
      $t = $this->telemetry->error("token_creation", "auth.php > Auth > token() > token creation", $this->dbc->error_list);
      if($t == "success") {
        return Array("logged" => "yes", "error" => "token_creation", "ctx" => $this->dbc->error_list);
      } else {
        return Array("logged" => "no: ".$t, "error" => "token_creation", "ctx" => $this->dbc->error_list);
      }
    }

  }

}

 ?>
