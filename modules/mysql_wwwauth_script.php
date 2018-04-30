<?php

namespace mysql_wwwauth;

global $WWWAUTH_SCRIPTS;
$WWWAUTH_SCRIPTS = [];

$WWWAUTH_SCRIPTS["CSYS_AUTHTOKENS"] = 
    "CREATE TABLE `CSYS_AUTHTOKENS` (
      `usertoken` varchar(255) DEFAULT NULL,
      `authtoken` varchar(255) DEFAULT NULL,
      `expire` varchar(255) DEFAULT NULL,
      `ua_login` varchar(255) DEFAULT NULL,
      `ua_last` varchar(255) DEFAULT NULL,
      `ip_login` varchar(255) DEFAULT NULL,
      `ip_last` varchar(255) DEFAULT NULL,
      `status` varchar(1) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$WWWAUTH_SCRIPTS["CSYS_GRANTS"] = 
    "CREATE TABLE `CSYS_GRANTS` (
      `usertoken` varchar(255) DEFAULT NULL,
      `api` varchar(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$WWWAUTH_SCRIPTS["CSYS_PAGES_GRANTS"] = 
    "CREATE TABLE `CSYS_PAGES_GRANTS` (
      `usertoken` varchar(255) NOT NULL,
      `pageid` varchar(255) NOT NULL,
      `allows` varchar(1) DEFAULT NULL,
      PRIMARY KEY (`usertoken`,`pageid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$WWWAUTH_SCRIPTS["CSYS_USERS"] = 
    "CREATE TABLE `CSYS_USERS` (
      `userid` varchar(255) DEFAULT NULL,
      `usertoken` varchar(255) DEFAULT NULL,
      `pwhash` varchar(255) DEFAULT NULL,
      `fullname` varchar(255) DEFAULT NULL,
      `privlevel` varchar(1) DEFAULT NULL,
      `tries` varchar(2) DEFAULT NULL,
      `locked` varchar(1) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
