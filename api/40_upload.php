<?php

    namespace upload;

    // Load module
    loadModule('userprivs');
    loadModule('mysql_base');

    class MethodHandler {
    
        // POST /upload
        function post_config() { return ["requireAppkey" => true, "requireUserAuth" => true]; }
        function post($api, $request, &$response) {
            
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
            
            // Check configured
            $f_dir = config("upload.dir", null);
            if ($f_dir == null) {
                error_log("[upload] > upload.dir does not configured.");
                throw new \Exception(ILLEGAL_LOGIC);
            }
            if (substr($f_dir, -1) != "/") $f_dir .= "/";

            // Save file
            $f_name = "";
            if (is_uploaded_file($_FILES["file"]["tmp_name"])) {
                
                // Parse filename
                list($file_name, $file_type) = explode(".", $_FILES['file']['name']);
                
                // Make filename
                $f_name = date("YmdHis") . "_" . substr(str_shuffle(str_repeat(C_TOKEN, 64)), 0, 64) . "." . $file_type;
                
                // Store
                if (move_uploaded_file($_FILES['file']['tmp_name'], $f_dir . $f_name )) {
                    chmod($f_dir . $f_name, 0644);
                } else {
                    error_log("[upload] > Error has occurred with move_uploaded_file.");
                    throw new \Exception(ILLEGAL_LOGIC);
                }
                
            }
            
            // Return filename
            $response["saved_filename"] = $f_name;
            
            // Succeeded
            return;
            
        }
        
    }
