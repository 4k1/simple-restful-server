<?php

    // Init error constants table
    $errs = [];
    const C_IDNMB           = '0123456789';
    const C_TOKEN           = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const NOERR             =  0; $errs[NOERR]               = "Succeeded.";
    const NOERR_WOR         =  1; $errs[NOERR_WOR]           = null;
    const ILLEGAL_LOGIC     = 11; $errs[ILLEGAL_LOGIC]       = "Illegal API call.";
    const MISSING_SYSENV    = 12; $errs[MISSING_SYSENV]      = "Missing system configurations.";
    const CANT_READ         = 13; $errs[CANT_READ]           = "Cannot understand your data.";
    const DATABASE_ERR      = 14; $errs[DATABASE_ERR]        = "Failed to connect database.";
    const DB_PREPARE_ERR    = 15; $errs[DB_PREPARE_ERR]      = "Illegal SQL.";
    const DB_OPER_ERR       = 16; $errs[DB_OPER_ERR]         = "DML Error.";
    const TOKEN_MISMATCH    = 17; $errs[TOKEN_MISMATCH]      = "Update token mismatch. This data has been already updated by another operator.";
    const LOGIN_ERROR       = 18; $errs[LOGIN_ERROR]         = "Login failed.";
    const SESSION_ERROR     = 19; $errs[SESSION_ERROR]       = "Cannot update current session status.";
    const ACCESS_DENIED     = 20; $errs[ACCESS_DENIED]       = "Access denied.";
    const INTERNAL          = 96; $errs[INTERNAL]            = "Internal error.";
    const PLUGIN_ERR        = 97; $errs[PLUGIN_ERR]          = "Plugin returns an error.";
    const SECURITY_ERROR    = 99; $errs[SECURITY_ERROR]      = "huh?";

    // Bundled api privilege keys for require userauth APIs
    $bundled_privkeys = [
        'priv.logout.get',
        'priv.privileges.extendtoken.get',
    ];
    
