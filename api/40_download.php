<?php

    namespace download;

    // Load module
    loadModule('userprivs');
    loadModule('mysql_base');

    class MethodHandler {
    
        // GET /download/foo
        function get_config() { return ["requireAppkey" => true, "requireUserAuth" => true]; }
        function get_privkey($api, $request, $privkey) { 
            // privkey = priv.api.download or priv.api.download::<filename>
            return array($privkey, $privkey . "::" . $api[1]);
        }
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
            
            // Check configured
            $f_dir = config("upload.dir", null);
            if ($f_dir == null) {
                error_log("[upload] > upload.dir does not configured.");
                throw new \Exception(ILLEGAL_LOGIC);
            }
            if (substr($f_dir, -1) != "/") $f_dir .= "/";
            
            // Check ext whitelist
            $previewable = false;
            $ext_ct = "";
            $ext_wl = array("jpg", "jpe", "jpeg", "gif", "bmp", "png", "ico", "txt", "pdf", "json", "xml", "swf", "flv", "svg", "svgz", "mp3");
            $ext = strtolower(substr($api[1], strrpos($api[1], '.') + 1));
            if (in_array($ext, $ext_wl)) {
                $previewable = true;
                $ext_ct = mime_content_type($f_dir . $api[1]);
            }
            
            // Readfile
            if ($_GET["preview"] == "1" && $previewable) {
                header("Content-Type: " . $ext_ct);
            } else {
                header("Content-Type: application/force-download");
                header("Content-disposition: attachment; filename=\"" . $api[1] . "\"");
            }
            readfile($f_dir . $api[1]);
            
            // No json response
            $response = null;
            
            // Succeeded
            return;
            
        }
        
    }
