<?php

  class BillingCycle {

    private $user;
    private $auth;
    private $dbc;
    private $telemetry;

    // Manages billing cycles
    public function __construct($auth, $db, $telemetry, $user){
      $this->user = $user;
      $this->auth = $auth;
      $this->dbc = $db;
      $this->telemetry = $telemetry;
    }

    public function fetch($token) {

      // get user data
      $at = $this->user->getAuthUser($token);
      if(isset($at["error"])) {
        // invalid token
        $t = $this->telemetry->auth("unauthorized", "BillingCycle.fetch() > Auth.getAuthUser()");
        $this->telemetry->functionLog("degraded", "BillingCycle.fetch", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "unauthorized:".$at["error"]);
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "unauthorized:".$at["error"]);
        }

      } else {
        // validate user credentials
        $atID = $at["data"]["user_id"];
        $atUN = $at["data"]["username"];
      }

      // fetch user ID row
      if($rawdata = $this->dbc->query("SELECT * FROM `vucket`.`cycles` WHERE userid = '".$atID."' LIMIT 1")) {
        if($rawdata->num_rows == 0) {
          // no data
          $t = $this->telemetry->billing("billingcycle_empty", $atUN);
          $this->telemetry->functionLog("degraded", "BillingCycle.fetch", $t["id"]);

          if($t["d"] == "success") {
            return Array("logged" => "yes", "error" => "billingcycle_empty", "meta" => Array("userid" => $atID, "username" => $atUN));
          } else {
            return Array("logged" => "no: ".$t["d"], "error" => "billingcycle_empty", "meta" => Array("userid" => $atID, "username" => $atUN));
          }
        } else {
          // we got data
          while($data = $rawdata->fetch_assoc()) {
            $t = $this->telemetry->billing("cycle_dates_fetched", $atUN);
            $this->telemetry->functionLog("success", "BillingCycle.fetch", $t["id"]);

            if($t["d"] == "success") {
              return Array("logged" => "yes", "data" => Array("userid" => $data["userid"], "type" => $data["type"], "dayofmonth" => $data["dayofmo"]));
            } else {
              return Array("logged" => "no: ".$t["d"], "data" => Array("userid" => $data["userid"], "type" => $data["type"], "dayofmonth" => $data["dayofmo"]));
            }
          }
        }
      } else {
        // mysql error
        $t = $this->telemetry->error("biilingcycle_query_failed", "BillingCycle.fetch()", $this->dbc->error);
        $this->telemetry->functionLog("error", "BillingCycle.fetch", $t["id"]);

        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "biilingcycle_query_failed");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "biilingcycle_query_failed");
        }
      }
    }

    public function remaining($token) {
      $day = $this->fetch($token);
      $today = Date('j');
      $daysinmo = Date('t');

      // check if we got an error
      if(isset($day["error"])) {
        // return error
        return $day;
      } else {
        
        // check if cycle day is greater than month
        if($day["data"]["dayofmonth"] > $daysinmo) {
          // It's probably February
          $t = $day["data"]["dayofmonth"] - $daysinmo;
          $actualend = $day["data"]["dayofmonth"] - $t;
        } else {
          $actualend = $day["data"]["dayofmonth"];
        }

        // get remaining days
        $remaining = $actualend - $today;

        // log and return
        $t = $this->telemetry->billing("cycle_dates_fetched_remaining", $day["data"]["userid"]);
        $this->telemetry->functionLog("success", "BillingCycle.remaining", $t["id"]);

        if($t["d"] == "success") {
          return Array("logged" => "yes", "data" => Array("userid" => $day["data"]["userid"], "today" => $today, "dayofmonth" => $day["data"]["dayofmonth"], "remaining" => $remaining));
        } else {
          return Array("logged" => "no: ".$t["d"], "data" => Array("userid" => $day["data"]["userid"], "today" => $today, "dayofmonth" => $day["data"]["dayofmonth"], "remaining" => $remaining));
        }

      }
    }

    public function assign($token, $day) {
      $fetch = $this->fetch($token);
      $day = preg_replace("/[^0-9]+/", "", $day);

      // check if we got an error
      if(isset($fetch["error"])) {
        if($fetch["error"] == "billingcycle_empty") {
          // insert
          if($this->dbc->query("INSERT INTO `vucket`.`cycles` (`id`, `type`, `userid`, `dayofmo`) VALUES (NULL, '1', '".$fetch["meta"]["userid"]."', '".$day."')")){
            // success
            $t = $this->telemetry->billing("cycle_assigned", $fetch["meta"]["userid"]);
            $this->telemetry->functionLog("success", "BillingCycle.ssign", $t["id"]);

            if($t["d"] == "success") {
              return Array("logged" => "yes", "data" => Array("userid" => $fetch["meta"]["userid"], "dayofmonth" => $day));
            } else {
              return Array("logged" => "no: ".$t["d"], "data" => Array("userid" => $fetch["meta"]["userid"], "today" => $today, "dayofmonth" => $day));
            }
          } else {
            // mysql error
            $t = $this->telemetry->error("billingcycle_assign_query_failed", "BillingCycle.assign()");
            $this->telemtry->functionLog("error", "BillingCycle.assign", $t["id"]);

            if($t["d"] == "success") {
              return Array("logged" => "yes", "error" => "billingcycle_assign_query_failed");
            } else {
              return Array("logged" => "no: ".$t["d"], "error" => "billingcycle_assign_query_failed");
            }
          }
        } else {
          // return error
          return $fetch;
        }
      } else {
        // return data
        return $fetch;
      }
    }

  }

?>