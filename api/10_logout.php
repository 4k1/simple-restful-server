<?php

    namespace logout;

    // Includes
    include_once(__DIR__ . '/../incl/siteconf.php');

    // Load module
    loadModule('userprivs');
    loadModule('mysql_base');

    class MethodHandler {
    
        // GET /logout
        function get_config() { return ["requireAppkey" => true, "requireUserAuth" => true]; }
        function get($api, $request, &$response) {

            // Connect to database
            $mybase = new \mysql_base\MySQLBase();
            $mybase->connect();
            
            // try logout
            $atoken = $_SERVER['HTTP_X_API_AUTHENTICATION'];
            $r = \userprivs\logout($mybase, $atoken, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            if ($r != 0) throw new \Exception(SESSION_ERROR);

            // Succeeded
            return;
            
        }
        
    }
