<?php

    namespace privileges\extendToken;

    // Includes
    include_once(__DIR__ . '/../incl/siteconf.php');

    // Load module
    loadModule('userprivs');
    loadModule('mysql_base');

    class MethodHandler {
    
        // GET /extendToken
        function get_config() { return ["requireAppkey" => true, "requireUserAuth" => true]; }
        function get($api, $request, &$response) {
            
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
            
            // Return userdata
            $response['userdata'] = $userdata;

            // Succeeded
            return;
            
        }
        
    }
