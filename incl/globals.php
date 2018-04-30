<?php

    // Includes
    include_once(__DIR__ . '/../incl/siteconf.php');
    include_once(__DIR__ . '/../incl/constants.php');
        
    // Configuration Loader
    /* throwable */ function config($k, $df="")
    {
        global $_APICONF;
        $v = $_APICONF[$k];
        if ($v == "") {
            if ($df === null) throw new Exception(MISSING_SYSENV);
            return $df;
        } else {
            return $v;
        }
    }

    // General loader
    /* throwable */ function loadModule($name)
    {
        include_once(__DIR__ . '/../modules/' . $name . '.php');
    }

    // Init timezone
    date_default_timezone_set(config("behavior.timezone", "Asia/Tokyo"));

    // ID check function
    function isCorrectID($var) {
        $var = trim($var);
        if (strlen($var) == 0) {
            return true;
        } elseif (preg_match("/^[a-zA-Z0-9._]+$/", $var)) {
            return true;
        } else {
            return false;
        }
    }