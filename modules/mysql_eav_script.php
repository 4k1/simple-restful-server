<?php

namespace mysql_eav;

global $EAV_SCRIPTS;
$EAV_SCRIPTS = [];

$EAV_SCRIPTS["CHAP_DATAS"] = 
    "CREATE TABLE `CHAP_DATAS` (
      `dataid` int(8) NOT NULL AUTO_INCREMENT,
      `delf` int(1) NOT NULL DEFAULT '0',
      `gkey` varchar(255) NOT NULL,
      `rkey` varchar(255) NOT NULL,
      `pkey` varchar(255) DEFAULT NULL,
      `dkey` varchar(255) NOT NULL,
      `dvalue` mediumtext NOT NULL,
      `owner` varchar(255) DEFAULT NULL,
      `cdate` varchar(255) DEFAULT NULL,
      `udate` varchar(255) DEFAULT NULL,
      `rdate` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`dataid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=20806 DEFAULT CHARSET=utf8";

$EAV_SCRIPTS["CHAP_UPDTOKENS"] = 
    "CREATE TABLE `CHAP_UPDTOKENS` (
      `gkey` varchar(255) NOT NULL,
      `rkey` varchar(255) NOT NULL,
      `pkey` varchar(255) DEFAULT NULL,
      `token` varchar(255) DEFAULT NULL,
      `lastuuser` varchar(255) DEFAULT NULL,
      `lastudate` varchar(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$EAV_SCRIPTS["CSYS_SEQUENCES"] = 
    "CREATE TABLE `CSYS_SEQUENCES` (
      `seqid` varchar(255) NOT NULL,
      `seqval` bigint(20) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`seqid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

