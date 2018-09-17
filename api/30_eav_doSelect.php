<?php

    namespace doSelect;

    // Load module
    loadModule('userprivs');
    loadModule('mysql_eav');

    class MethodHandler {
    
        // POST /doSelect
        function post_config() { return ["requireAppkey" => true, "requireUserAuth" => true]; }
        function post_privkey($api, $request, $privkey) { 

            // Get request json
            $obj_json = new \jsonBody($request);
            $post_json = $obj_json->getArray();
            
            return array($privkey, $privkey . "::" . $post_json["gkey"]);

        }
        function post($api, $request, &$response) {
            
            // Get request json
            $obj_json = new \jsonBody($request);
            $post_json = $obj_json->getArray();
            
            // Check relative id
            if (isset($post_json["id"]) && $post_json["id"] != "") {
                $json_id = $post_json["id"];
                $rv = "AND rkey = ? ";
            } else {
                $json_id = "";
                $rv = "";
            }

            // Check parent id
            if (isset($post_json["parent"]) && $post_json["parent"] != "") {
                $json_parent = $post_json["parent"];
                $pv = "AND pkey = ? ";
            } else {
                $json_parent = "";
                $pv = "";
            }

            // Connect to database
            $mybase = new \mysql_eav\EAV();
            $mybase->connect();

            // Callback - onDBConnected
            $cbarr = array("dbi" => $mybase->getMySQLi(), "user" => config("database.user", null), "database" => config("database.name", null));
            callbackTo('onDBConnected', $cbarr);

            // Check usertoken
            $userdata = [];
            if (!\userprivs\isEffectiveSession($mybase, $_SERVER['HTTP_X_API_AUTHENTICATION'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_USER_AGENT'], $userdata)) {
                // Session error
                error_log("[extendToken] > Cannot session extend.");
                throw new \Exception(SESSION_ERROR);
            }
                 
            // Prepare SQL
            $sql_where = $updtoken = "";            
            $response["data"]     = $mybase->general_fetch($post_json, $mybase->getMySQLi(), $sql_where, $updtoken, $post_json["gkey"], $json_parent, $json_id);
            $response["updtoken"] = $updtoken;
            
            // Succeeded
            return;
            
        }
        
    }
