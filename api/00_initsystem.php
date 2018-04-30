<?php

    namespace initSystem;

    // Includes
    include_once(__DIR__ . '/../incl/siteconf.php');

    // Load MySQL module
    loadModule('userprivs');
    loadModule('mysql_wwwauth');
    loadModule('mysql_eav');
    
    class MethodHandler {
    
        // POST /initSystem
        function post_config() { return ["requireAppkey" => true, "requireUserAuth" => false]; }
        function post($api, $request, &$response) {
            
            // Get request json
            $obj_json = new \jsonBody($request);
            $post_json = $obj_json->getArray();
            
            // Check new user parameters
            if ($post_json["initID"] == "" || $post_json["initPW"] == "" || $post_json["fullname"] == "") {
                error_log("[initSystem::post] > missing initID or initPW.");
                throw new \Exception(ILLEGAL_LOGIC);
            }
            
            // Create WWW Auth tables
            $my = new \mysql_wwwauth\WWWAuth();
            $my->connect();
            $my->initialize();
            
            // Add administrator
            $new_utoken = $new_atoken = "";
            $r = \userprivs\addNewUser($my, $post_json["initID"], $post_json["initPW"], $post_json["fullname"], "0" /* firstuser will be owner */, $new_utoken, $new_atoken);
            if ($r != 0) throw new Exception(ILLEGAL_LOGIC);
            
            // Create EAV model tables
            if (config("database.useeavs", false)) {
                
                // Create instance of mysql_eav
                $my = new \mysql_eav\EAV();
                $my->connect();
                $my->initialize();
                
            }
            
            // Succeeded
            return;
            
        }
        
    }
