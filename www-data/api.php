<?php

    // Includes
    // 1 - Defaults
    include_once(__DIR__ . '/../incl/globals.php');
    include_once(__DIR__ . '/../incl/constants.php');
    
    // 2 - APIs
    foreach(new GlobIterator(__DIR__ . '/../api/*.php') as $file) {
        if (is_readable($file)) {
            require $file;
        }
    }
    
    // 3 - Hooks
    include_once(__DIR__ . '/../hooks/global.php');
    spl_autoload_register(function ($name) {
        $file = __DIR__ . '/../hooks/px_' . $name . '_handler.php';
        if (is_readable($file)) {
            require_once $file;
        }
    });
    
    class jsonBody {
        
        private $raw_ = "";
        private $err_ = true;
        private $bdy_ = "";
        
        function __construct($p_json) {
            
            // Get posted json
            if ($p_json === null || strlen($p_json) == 0) {
                error_log("::jsonBody > No contents.");
                return;
            }
            $post_json = json_decode($p_json, true);
            if ($post_json === null) {
                error_log("::jsonBody > json decode error(post body).");
                return;
            }
            
            // Succeeded
            $this->$err_ = false;
            $this->$raw_ = $p_json;
            $this->$bdy_ = $post_json;
            
        }
        
        function getArray() {
            return $this->$bdy_;
        }
        
    }

    // Callback function for hooks
    /* throwable */ function callbackTo($fx, &$params, $service_id = "") {
        // Check params
        if (!isCorrectID($fx)) throw new Exception(PLUGIN_ERR);
        
        // Callback
        if ($service_id == "") {
            if(function_exists($fx)) {
                try {
                    $fx($params);
                } catch (Exception $e) {
                    error_log("[api.php] > Callback " . $fx . " raised an exception.");
                    throw new Exception(PLUGIN_ERR);
                }
            }
        } else {
            if(class_exists($service_id)) {
                try {
                    $obj = new $service_id;
                    if (method_exists($obj, $fx)) {
                        $obj->$fx($params);
                    }
                } catch (Exception $e) {
                    error_log("[api.php] > Callback " . $service_id . "->" . $fx . " raised an exception.");
                    throw new Exception(PLUGIN_ERR);
                }
            }
        }
    }
    
    // Initialize HTTP response
    header("Content-Type: application/json; charset=utf-8");
    
    // result json skelton
    $jary_base_response = array( "status"       => ILLEGAL_LOGIC
                                ,"description"  => $errs[ILLEGAL_LOGIC]
                                ,"data"         => ""
                                ,"timestamp"    => date("YmdHisO")
                                ,"token"        => substr(str_shuffle(str_repeat(C_TOKEN, 64)), 0, 64)
                                );

    // Start 
    try {
        
        // Check URL
        if (!preg_match("/^[a-zA-Z0-9\/_.]+$/", $_SERVER['REQUEST_URI'])) {
            error_log("[api.php] > URL Error.");
            throw new Exception(SECURITY_ERROR);
        }
        
        // Check Method
        $p_m = $_SERVER['REQUEST_METHOD'];
        if ($p_m != "GET" && $p_m != "POST" && $p_m != "PUT" && $p_m != "DELETE") {
            error_log("[api.php] > Illegal method call.");
            throw new Exception(SECURITY_ERROR);
        }            
        
        // Parse RESTful API
        $p_fullp        = substr($_SERVER['REQUEST_URI'], 1);
        $p_all_paths    = explode('/', $p_fullp);
        $p_paths        = $p_all_paths;
    
        // Callback - onStart
        $cbarr = array();
        callbackTo('onStart', $cbarr);

        // Get raw request
        $raw_request = file_get_contents('php://input');

        // Call handler
        $fx_class_recursive_resolve = function($p_paths, $raw_request, &$jary_base_response) use ($p_all_paths, &$fx_class_recursive_resolve) {
            
            try {
                
                // Get method
                $http_method = strtolower($_SERVER['REQUEST_METHOD']);
                
                // Create handler
                $api_class_id = str_replace("/", "\\", $p_paths) . "\\MethodHandler";
                // Create instance (ex. GET /ping ... ping\MethodHandler)
                $api_class = new $api_class_id;
                
                // Get API configure
                $config_method = $http_method . "_config";
                $api_config = $api_class::$config_method();
                
                // Check apikey
                if ($api_config["requireAppkey"]) {
                    $api_appkey = $_SERVER['HTTP_X_API_APPKEY'];
                    //$api_appkey = config("service.apikey", null); // debug
                    if (config("service.apikey", null) != $api_appkey) {
                        error_log("[api.php] > apikey is wrong.");
                        throw new Exception(SECURITY_ERROR);
                    }
                }
                
                // Check userauth
                $atoken = "";
                if ($api_config["requireUserAuth"]) {
                    // Get atoken
                    if (!isset($_SERVER['HTTP_X_API_AUTHENTICATION'])) {
                        error_log("[api.php] > X-API-Authentication was required.");
                        throw new Exception(SESSION_ERROR);
                    }
                    $atoken = substr($_SERVER['HTTP_X_API_AUTHENTICATION'], 0, 255);
                    
                    // Get user privs
                    $privlevel = "";
                    $privs     = [];
                    if (\userprivs\getPrivs($atoken, $privlevel, $privs) != 0) {
                        error_log("[api.php] > Current session was expired.");
                        throw new Exception(SESSION_ERROR);
                    }
                }
                
                // Call function (ex. GET /ping ... ping\MethodHandler::get() )
                $api_class::$http_method($p_all_paths, $raw_request, $jary_base_response);
                if ($jary_base_response == null) throw new Exception(NOERR_WOR);
                throw new Exception(NOERR);
                            
            } catch (Exception $e) {
                // Logical exception
                throw $e;
                
            } catch (Error $e) {
                
                // logging
                //error_log("[api.php] > API error. [" . $api_class_id . "] > " . $e);
                
                // Declease class path
                $lpos = strrpos($p_paths, "/");
                if ($lpos === FALSE) {
                    // Logical error
                    throw new Exception(ILLEGAL_LOGIC);
                } else {
                    $next_class_name = substr($p_paths, 0, $lpos);
                    $fx_class_recursive_resolve($next_class_name, $raw_request, $jary_base_response);
                }
                
            }
            
        };
        $fx_class_recursive_resolve($p_fullp, $raw_request, $jary_base_response);
        
    } catch (Exception $e) {
        if ($errs[$e->getMessage()] != null) {
            $jary_base_response["status"] = $e->getMessage();
            $jary_base_response["description"] = $errs[$e->getMessage()];
        }
    }

    // Output response
    if ($jary_base_response != null) print(json_encode($jary_base_response));
