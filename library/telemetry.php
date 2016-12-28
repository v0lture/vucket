<?php

  require_once "parse.php";

  // telemetry
  // Error and access reporting
  class Telemetry {

    // database connector
    private $db;

    public function __construct($db) {
      // set $db
      $this->dbc = $db;
    }

    // error logging
    public function error($code, $trace, $mysql="none") {
      // init vars
      $ip = filter($_SERVER["REMOTE_ADDR"]);
      $code = filter($code);
      $trace = filter($trace);
      $time = filter(microtime(true));
      $mysql = filter($mysql);

      // submit to database
      if($this->dbc->query("INSERT INTO `log` (id, code, trace, mysql, ip, time, type) VALUES (NULL, '".$code."', '".$trace."', '".$mysql."', '".$ip."', '".$time."', 'error')")){
        return "success";
      } else {
        return "error: ".$this->dbc->error;
      }
    }

    // auth logging
    public function auth($code, $username) {
      // init vars
      $ip = filter($_SERVER["REMOTE_ADDR"]);
      $code = filter($code);
      $username = filter($username);
      $time = filter(microtime(true));
      $mysql = "";

      // submit to database
      if($this->dbc->query("INSERT INTO `log` (id, code, trace, mysql, ip, time, type) VALUES (NULL, '".$code."', '".$username."', '".$mysql."', '".$ip."', '".$time."', 'auth')")){
        return "success";
      } else {
        // logception
        $this->error("telemetry_auth", "telemetry.php > auth()", $this->dbc->error);
        return "error: ".$this->dbc->error;
      }
    }

    // token logging
    public function token($method, $token) {
      // init vars
      $ip = filter($_SERVER["REMOTE_ADDR"]);
      $method = filter($method);
      $token = filter($token);
      $time = filter(microtime(true));
      $mysql = "";

      // submit to database
      if($this->dbc->query("INSERT INTO `log` (id, code, trace, mysql, ip, time, type) VALUES (NULL, '".$method."', '".$token."', '".$mysql."', '".$ip."', '".$time."', 'token')")){
        return "success";
      } else {
        // logception
        $this->error("telemetry_auth", "telemetry.php > auth()", $this->dbc->error);
        return "error: ".$this->dbc->error;
      }
    }

  }

 ?>