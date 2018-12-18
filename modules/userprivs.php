<?php

    namespace userprivs;

    // Includes
    // 1 - Defaults
    include_once(__DIR__ . '/../incl/globals.php');
    include_once(__DIR__ . '/../incl/constants.php');
    
    // 2 - Modules
    include_once(__DIR__ . '/../modules/mysql_base.php');
    
    // Logging function
    const LOGID_UP          = "USER_PRIV";
    function _log($id, $e, $v) {
        if ($e == "") $e = " ";
        error_log ("[" . $e . "] " . $id . " > " . $v . "\n");
    }

    // Check system initialized
    // false : Nobody registered
    // true  : Initialized
    /* throwable */ function isInitialized($my) {

        // Get count users        
        $sql = "SELECT COUNT(*) FROM CSYS_USERS";
        if ($stmt = $my->prepare($sql)) {
            
            // query
            $stmt->execute();
            
            // fetch
            $cnt = "";
            $stmt->bind_result($cnt);
            $stmt->fetch();
            $stmt->close();
            
            if ($cnt == "0") {
                return false;
            } else {
                return true;
            }
            
        } else {
            throw new \Exception();
        }

    }

    // User Logout
    // -9 : Server Error
    //  0 : Succeeded
    /* throwable */ function logout($mybase, $authtoken, $ip, $ua) {

        try {
            
            // Check token
            if ($authtoken == "") throw new \Exception();
            
            // insert logged in metas
            $my = $mybase->getMySQLi();
            $sql = "UPDATE CSYS_AUTHTOKENS SET ua_last = ?, ip_last = ?, status = '1' WHERE authtoken = ?";
            if ($stmt = $my->prepare($sql)) {
                $stmt->bind_param("sss", $ua, $ip, $authtoken);
                $stmt->execute();
                if ($stmt->error != "") {
                    throw new \Exception();
                }
            } else {
                throw new \Exception();
            }
            
            // done
            $stmt->close();
            return 0;

        } catch (\Exception $e) {
            return -9;
        }
        
    }

    // Extend SessionID
    // 0 : Succeeded
    // note : even if the session has already expired, it will be extended.
    /* throwable */ function extendSession($my, $authtoken, $ip, $ua) {

        try {
        
            // make date will be expired
            $expire = date("YmdHis", strtotime("+2 day"));
            
            // logged in metas
            $sql    = "UPDATE CSYS_AUTHTOKENS SET expire = ?, ua_last = ?, ip_last = ? WHERE authtoken = ?";
            if ($stmt = $my->prepare($sql)) {
                
                // query
                $stmt->bind_param("ssss", $expire, $ua, $ip, $authtoken);
                $stmt->execute();
                if ($stmt->error != "") {
                    throw new \Exception();
                }
                
            } else {
                throw new \Exception();
            }
            
            // done
            return 0;

        } catch (\Exception $e) {
            throw $e;
        }
        
    }

    // Check effectivity current session 
    // true  : effective(extended)
    // false : already expired or illegal
    /* throwable */ function isEffectiveSession($mybase, $authtoken, $ip, $ua, &$userdata) {
        
        try {

            // initialize
            $userdata = [];
            $my = $mybase->getMySQLi();

            // check token
            $sql  = "SELECT count(*) FROM CSYS_AUTHTOKENS WHERE authtoken = ? AND expire > ? AND status = '0'";
            if ($stmt = $my->prepare($sql)) {
                
                // query
                $stmt->bind_param("ss", $authtoken, date("YmdHis"));
                $stmt->execute();
                
                // fetch
                $cnt = "";
                $stmt->bind_result($cnt);
                $stmt->fetch();
                $stmt->close();
                
                if ($cnt != "0") {
                    
                    // extend current session
                    \userprivs\extendSession($my, $authtoken, $ip, $ua);
                    
                    // Get userdata
                    $sql  = "SELECT expire, ua_last, ip_last, status, userid, U.usertoken, fullname, U.privlevel FROM CSYS_USERS AS U, CSYS_AUTHTOKENS AS A WHERE A.usertoken = U.usertoken AND A.authtoken = ?";
                    if ($stmt = $my->prepare($sql)) {
                        
                        // query
                        $stmt->bind_param("s", $authtoken);
                        $stmt->execute();
                        
                        // fetch
                        $expire = $ua_last = $ip_last = $status = $userid = $usertoken = $fullname = $privlevel = "";
                        $stmt->bind_result($expire, $ua_last, $ip_last, $status, $userid, $usertoken, $fullname, $privlevel);
                        $stmt->fetch();
                        $stmt->close();
                        $userdata["expire"]     = $expire;
                        $userdata["ua_last"]    = $ua_last;
                        $userdata["ip_last"]    = $ip_last;
                        $userdata["status"]     = $status;
                        $userdata["userid"]     = $userid;
                        $userdata["usertoken"]  = $usertoken;
                        $userdata["fullname"]   = $fullname;
                        $userdata["privlevel"]  = $privlevel;
                        
                    } else {
                        \userprivs\_log(LOGID_UP, "-", "Error on prepare select. 1");
                        throw new \Exception();
                    }
                    
                    return true;
                } else {
                    return false;
                }
                
            } else {
                \userprivs\_log(LOGID_UP, "-", "Error on prepare select. 2");
                throw new \Exception();
            }

            
        } catch (\Exception $e) {
            \userprivs\_log(LOGID_UP, "-", "Exception.");
            return false;
        }
        
    }

    // Generate Auth token
    // token : generated auth token 
    /* throwable */ function generateAuthtoken($my) {
        
        // Token generator
        $retries = 0;
        while (1) {
            
            // check infinity
            if ($retries > 20) throw new \Exception();
        
            // make token
            $authtoken = substr(str_shuffle(str_repeat(C_TOKEN, 64)), 0, 64);
            
            // check tokens duplicated
            $sql  = "SELECT count(*) FROM CSYS_AUTHTOKENS WHERE authtoken = ?";
            if ($stmt = $my->prepare($sql)) {
                
                // query
                $stmt->bind_param("s", $authtoken);
                $stmt->execute();
                
                // fetch
                $cnt = "";
                $stmt->bind_result($cnt);
                $stmt->fetch();
                $stmt->close();
                
                if ($cnt == "0") {
                    return $authtoken;
                } else {
                    $retries += 1;
                }
                
            } else {
                throw new \Exception();
            }
            
        } // while
        
    }

    // User Login
    // -1 : User locked
    // -2 : Login failed
    // -9 : Server Error
    //  0 : Succeeded
    /* throwable */ function login($mybase, $id, &$pw, $ip, $ua, &$authtoken) {
        
        try {
            
            // Check ID format
            if (!isCorrectEmail($id)) return -2;
            
            // Get MySQLi instance
            $my = $mybase->getMySQLi();
            
            // pw initialize
            $pwhash = hash(config("security.hash_algo", "sha512"), $pw . config("security.pwsalt") . $id);
            
            // get user login metas
            $sql = "SELECT fullname, usertoken, tries, locked FROM CSYS_USERS WHERE userid = ?";
            $tries = $locked = $fillname = $usertoken = "";
            
            if ($stmt = $my->prepare($sql)) {
                
                // query
                $stmt->bind_param("s", $id);
                $stmt->execute();
                
                // fetch
                $stmt->bind_result($fullname, $usertoken, $tries, $locked);
                $stmt->fetch();
                $stmt->close();
                
                if ($locked != "0") {
                    error_log("Lock outed user. id = " . $id);
                    return -1;
                }
                
            } else {
                throw new \Exception();
            }
            
            // check correct login
            if (config("userprivs.auth.ldap.enable", 0) == 0) {
                // login via database
                $sql = "SELECT count(*) FROM CSYS_USERS WHERE userid = ? AND pwhash = ?";
                $loggedin = false;
                if ($stmt = $my->prepare($sql)) {
                    
                    // query
                    $stmt->bind_param("ss", $id, $pwhash);
                    $stmt->execute();
                    
                    // fetch
                    $cnt = 0;
                    $stmt->bind_result($cnt);
                    $stmt->fetch();
                    $stmt->close();
                    
                    if ($cnt != "0") {
                        $loggedin = true;
                    }
                    
                } else {
                    throw new \Exception();
                }
            } else {
                
                try {
                    // login via LDAP
                    $ld_host = config("userprivs.auth.ldap.dchost");
                    $ld_port = config("userprivs.auth.ldap.dcport", "389");
                    $ld_rdn  = config("userprivs.auth.ldap.rdn");
                    $ld_dom  = config("userprivs.auth.ldap.domain");
                    $ld_pass = $pw;
                    $ld_conn = ldap_connect($ld_host, $ld_port);
                    if (!$ld_conn) {
                        error_log("Cannot connect LDAP server on login API.");
                        throw new \Exception();
                    }
                    error_log("Connected to LDAP server.");
                    ldap_set_option($ld_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
                    ldap_set_option($ld_conn, LDAP_OPT_REFERRALS, 0);
                    
                    // make rdn
                    $ld_rdn_e = "CN=" . $id . ",CN=Users," . $ld_rdn;
                    
                    // authentication
                    $bind = ldap_bind($ld_conn, $ld_dom . "\\" . $id, $pw);
                    if (!$bind) {
                        error_log("LDAP handling error.");
                        return -2;
                    }
                    error_log("LDAP binded.");
                    
                    // Get user information
                    $ld_ui_rdn    = $ld_rdn;
                    $ld_ui_filter = "(|(sn=$id*)(givenname=$id*))";//" . $id . ")";
                    $ld_ui_attr   = array("ou","sn","givenname","displayname");
                    $ld_ui_cursor = ldap_search($ld_conn, $ld_ui_rdn, $ld_ui_filter, $ld_ui_attr);
                    $ld_ui_data   = ldap_get_entries($ld_conn, $ld_ui_cursor);
                    $fullname = $ld_ui_data[0]["displayname"][0];
                    
                    // Logged in
                    error_log("LDAP Authenticated : " . $fullname);
                    ldap_close($ld_conn);
                    $loggedin = true;

                } catch (\Exception $e) {
                    error_log("Error has occurred during performing a login sequence.");
                    throw new \Exception();
                }
                
                
            }

            // pw clear from memory
            $pw = "";

            // increment count of login tried
            if ($loggedin) {
                $tries = 0;
            } else {
                $tries += 1;
                if ($tries > 7) $locked = 1;    // Auto account lock when failed 8 times
            }
            
            // update flags
            $sql = "UPDATE CSYS_USERS SET tries = ?, locked = ?, fullname = ? WHERE userid = ?";
            if ($stmt = $my->prepare($sql)) {
                
                // query
                $stmt->bind_param("ssss", $tries, $locked, $fullname, $id);
                $stmt->execute();
                if ($stmt->error != "") {
                    throw new \Exception();
                }
                
            } else {
                throw new \Exception();
            }
            
            if ($loggedin) {
            
                // make date will be expired
                $expire = date("YmdHis", strtotime("+2 day"));
                
                // insert logged in metas
                $authtoken  = generateAuthtoken($my);
                $sql = "INSERT INTO CSYS_AUTHTOKENS VALUES (?,?,?,?,?,?,?,'0')";
                if ($stmt = $my->prepare($sql)) {
                    $stmt->bind_param("sssssss", $usertoken, $authtoken, $expire, $ua, $ua, $ip, $ip);
                    $stmt->execute();
                    if ($stmt->error != "") {
                        throw new \Exception();
                    }
                } else {
                    throw new \Exception();
                }
            
            }
            
            // done
            $stmt->close();
            if ($loggedin) return 0;
            return -2;

        } catch (\Exception $e) {
            error_log($e->getMessage());
            return -9;
        }
        
    }
    
    // Get User Privileges
    // -1 : Unknown authtoken
    // -9 : Server Error
    //  0 : Succeeded
    function getPrivs($authtoken, &$privlevel, &$privs) {
        
        try {
            
            // Connect database
            $my_obj = new \mysql_base\MySQLBase();
            $my_obj->connect();
            
            // Get DBi
            $my = $my_obj->getMySQLi();
            
            // get from CSYS_USERS
            $usertoken = "";
            $sql = "SELECT U.usertoken, privlevel FROM CSYS_USERS AS U, CSYS_AUTHTOKENS AS A WHERE U.usertoken = A.usertoken AND A.authtoken = ?";
            if ($stmt = $my->prepare($sql)) {

                // query
                $stmt->bind_param("s", $authtoken);
                $stmt->execute();
                $stmt->store_result();
                
                // check result
                if ($stmt->num_rows != 1) {
                    return -1;
                }
                
                // fetch
                $stmt->bind_result($usertoken, $privlevel);
                $stmt->fetch();
                $stmt->close();
                
            } else {
                \userprivs\_log(LOGID_UP, "-", "Error on prepare of select. 1");
                return -9;
            }
            if ($privlevel == "1") return 0;
            
            // get from CSYS_GRANTS
            $sql = "SELECT api FROM CSYS_GRANTS WHERE usertoken = ? OR usertoken = '*'";
            if ($stmt = $my->prepare($sql)) {

                // query
                $stmt->bind_param("s", $usertoken);
                $stmt->execute();
                $stmt->store_result();
                
                // fetch
                $api = "";
                $privs = [];
                $stmt->bind_result($api);
                while ($stmt->fetch()) {
                    array_push($privs, $api);
                }
                $stmt->close();
                
            } else {
                \userprivs\_log(LOGID_UP, "-", "Error on prepare of select. 2");
                return -9;
            }
               
        } catch (\Exception $e) {
            return -9;
        }

    }
    
    // Get All Users List
    // -9 : Server Error
    //  0 : Succeeded
    function getUserList($mybase, &$users) {
        
        try {
            
            // Get MySQLi instance
            $my = $mybase->getMySQLi();
            
            // get from CSYS_GRANTS
            $sql = "SELECT userid, usertoken, fullname, privlevel, locked FROM CSYS_USERS";
            if ($stmt = $my->prepare($sql)) {

                // query
                $stmt->execute();
                $stmt->store_result();
                
                // fetch
                $userid = $usertoken = $fullname = $privlevel = $locked = "";
                $users = [];
                $stmt->bind_result($userid, $usertoken, $fullname, $privlevel, $locked);
                while ($stmt->fetch()) {
                    $row = [];
                    $row['userid']    = $userid;
                    $row['usertoken'] = $usertoken;
                    $row['fullname']  = $fullname;
                    $row['privlevel'] = $privlevel;
                    $row['locked']    = $locked;
                    array_push($users, $row);
                }
                $stmt->close();
                
                // finalize
                return 0;
                
            } else {
                \userprivs\_log(LOGID_UP, "-", "Error on prepare of select. 1");
                return -9;
            }
               
        } catch (\Exception $e) {
            return -9;
        }
        
    }
    
    // Register New User
    // -1 : Duplicated
    // -2 : No more generate token
    // -9 : Server Error
    //  0 : Succeeded
    /* throwable */ function addNewUser($mybody, $id, &$pw, $fullname, $privlevel, &$usertoken, &$authtoken) {
        
        try {
            
            // pw initialize
            $pwhash = hash(config("security.hash_algo", "sha512"), $pw . config("security.pwsalt") . $id);
            $pw = "";

            // Start transaction
            $mybody->begin();
            $my = $mybody->getMySQLi();
            
            // check duplicated
            $sql = "SELECT count(*) FROM CSYS_USERS WHERE userid = ?";
            if ($stmt = $my->prepare($sql)) {
                
                // query
                $stmt->bind_param("s", $id);
                $stmt->execute();
                
                // fetch
                $cnt = "";
                $stmt->bind_result($cnt);
                $stmt->fetch();
                $stmt->close();
                
                if ($cnt != "0") {
                    \userprivs\_log(LOGID_UP, "-", "User " . $id . " is already exists.");
                    return -1;
                }
                
            } else {
                \userprivs\_log(LOGID_UP, "-", "Error on prepare of select. 1");
                throw new \Exception();
            }
            
            // Tokens generator
            $retries = 0;
            while (1) {
                
                // check infinity
                if ($retries > 20) return -2;
            
                // make token
                $usertoken = substr(str_shuffle(str_repeat(C_TOKEN, 20)), 0, 20);
                $authtoken = substr(str_shuffle(str_repeat(C_TOKEN, 64)), 0, 64);
                
                // check tokens duplicated
                $sql  = "SELECT VA+VB FROM";
                $sql .= "    ( SELECT count(*) AS VA FROM CSYS_USERS      WHERE usertoken = ? ) AS A";
                $sql .= "  , ( SELECT count(*) AS VB FROM CSYS_AUTHTOKENS WHERE authtoken = ? ) AS B";
                if ($stmt = $my->prepare($sql)) {
                    
                    // query
                    $stmt->bind_param("ss", $usertoken, $authtoken);
                    $stmt->execute();
                    
                    // fetch
                    $cnt = "";
                    $stmt->bind_result($cnt);
                    $stmt->fetch();
                    $stmt->close();
                    
                    if ($cnt == "0") {
                        break;
                    } else {
                        $retries += 1;
                    }
                    
                } else {
                    \userprivs\_log(LOGID_UP, "-", "Error on prepare of select. 2");
                    throw new \Exception();
                }
                
            } // while 
            
            // insert new record
            $sql = "INSERT INTO CSYS_USERS(userid, usertoken, pwhash, fullname, privlevel, tries, locked) VALUES (?,?,?,?,?,0,0)";
            if ($stmt = $my->prepare($sql)) {
                $stmt->bind_param("sssss", $id, $usertoken, $pwhash, $fullname, $privlevel);
                $stmt->execute();
                if ($stmt->error != "") {
                    throw new \Exception();
                    \userprivs\_log(LOGID_UP, "-", "Error on execute of insert. 3");
                }
            } else {
                \userprivs\_log(LOGID_UP, "-", "Error on prepare of insert. 4");
                throw new \Exception();
            }
            
            // done
            $stmt->close();
            return 0;
                        
        } catch (\Exception $e) {
            return -9;
        }
        
    }
