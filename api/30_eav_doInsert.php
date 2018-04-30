<?php

    namespace doInsert;

    // Load module
    loadModule('userprivs');
    loadModule('mysql_eav');

    class MethodHandler {
    
        // POST /doInsert
        function post_config() { return ["requireAppkey" => true, "requireUserAuth" => true]; }
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

            // Make unique key
            while (1) {
                
                // Generate rkey
                if (!isset($post_json["id"]) || $post_json["id"] == "") {
                    
                    // get class
                    $json_cls = array();
                    if (isset($post_json["class"])) {
                        $json_cls = explode(' ', $post_json["class"]);
                    }
                    
                    // Generate
                    if (in_array("insert_id_numbering", $json_cls) === FALSE)
                        $rkey = substr(str_shuffle(str_repeat(C_TOKEN, 64)), 0, 64);
                    else 
                        $rkey = substr(str_shuffle(str_repeat(C_IDNMB, 64)), 0, 64);
                    
                    // Seeded
                    if (isset($post_json["idseed"])) $rkey = $post_json["idseed"] . "." . $rkey; 
                    
                } else {
                    
                    // use specified id
                    if (isCorrectID($post_json["id"])) {
                        $rkey = $post_json["id"];
                    } else {
                        error_log("[api.php] > Illegal id format.");
                        throw new \Exception(ILLEGAL_LOGIC);
                    }                    
                    
                }

                // Check exist unique key
                $sql = "SELECT COUNT(*) FROM CHAP_DATAS WHERE gkey = ? AND rkey = ?";
                if ($stmt = $my->prepare($sql)) {
                    $stmt->bind_param("ss", $post_json["gkey"], $rkey);
                    $stmt->execute();
                    $cnt = 0;
                    $stmt->bind_result($cnt);
                    if ($cnt == 0) {
                        $stmt->close();
                        break;
                    }
                    $stmt->close();
                }
            }

            // pkey
            $pkey = "";
            if (isset($post_json["parent"])) {
                $pkey = $post_json["parent"];
            }

            // Start transaction
            $mybase->begin();

            // Make new update token
            $tm_parent = ( isset($post_json["parent"]) ) ? $pkey : "";
            $newtoken  = $mybase->makeNewUpdtoken($my, $post_json["gkey"], $rkey, isset($post_json["parent"]), $tm_parent);
            $response["updtoken"] = $newtoken;

            // Callback - onPreQuery(Inserting)
            $cbarr = array("dbi" => $my, "json" => &$post_json, "caller" => "inserting", "newrkey" => $rkey, "base" => &$response);
            callbackTo("onPreQuery", $cbarr, $post_json["gkey"]);
            
            // Prepare SQL
            $sql = "INSERT INTO CHAP_DATAS(gkey, rkey, pkey, dkey, dvalue, cdate, udate) VALUES (?,?,?,?,?,?,?)";
            if ($stmt = $my->prepare($sql)) {

                foreach($post_json["request"] as $k => $v) {

                    $stmt->bind_param( "sssssss"
                                     , $post_json["gkey"]
                                     , $rkey
                                     , $pkey
                                     , $k
                                     , $v
                                     , $response["timestamp"]
                                     , $response["timestamp"]
                                     );
                    $stmt->execute();
                    if ($stmt->error != "") {
                        error_log("[api.php] > SQL error.");
                        throw new \Exception(DB_OPER_ERR);
                    }

                }

                // Callback - onInserted
                $cbarr = array("dbi" => $my, "stmt" => $stmt, "sql" => $sql, "json" => $post_json, "newrkey" => $rkey, "base" => &$response);
                callbackTo("onInserted", $cbarr, $post_json["gkey"]);
                
                $stmt->close();
                $mybase->commit();
                $response["data"] = array("id" => $rkey);

            } else {
                error_log("[api.php] > Cannot prepare SQL.");
                throw new \Exception(DB_PREPARE_ERR);
            }
            
            // Succeeded
            return;
            
        }
        
    }
