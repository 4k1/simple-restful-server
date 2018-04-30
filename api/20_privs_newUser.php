<?php

    namespace privileges\newUser;

    // Includes
    include_once(__DIR__ . '/../incl/siteconf.php');

    // Load module
    loadModule('userprivs');
    loadModule('mysql_base');

    class MethodHandler {
    
        // POST /newUser
        function post_config() { return ["requireAppkey" => true, "requireUserAuth" => true]; }
        function post($api, $request, &$response) {
            
            // Get request json
            $obj_json = new \jsonBody($request);
            $post_json = $obj_json->getArray();
            
            // check params
            if (!isset($post_json["id"]) || !isset($post_json["pw"]) || !isset($post_json["fullname"]) || !isset($post_json["privlevel"])) throw new \Exception(ILLEGAL_LOGIC);
            
            // Connect to database
            $mybase = new \mysql_base\MySQLBase();
            $mybase->connect();
    
            // Check usertoken
            $userdata = [];
            if (!\userprivs\isEffectiveSession($mybase, $_SERVER['HTTP_X_API_AUTHENTICATION'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_USER_AGENT'], $userdata)) {
                // Session error
                error_log("[extendToken] > Cannot session extend.");
                throw new \Exception(SESSION_ERROR);
            }
            
            // Check superuser
            if ($userdata["privlevel"] != "0") {
                error_log("[newUser] > Denied to create new user by your privilege level.");
                throw new \Exception(ILLEGAL_LOGIC);
            }
            
            // Add user
            $new_utoken = $new_atoken = "";
            $pw = $post_json["pw"];
            $r = \userprivs\addNewUser($mybase, $post_json["id"], $pw, $post_json["fullname"], $post_json["privlevel"], $new_utoken, $new_atoken);
            $response["notice"]            = $r;
            $response["newuser_authtoken"] = $new_atoken;
            $response["newuser_usertoken"] = $new_utoken;
            if ($r != 0) throw new \Exception(ILLEGAL_LOGIC);
            
            // Succeeded
            return;
            
        }
        
    }
