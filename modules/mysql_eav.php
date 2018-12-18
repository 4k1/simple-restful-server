<?php

    namespace mysql_eav;

    // Includes
    // 1 - Defaults
    include_once(__DIR__ . '/../incl/globals.php');
    include_once(__DIR__ . '/../incl/constants.php');
    
    // 2 - Modules
    include_once(__DIR__ . '/mysql_base.php');
    
    // 3 - Database Scripts
    include_once(__DIR__ . '/mysql_eav_script.php');
    
    class EAV extends \mysql_base\MySQLBase {
        
        function initialize() {
            
            // Check database opened
            if (!$this->$connected_) throw new \Exception(INTERNAL);
            
            // Use EAV scripts
            global $EAV_SCRIPTS;
            
            // Check already initialized
            $sql = "SHOW TABLES;";
            if ($stmt = $this->$my_->prepare($sql)) {
                
                // Execute
                $stmt->execute();
                $stmt->store_result();
                
                // Init tablename val
                $name = "";
                $stmt->bind_result($name);
                
                // Check table exists
                while($stmt->fetch()) {
                    if (array_key_exists($name, $EAV_SCRIPTS)) {
                        error_log("[mysql_eav::initialize] > System already initialized. (Table " . $name. " exists.)");
                        throw new \Exception(INTERNAL);
                    }
                }
                
            }
            
            // Create tables
            foreach ($EAV_SCRIPTS as $key => $tbl) {
                if ($stmt = $this->$my_->prepare($tbl)) {
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
        }
                
        // Function to make a new update token
        /* throwable */ function makeNewUpdtoken($my, $gkey, $id, $isset_parent, $parent) {
        
            // Check params
            if (!isCorrectID($gkey) || !isCorrectID($id) || !isCorrectID($parent)) throw new \Exception(INTERNAL);
        
            // Delete old UpdateToken
            $sql_ot = "DELETE FROM CHAP_UPDTOKENS WHERE gkey = ? AND rkey = ?";
            if ($isset_parent) $sql_ot .= " AND pkey = ?";
            
            if ($stmt = $my->prepare($sql_ot)) {
            
                if ($isset_parent) {
                    $stmt->bind_param( "sss"
                                     , $gkey
                                     , $id
                                     , $parent
                                     );
                } else {
                    $stmt->bind_param( "ss"
                                     , $gkey
                                     , $id
                                     );
                }
                $stmt->execute();
                if ($stmt->error != "") {
                    error_log("[api.php] > SQL error. Delete for CHAP_UPDTOKENS.");
                    throw new \Exception(DB_OPER_ERR);
                }
                $stmt->close();
                
            } else {
                error_log("[api.php] > Cannot prepare SQL to DELETE for CHAP_UPDTOKENS. " . $my->error);
                throw new \Exception(DB_PREPARE_ERR);
            }
            
            // Insert new UpdateToken
            $nvl = "null";
            $newtoken = substr(str_shuffle(str_repeat(C_TOKEN, 64)), 0, 64);
            $sql_tk = "INSERT CHAP_UPDTOKENS VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt = $my->prepare($sql_tk)) {
            
                $stmt->bind_param( "ssssss"
                                 , $gkey
                                 , $id
                                 , $parent
                                 , $newtoken
                                 , $nvl
                                 , date("YmdHisO")
                                 );
                $stmt->execute();
                if ($stmt->error != "") {
                    error_log("[api.php] > SQL error. Insert for CHAP_UPDTOKENS.");
                    throw new \Exception(DB_OPER_ERR);
                }
                $stmt->close();
                return $newtoken;
                
            } else {
                error_log("[api.php] > Cannot prepare SQL to INSERT for CHAP_UPDTOKENS.");
                throw new \Exception(DB_PREPARE_ERR);
            }
    
        }

            
        // General fetch function
        /* throwable */ function general_fetch($post_json, $my, $append_sql, &$updtoken, $gkey, $parent = "", $id = "", $override_sql = "") {
    
            // Check params
            if (!isCorrectID($updtoken) || !isCorrectID($gkey) || !isCorrectID($parent) || !isCorrectID($id)) throw new Exception(INTERNAL);
    
            // Judge key pattern
            $keycnt = 0;
            $kv1 = $kv2 = "";
            if ($parent == "" && $id == "") {
                $sql_key = "AND gkey = ? ";
            } elseif ($parent == "") {
                $sql_key = "AND gkey = ? AND rkey = ? ";
                $kv1 = $id;
                $keycnt = 1;
            } elseif ($id == "") {
                $sql_key = "AND gkey = ? AND pkey = ? ";
                $kv1 = $parent;
                $keycnt = 1;
            } else {
                $sql_key = "AND gkey = ? AND pkey = ? AND rkey = ? ";
                $kv1 = $parent;
                $kv2 = $id;
                $keycnt = 2;
            }
    
            // make transaction data for callback
            $transaction_data = [];
            
            // Callback - onBeforeSelect
            $cbarr = array("dbi" => $my, "json" => $post_json, "transaction_data" => $transaction_data);
            callbackTo("onBeforeSelect", $cbarr, $post_json["gkey"]);
            $transaction_data = $cbarr["transaction_data"];
    
            // Make SQL
            $sql_col = "SELECT dataid, rkey, pkey, dkey, dvalue";
            $sql_frm = "FROM CHAP_DATAS";
            $sql_whr = "WHERE delf = 0 AND rdate IS NULL " . $sql_key . $append_sql;
            $sql_odr = "ORDER BY rkey desc, dkey";
            $sql_etc = "";
            if ($override_sql != "") {
                $sql_whr = "";
                $sql_odr = "";
                $sql_etc = $override_sql;
            }

            
            // Callback - onBeforeSelectSQL
            $cbarr = array("dbi" => $my, "json" => $post_json, "columns" => $sql_col, "from" => $sql_frm, "where" => $sql_whr, "order" => $sql_odr, "etc" => $sql_etc);
            callbackTo("onConstractSelectSQL", $cbarr, $post_json["gkey"]);
            $sql = $cbarr["columns"] . " " . $cbarr["from"] . " " . $cbarr["where"] . " " . $cbarr["order"] . " " . $cbarr["etc"];
    
            // Do SELECT
            if ($stmt = $my->prepare($sql)) {
    
                // Bind query
                if ($keycnt == 0) {
                    $stmt->bind_param("s", $gkey);
                } elseif ($keycnt == 1) {
                    $stmt->bind_param("ss", $gkey, $kv1);
                } else {
                    $stmt->bind_param("sss", $gkey, $kv1, $kv2);
                }
                
                // Execute SQL
                $stmt->execute();
                $stmt->store_result();
                
                // Make onBreak function
                $fx_onBreak = null;
                if ($fx_onBreak == null) {
                    $fx_onBreak = function(&$rows, &$tmp) {
                        if ($rows == null) $rows = array();
                        array_push($rows, $tmp);
                        $tmp = [];
                    };
                }
                
                // Init fetch buffer
                $dataid = $rkey = $pkey = $dkey = $dvalue = "";
                $rows_all = array();
                $current_key = "";
                $tmp = array();
    
                // Bind result
                $stmt->bind_result($dataid, $rkey, $pkey, $dkey, $dvalue);
    
                // Fetch
                while ($stmt->fetch()) {
                    if ($current_key != "" && $current_key != $rkey) {
                        
                        // Callback - onRowFetched
                        $skip = false;
                        $cbarr = array("dbi" => $my, "json" => $post_json, "transaction_data" => $transaction_data, "row" => $tmp, "skip" => &$skip);
                        callbackTo("onRowFetched", $cbarr, $post_json["gkey"]);
                        $tmp = $cbarr["row"];
                        
                        if ($skip) $tmp = []; else $fx_onBreak($rows_all, $tmp);
                    }
                    $tmp["id"]      = $rkey;
                    $tmp["parent"]  = $pkey;
                    $tmp[$dkey]     = $dvalue;
                    $current_key    = $rkey;
                }
                if ($current_key != "") {
                    
                    // Callback - onRowFetched
                    $skip = false;
                    $cbarr = array("dbi" => $my, "json" => $post_json, "transaction_data" => $transaction_data, "row" => $tmp, "skip" => &$skip);
                    callbackTo("onRowFetched", $cbarr, $post_json["gkey"]);
                    $tmp = $cbarr["row"];
                    
                    if ($skip) $tmp = []; else $fx_onBreak($rows_all, $tmp);
                }
    
                // Close
                $stmt->close();
    
            } else {
                error_log("[api.php] > Cannot prepare SQL.");
                throw new Exception(DB_PREPARE_ERR);
            }
            
            // Get current tuple token to update itself
            $sql = "SELECT token FROM CHAP_UPDTOKENS WHERE 1 = 1 " . $sql_key;
            if ($stmt = $my->prepare($sql)) {
                
                // Bind query
                if ($keycnt == 0) {
                    $stmt->bind_param("s", $gkey);
                } elseif ($keycnt == 1) {
                    $stmt->bind_param("ss", $gkey, $kv1);
                } else {
                    $stmt->bind_param("sss", $gkey, $kv1, $kv2);
                }
                
                // Execute SQL
                $stmt->execute();
                $stmt->store_result();
                
                // Bind result
                $stmt->bind_result($updtoken);
                if (!$stmt->fetch()) {
                    $updtoken = "INITIALTOKEN";
                }
                
                // Close
                $stmt->close();
    
            } else {
                error_log("[api.php] > Cannot prepare SQL (CHAP_UPDTOKENS).");
                throw new Exception(DB_PREPARE_ERR);
            }
            
            // Callback - onAllDataFetched
            $cbarr = array("dbi" => $my, "json" => $post_json, "data" => $rows_all);
            callbackTo("onAllDataFetched", $cbarr, $post_json["gkey"]);
            $rows_all = $cbarr["data"];
            
            // Finalize
            return $rows_all;
            
        }

                
    };