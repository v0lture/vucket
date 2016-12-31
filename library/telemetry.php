<?php

  require_once "parse.php";

  // telemetry
  // Error and access reporting
  class Telemetry {

    // database connector
    private $dbc;

    public function __construct($db) {
      // set $db
      $this->dbc = $db;
    }

    // Function logging
    // Monitors function run times and their state
    // States: success, degraded, error
    // Meant to be a "try-and-see" analytic, result is typically ignored unless needed

    public function functionLog($state, $func, $logid) {
      // init vars
      $state = filter($state);
      $func = filter($func);
      $logid = filter($logid);
      $time = filter(microtime(true));

      // submit to database
      if($this->dbc->query("INSERT INTO `functions` (id, time, state, func, logid) VALUES (NULL, '".$time."', '".$state."', '".$func."', '".$logid."')")){
        return "success";
      } else {
        return "error: ".$this->dbc->error;
      }
    }

    // Event logging
    // Logs all the events
    // Logs event type, trace/data, mysql error, or other relative data

    // error logging
    public function error($code, $trace, $mysql="none") {
      // init vars
      // Travis CI IP override
      if(isset($_SERVER["REMOTE_ADDR"])) {
        $ip = filter($_SERVER["REMOTE_ADDR"]);
      } else {
        $ip = "testing_environment";
      }
      $code = filter($code);
      $trace = filter($trace);
      $time = filter(microtime(true));
      $mysql = filter($mysql);

      // submit to database
      if($this->dbc->query("INSERT INTO `log` (id, code, trace, mysql, ip, time, type) VALUES (NULL, '".$code."', '".$trace."', '".$mysql."', '".$ip."', '".$time."', 'error')")){
        return Array("id" => $this->dbc->insert_id, "d" => "success");
      } else {
        return Array("id" => $this->dbc->insert_id, "d" => "error: ".$this->dbc->error);
      }
    }

    // auth logging
    public function auth($code, $username) {
      // init vars
      // Travis CI IP override
      if(isset($_SERVER["REMOTE_ADDR"])) {
        $ip = filter($_SERVER["REMOTE_ADDR"]);
      } else {
        $ip = "testing_environment";
      }
      $code = filter($code);
      $username = filter($username);
      $time = filter(microtime(true));
      $mysql = "";

      // submit to database
      if($this->dbc->query("INSERT INTO `log` (id, code, trace, mysql, ip, time, type) VALUES (NULL, '".$code."', '".$username."', '".$mysql."', '".$ip."', '".$time."', 'auth')")){
        return Array("id" => $this->dbc->insert_id, "d" => "success");
      } else {
        // logception
        $this->error("telemetry_auth", "telemetry.php > auth()", $this->dbc->error);
        return Array("id" => $this->dbc->insert_id, "d" => "error: ".$this->dbc->error);
      }
    }

    // token logging
    public function token($method, $token) {
      // init vars
      // Travis CI IP override
      if(isset($_SERVER["REMOTE_ADDR"])) {
        $ip = filter($_SERVER["REMOTE_ADDR"]);
      } else {
        $ip = "testing_environment";
      }
      $method = filter($method);
      $token = filter($token);
      $time = filter(microtime(true));
      $mysql = "";

      // submit to database
      if($this->dbc->query("INSERT INTO `log` (id, code, trace, mysql, ip, time, type) VALUES (NULL, '".$method."', '".$token."', '".$mysql."', '".$ip."', '".$time."', 'token')")){
        return Array("id" => $this->dbc->insert_id, "d" => "success");
      } else {
        // logception
        $this->error("telemetry_auth", "telemetry.php > token()", $this->dbc->error);
        return Array("id" => $this->dbc->insert_id, "d" => "error: ".$this->dbc->error);
      }
    }

    // user logging
    public function user($action, $logtag) {
      // init vars
      // Travis CI IP override
      if(isset($_SERVER["REMOTE_ADDR"])) {
        $ip = filter($_SERVER["REMOTE_ADDR"]);
      } else {
        $ip = "testing_environment";
      }
      $action = filter($action);
      $logtag = filter($logtag);
      $time = filter(microtime(true));
      $mysql = "";

      // submit to database
      if($this->dbc->query("INSERT INTO `log` (id, code, trace, mysql, ip, time, type) VALUES (NULL, '".$action."', '".$logtag."', '".$mysql."', '".$ip."', '".$time."', 'user')")){
        return Array("id" => $this->dbc->insert_id, "d" => "success");
      } else {
        // logception
        $this->error("telemetry_auth", "telemetry.php > user()", $this->dbc->error);
        return Array("id" => $this->dbc->insert_id, "d" => "error: ".$this->dbc->error);
      }
    }

  }

 ?>
