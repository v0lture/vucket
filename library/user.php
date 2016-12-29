<?php


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

  }


 ?>
