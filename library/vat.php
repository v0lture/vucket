<?php

  require_once "parse.php";

  class vAT {

    private $auth;
    private $dbc;
    private $telemetry;
    private $user;
    // vAT Rings
    private $rings = Array(0 => "stable", 1 => "beta", 2 => "alpha", 3 => "nightly");

    // get needed classes
    public function __construct($auth, $db, $telemetry, $user) {
      $this->auth = $auth;
      $this->dbc = $db;
      $this->telemetry = $telemetry;
      $this->user = $user;
    }

    // get sub. status
    public function subscriptions($token){

      // iterator
      $i = 0;

      // get user data
      $at = $this->user->getAuthUser($token);
      if(isset($at["error"])) {
        // invalid token
        $t = $this->telemetry->auth("unauthorized", "vat.php > subscriptions() > getAuthUser()");
        $this->telemetry->functionLog("degraded", "subscriptions", $t["id"]);
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

      // get subscriptions
      if($d = $this->dbc->query("SELECT * FROM `vucket`.`vat` WHERE `userid` = ".filter($atID))) {

        // no results
        if($d->num_rows == 0) {
          $t = $this->telemetry->user("get_vat_subs", $atUN);
          $this->telemetry->functionLog("success", "subscriptions", $t["id"]);

          if($t["d"] == "success") {
            return Array("logged" => "yes", "data" => Array("count" => 0)); 
          } else {
            return Array("logged" => "no: ".$t["d"], Array("count" => 0));
          }
        } else {
          // results
          while($data = $d->fetch_assoc()) {
            $rd[$i] = Array("app" => $data["app"], "ring" => $this->rings[$data["ring"]], "ring-id" => $data["ring"]);
            $i++;
          }

          // return
          $t = $this->telemetry->user("get_vat_subs", $atUN);
          $this->telemetry->functionLog("success", "subscriptions", $t["id"]);

          if($t["d"] == "success") {
            return Array("logged" => "yes", "data" => Array("count" => $d->num_rows, "apps" => $rd)); 
          } else {
            return Array("logged" => "no: ".$t["d"], Array("count" => $d->num_rows, "apps" => $rd));
          }
        }


      } else {
        $t = $this->telemetry->error("subscriptions_query_failed", "vat.php > subscriptions() > getAuthUser() > query", $this->dbc->error);
        $this->telemetry->functionLog("error", "subscriptions", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "subscriptions_query_failed");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "subscriptions_query_failed");
        }
      }
    }

    // change sub. status
    public function updateSubscription($token, $app, $ring) {

      // get user data
      $at = $this->user->getAuthUser($token);
      if(isset($at["error"])) {
        // invalid token
        $t = $this->telemetry->auth("unauthorized", "vat.php > subscriptions() > getAuthUser()");
        $this->telemetry->functionLog("degraded", "subscriptions", $t["id"]);
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

      // validate ring and apps
      if($ring > 3){
        $t = $this->telemetry->auth("invalid_ring", "vat.php > subscribe() > ring validation");
        $this->telemetry->functionLog("degraded", "invalid_ring", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "invalid_ring");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "invalid_ring");
        }
      }

      // update subscription
      if($d = $this->dbc->query("UPDATE `vucket`.`vat` SET `ring` = ".filter($ring)." WHERE `userid` = ".filter($atID)." AND `app` = '".filter($app)."'")) {
        $t = $this->telemetry->user("vat_subscription_change", $atUN);
        $this->telemetry->functionLog("success", "subscribe", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "data" => Array("success" => true));
        } else {
          return Array("logged" => "no: ".$t["d"], "data" => Array("success"));
        }
      } else {
        $t = $this->telemetry->error("subscribe_query_failed", "vat.php > subscriptions() > getAuthUser() > query", $this->dbc->error);
        $this->telemetry->functionLog("error", "subscribe", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "subscribe_query_failed");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "subscribe_query_failed");
        }
      }
    }

    // subscribe to new app
    public function subscribe($token, $app, $ring){
      $subs = $this->subscriptions($token);
      $app = filter($app);
      $ring = filter($ring);

      // get user data
      $at = $this->user->getAuthUser($token);
      if(isset($at["error"])) {
        // invalid token
        $t = $this->telemetry->auth("unauthorized", "vat.php > subscriptions() > getAuthUser()");
        $this->telemetry->functionLog("degraded", "subscriptions", $t["id"]);
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

      // validate ring and apps
      if($ring > 3){
        $t = $this->telemetry->auth("invalid_ring", "vat.php > subscribe() > ring validation");
        $this->telemetry->functionLog("degraded", "invalid_ring", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "invalid_ring");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "invalid_ring");
        }
      }

      if(isset($subs["error"])) {
        // relay the error above
        $this->telemetry->functionLog("error", "subscribe", 0);
        return Array("logged" => $subs["logged"], "error" => $subs["error"]);
      } else {
        // check if there we already exist
        if(in_array($app, array_column($subs["data"]["apps"], "app"))) {
          // update instead
          $rr = $this->updateSubscription($token, $app, $ring);
          if(isset($rr["error"])) {
            $this->telemetry->functionLog("error", "subscribe", 0);
            return $rr;
          } else {
            $this->telemetry->functionLog("success", "subscribe", 0);
            return $rr;
          }
        } else {
          // add instead
          if($this->dbc->query("INSERT INTO `vucket`.`vat` (`id`, `userid`, `app`, `ring`) VALUES (NULL, '".$atID."', '".$app."', '".$ring."')")){
            // great
            $t = $this->telemetry->user("vat_subscribed", $atUN);
            $this->telemetry->functionLog("success", "subscribe", $t["id"]);

            if($t["d"] == "success") {
              return Array("logged" => "yes", "data" => Array("subscribed" => true));
            } else {
              return Array("logged" => "no: ".$t["d"], "data" => Array("subscribed" => true));
            }
          } else {
            // you had one job MySQL
            $t = $this->telemetry->error("subscribe_query_failed", "vat.php > subscribe() > getAuthUser() > query", $this->dbc->error);
            $this->telemetry->functionLog("error", "subscribe_query_failed", $t["id"]);

            if($t["d"] == "success") {
              return Array("logged" => "yes", "error" => "subscribe_query_failed");
            } else {
              return Array("logged" => "no: ".$t["d"], "error" => "subscribe_query_failed");
            }
          }
        }
      }
    }

  }

?>