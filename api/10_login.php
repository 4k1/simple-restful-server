<?php

    namespace login;

    // Includes
    include_once(__DIR__ . '/../incl/siteconf.php');

    // Load module
    loadModule('userprivs');
    loadModule('mysql_base');

    class MethodHandler {
    
        // POST /login
        function post_config() { return ["requireAppkey" => true, "requireUserAuth" => false]; }
        function post($api, $request, &$response) {
            
            // Get request json
            $obj_json = new \jsonBody($request);
            $post_json = $obj_json->getArray();
            
            // Check params required
            $uid = $upw = $atoken = "";
            if (isset($post_json["id"]) && isset($post_json["pw"])) {
                $uid = $post_json["id"];
                $upw = $post_json["pw"];
            } else {
                error_log("login > missing id or pw.");
                throw new \Exception(LOGIN_ERROR);
            }
            
            // Connect to database
            $mybase = new \mysql_base\MySQLBase();
            $mybase->connect();
            
            // try login
            $atoken = "";
            $r = \userprivs\login($mybase, $uid, $upw, $post_json["ip"], $post_json["ua"], $atoken);
            if ($r != 0) {
                error_log("Login failed. r = " . $r);
                throw new \Exception(LOGIN_ERROR);
            }
            
            // return User-token
            $response["authtoken"] = $atoken;
            throw new \Exception(NOERR);

            // Succeeded
            return;
            
        }
        
    }
