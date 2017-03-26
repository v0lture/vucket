<?php

    class Health {

        private $dbc;
        private $telemetry;

        // set those variables
        public function __construct($db, $telemetry) {
            $this->dbc = $db;
            $this->telemetry = $telemetry;
        }

        // wrapper
        public function fetch(){
            $cached = $this->cached();

            // if age is over 3600 (1 hour) in seconds
            if($cached["data"]["age"] > 3600){
                // rebuild cached data and return it
                $rebuilt = $this->rebuild();
                if(isset($rebuilt["result"])) {
                    return $this->cached();
                } else {
                    return $rebuilt;
                }
            } else {
                // return cached data
                return $cached;
            }
        }

        // load cache result
        private function cached() {

            // fetch & error catch
            if($rawdata = $this->dbc->query("SELECT * FROM `vucket`.`health_cache` ORDER BY `health_cache`.`created` DESC LIMIT 1")){

                // check if any results
                if($rawdata->num_rows == 0) {
                    $this->telemetry->functionLog("degraded", "Health.cached", 0);
                    return Array("logged" => "yes", "data" => Array("age" => "36000000", "human_age" => "never updated", "success" => 0, "degraded" => 0, "error" => 0));
                }
                
                // parse
                while($data = $rawdata->fetch_assoc()) {
                    $this->telemetry->functionLog("success", "Health.cached", 0);

                    $currentseconds = microtime(true);
                    $difference = $currentseconds - $data["created"];

                    if($difference < 60) {
                        // Under 60 seconds
                        $human_age = "under a minute ago";
                    } elseif($difference >= 60 && $difference < 3600) {
                        // Over 60 seconds but under 1 hour
                        $num = round($difference / 60);
                        $human_age = $num." minutes ago";
                    } else {
                        // it exceeds what this data will be cached for
                        $human_age = "over an hour ago";
                    }

                    // This is technically correct.
                    // The best kind of correct.
                    return Array("logged" => "yes", "data" => Array("age" => round($difference), "human_age" => $human_age, "success" => $data["success"], "degraded" => $data["degraded"], "error" => $data["error"]));
                }
            } else {
                // an error occurred
                $t = $this->telemetry->error("health_cache_query_failed", "Health.fetch > Health.cached > query", $this->dbc->error);
                $this->telemetry->functionLog("error", "Health.cached", $t["id"]);

                // check if log succeeded and return
                if($t["d"] == "success") {
                    return Array("logged" => "yes", "error" => "health_cache_query_failed");
                } else {
                    return Array("logged" => "no: ".$t["d"], "error" => "health_cache_query_failed");
                }
            }
        }

        // regenerate cache result
        private function rebuild() {
            // oh hey Unity, haven't seen you in a while
            $unitytimestamp = (microtime(true) - 3600);

            // Success query
            if(!$successes = $this->dbc->query("SELECT * FROM `vucket`.`functions` WHERE `state` = 'success' AND `time` >= '".$unitytimestamp."';")) {
                // an error occurred
                $t = $this->telemetry->error("health_cache_rebuild_success_query_failed", "Health.rebuild > query", $this->dbc->error);
                $this->telemetry->functionLog("error", "Health.rebuild", $t["id"]);

                // check if log succeeded and return
                if($t["d"] == "success") {
                    return Array("logged" => "yes", "error" => "health_cache_rebuild_success_query_failed");
                } else {
                    return Array("logged" => "no: ".$t["d"], "error" => "health_cache_rebuild_success_query_failed");
                }
            }

            // Degraded query
            if(!$degraded = $this->dbc->query("SELECT * FROM `vucket`.`functions` WHERE `state` = 'degraded' AND `time` >= '".$unitytimestamp."';")) {
                // an error occurred
                $t = $this->telemetry->error("health_cache_rebuild_degraded_query_failed", "Health.rebuild > query", $this->dbc->error);
                $this->telemetry->functionLog("error", "Health.rebuild", $t["id"]);

                // check if log succeeded and return
                if($t["d"] == "success") {
                    return Array("logged" => "yes", "error" => "health_cache_rebuild_degraded_query_failed");
                } else {
                    return Array("logged" => "no: ".$t["d"], "error" => "health_cache_rebuild_degraded_query_failed");
                }
            }

            // Error query
            if(!$errors = $this->dbc->query("SELECT * FROM `vucket`.`functions` WHERE `state` = 'error' AND `time` >= '".$unitytimestamp."';")) {
                // an error occurred
                $t = $this->telemetry->error("health_cache_rebuild_error_query_failed", "Health.rebuild > query", $this->dbc->error);
                $this->telemetry->functionLog("error", "Health.rebuild", $t["id"]);

                // check if log succeeded and return
                if($t["d"] == "success") {
                    return Array("logged" => "yes", "error" => "health_cache_rebuild_error_query_failed");
                } else {
                    return Array("logged" => "no: ".$t["d"], "error" => "health_cache_rebuild_error_query_failed");
                }
            }

            // everything passed A-OK!
            if($this->dbc->query("INSERT INTO `vucket`.`health_cache` (`id`, `created`, `timeframe`, `success`, `degraded`, `error`) VALUES (NULL, '".microtime(true)."', '3600', '".$successes->num_rows."', '".$degraded->num_rows."', '".$errors->num_rows."')")) {
                // an error occurred
                $this->telemetry->functionLog("success", "Health.rebuild", 0);
                return Array("logged" => "yes", "result" => "success");

            } else {
                // an error occurred
                $t = $this->telemetry->error("health_cache_rebuild_cache_query_failed", "Health.rebuild > insert query", $this->dbc->error);
                $this->telemetry->functionLog("error", "Health.rebuild", $t["id"]);

                // check if log succeeded and return
                if($t["d"] == "success") {
                    return Array("logged" => "yes", "error" => "health_cache_rebuild_cache_query_failed");
                } else {
                    return Array("logged" => "no: ".$t["d"], "error" => "health_cache_rebuild_cache_query_failed");
                }
            }


        }

    }

?>