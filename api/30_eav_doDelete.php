<?php

    namespace doDelete;

    // Load module
    loadModule('userprivs');
    loadModule('mysql_eav');

    class MethodHandler {
    
        // POST /doDelete
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
            
            // Check required fields
            if ($post_json["gkey"] == "" || $post_json["id"] == "") {
                error_log("[api.php] > missing gkey or id.");
                throw new Exception(ILLEGAL_LOGIC);
            }

            // Connect to database
            $mybase = new \mysql_eav\EAV();
            $mybase->connect();
            $my = $mybase->getMySQLi();

            // Check usertoken
            $userdata = [];
            if (!\userprivs\isEffectiveSession($mybase, $_SERVER['HTTP_X_API_AUTHENTICATION'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_USER_AGENT'], $userdata)) {
                // Session error
                error_log("[extendToken] > Cannot session extend.");
                throw new \Exception(SESSION_ERROR);
            }

            // Prepare SQL
            $mybase->begin();
            $sql = "UPDATE CHAP_DATAS SET rdate=? WHERE gkey=? AND rkey=?";
            if ($stmt = $my->prepare($sql)) {

                $stmt->bind_param( "sss"
                                 , $response["timestamp"]
                                 , $post_json["gkey"]
                                 , $post_json["id"]
                                 );
                $stmt->execute();
                if ($stmt->error != "") {
                    error_log("[api.php] > SQL error.");
                    throw new \Exception(DB_OPER_ERR);
                }

                // Callback - onDeleted
                $cbarr = array("dbi" => $my, "json" => $post_json);
                callbackTo("onDeleted", $cbarr, $post_json["gkey"]);

                $stmt->close();
                $mybase->commit();
                
            } else {
                error_log("[api.php] > Cannot prepare SQL.");
                throw new \Exception(DB_PREPARE_ERR);
            }
            
            // Succeeded
            return;
            
        }
        
    }
