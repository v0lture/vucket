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
        $t = $this->telemetry->auth("unauthorized", "vAT.subscriptions() > User.getAuthUser()");
        $this->telemetry->functionLog("degraded", "vAT.subscriptions", $t["id"]);
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
          $this->telemetry->functionLog("success", "vAT.subscriptions", $t["id"]);

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
          $this->telemetry->functionLog("success", "vAT.subscriptions", $t["id"]);

          if($t["d"] == "success") {
            return Array("logged" => "yes", "data" => Array("count" => $d->num_rows, "apps" => $rd)); 
          } else {
            return Array("logged" => "no: ".$t["d"], Array("count" => $d->num_rows, "apps" => $rd));
          }
        }


      } else {
        $t = $this->telemetry->error("subscriptions_query_failed", "vAT.subscriptions() > User.getAuthUser() > query", $this->dbc->error);
        $this->telemetry->functionLog("error", "vAT.subscriptions", $t["id"]);
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
        $t = $this->telemetry->auth("unauthorized", "vAT.UpdatesSubscription() > User.getAuthUser()");
        $this->telemetry->functionLog("degraded", "vAT.updateSubscription", $t["id"]);
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
        $t = $this->telemetry->auth("invalid_ring", "vAT.updateSubscription() > ring validation");
        $this->telemetry->functionLog("degraded", "vAT.updateSubscription", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "invalid_ring");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "invalid_ring");
        }
      }

      // update subscription
      if($d = $this->dbc->query("UPDATE `vucket`.`vat` SET `ring` = ".filter($ring)." WHERE `userid` = ".filter($atID)." AND `app` = '".filter($app)."'")) {
        $t = $this->telemetry->user("vat_subscription_change", $atUN);
        $this->telemetry->functionLog("success", "vAT.updateSubscription", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "data" => Array("success" => true));
        } else {
          return Array("logged" => "no: ".$t["d"], "data" => Array("success"));
        }
      } else {
        $t = $this->telemetry->error("subscribe_query_failed", "vAT.updateSubscription() > User.getAuthUser() > query", $this->dbc->error);
        $this->telemetry->functionLog("error", "vAT.updateSubscription", $t["id"]);
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
        $t = $this->telemetry->auth("unauthorized", "vAT.subscribe() > User.getAuthUser()");
        $this->telemetry->functionLog("degraded", "vAT.subscribe", $t["id"]);
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
        $t = $this->telemetry->auth("invalid_ring", "vAT.subscribe() > ring validation");
        $this->telemetry->functionLog("degraded", "vAT.subscribe", $t["id"]);
        if($t["d"] == "success") {
          return Array("logged" => "yes", "error" => "invalid_ring");
        } else {
          return Array("logged" => "no: ".$t["d"], "error" => "invalid_ring");
        }
      }

      if(isset($subs["error"])) {
        // relay the error above
        $this->telemetry->functionLog("error", "vAT.subscribe", 0);
        return Array("logged" => $subs["logged"], "error" => $subs["error"]);
      } else {
        // to make phpunit happy, check if the results array has a value
        $subsset = false;
        if(isset($subs["data"]["apps"])){
          $subsset = in_array($app, array_column($subs["data"]["apps"], "app"));
        }

        // check if there we already exist
        if($subsset) {
          // update instead
          $rr = $this->updateSubscription($token, $app, $ring);
          if(isset($rr["error"])) {
            $this->telemetry->functionLog("error", "vAT.subscribe", 0);
            return $rr;
          } else {
            $this->telemetry->functionLog("success", "vAT.subscribe", 0);
            return $rr;
          }
        } else {
          // add instead
          if($this->dbc->query("INSERT INTO `vucket`.`vat` (`id`, `userid`, `app`, `ring`) VALUES (NULL, '".$atID."', '".$app."', '".$ring."')")){
            // great
            $t = $this->telemetry->user("vat_subscribed", $atUN);
            $this->telemetry->functionLog("success", "vAT.subscribe", $t["id"]);

            if($t["d"] == "success") {
              return Array("logged" => "yes", "data" => Array("subscribed" => true));
            } else {
              return Array("logged" => "no: ".$t["d"], "data" => Array("subscribed" => true));
            }
          } else {
            // you had one job MySQL
            $t = $this->telemetry->error("subscribe_query_failed", "vAT.subscribe() > User.getAuthUser() > query", $this->dbc->error);
            $this->telemetry->functionLog("error", "vAT.subscribe", $t["id"]);

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