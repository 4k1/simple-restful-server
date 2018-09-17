<?php

    namespace doUpdate;

    // Load module
    loadModule('userprivs');
    loadModule('mysql_eav');

    class MethodHandler {
    
        // POST /doUpdate
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
            if ($post_json["gkey"] == "") {
                error_log("[api.php] > missing gkey.");
                throw new \Exception(ILLEGAL_LOGIC);
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

            // Check required fields
            if ($post_json["gkey"] == "" || $post_json["id"] == "" || !isset($post_json["updtoken"]) ) {
                error_log("[api.php] > missing gkey, id or updtoken.");
                throw new \Exception(ILLEGAL_LOGIC);
            }
            
            // pkey
            $pkey = $pkey_sql = "";
            if (isset($post_json["parent"])) {
                $pkey = $post_json["parent"];
                $pkey_sql = " AND pkey=?";
            }

            // Get pre-data
            $sql_where = $updtoken = "";
            $pre_data = $mybase->general_fetch($post_json, $my, $sql_where, $updtoken, $post_json["gkey"], $pkey, $post_json["id"]);

            // Check UpdateToken
            if ($post_json["updtoken"] != $updtoken) {
                throw new \Exception(TOKEN_MISMATCH);                
            }

            // Start transaction
            $mybase->begin();
            
            // Make new update token
            $tm_parent = ( isset($post_json["parent"]) ) ? $post_json["parent"] : "";
            $newtoken  = $mybase->makeNewUpdtoken($my, $post_json["gkey"], $post_json["id"], isset($post_json["parent"]), $tm_parent);            
            $response["updtoken"] = $newtoken;

            // Callback - onPreQuery(Updating)
            $cbarr = array("dbi" => $my, "json" => &$post_json, "caller" => "updating", "base" => &$response);
            callbackTo("onPreQuery", $cbarr, $post_json["gkey"]);
            
            // Prepare SQL
            $sql_u = "UPDATE CHAP_DATAS SET dvalue=?, udate=? WHERE gkey=? AND rkey=? AND dkey=?" . $pkey_sql;
            $sql_i = "INSERT INTO CHAP_DATAS(gkey, rkey, pkey, dkey, dvalue, cdate, udate) VALUES (?,?,?,?,?,?,?)";
            if ($stmt = $my->prepare($sql_u)) {

                foreach($post_json["request"] as $k => $v) {

                    if ($pkey == "") {
                        $stmt->bind_param( "sssss"
                                         , $v
                                         , $response["timestamp"]
                                         , $post_json["gkey"]
                                         , $post_json["id"]
                                         , $k
                                         );
                    } else {
                        $stmt->bind_param( "ssssss"
                                         , $v
                                         , $response["timestamp"]
                                         , $post_json["gkey"]
                                         , $post_json["id"]
                                         , $k
                                         , $pkey
                                         );
                    }
                    $stmt->execute();
                    if ($stmt->error != "") {
                        error_log("[api.php] > SQL error. Update.");
                        throw new \Exception(DB_OPER_ERR);
                    }
                    if ($stmt->affected_rows == 0) {
                        
                        error_log("[api.php] > Detect column enhanced. key='" . $k . "'. Really?");
                        
                        if ($stmt_ins = $my->prepare($sql_i)) {
            
                            $stmt_ins->bind_param( "sssssss"
                                             , $post_json["gkey"]
                                             , $post_json["id"]
                                             , $pkey
                                             , $k
                                             , $v
                                             , $response["timestamp"]
                                             , $response["timestamp"]
                                             );
                            $stmt_ins->execute();
                            if ($stmt_ins->error != "") {
                                error_log("[api.php] > SQL error. Update->Insert.gkey=[" . $post_json["gkey"] . "],id=[" . $post_json["id"] . "],pkey=[" . $pkey . "],key=[" . $k . "],value=[" . $v . "],timestamp=[" . $response["timestamp"] . "]");
                                throw new \Exception(DB_OPER_ERR);
                            }
                            $stmt_ins->close();
                            
                        } 
                        
                    }

                }

                // Callback - onUpdated
                $cbarr = array("dbi" => $my, "json" => $post_json, "predata" => $pre_data, "base" => &$response);
                callbackTo("onUpdated", $cbarr, $post_json["gkey"]);

                $stmt->close();
                $mybase->commit();
                throw new \Exception(NOERR);

            } else {
                
                error_log("[doUpdate] > No Update entry.");
                
            }
            
            // Succeeded
            return;
            
        }
        
    }
