<?php

    namespace privileges\getUserList;

    // Includes
    include_once(__DIR__ . '/../incl/siteconf.php');

    // Load module
    loadModule('userprivs');
    loadModule('mysql_base');

    class MethodHandler {
    
        // GET /getUserList
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
        
            // Get user list
            $userlist = [];
            if (\userprivs\getUserList($mybase, $userlist) != 0) throw new \Exception(ILLEGAL_LOGIC);
            $response["data"] = $userlist;
                
            // Succeeded
            return;
            
        }
        
    }
