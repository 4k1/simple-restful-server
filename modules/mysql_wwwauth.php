<?php

    namespace mysql_wwwauth;

    // Includes
    // 1 - Defaults
    include_once(__DIR__ . '/../incl/globals.php');
    include_once(__DIR__ . '/../incl/constants.php');
    
    // 2 - Modules
    include_once(__DIR__ . '/mysql_base.php');
    
    // 3 - Database Scripts
    include_once(__DIR__ . '/mysql_wwwauth_script.php');
    
    class WWWAuth extends \mysql_base\MySQLBase {
        
        function initialize() {
            
            // Check database opened
            if (!$this->$connected_) throw new \Exception(INTERNAL);
            
            // Use EAV scripts
            global $WWWAUTH_SCRIPTS;
            
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
                    if (array_key_exists($name, $WWWAUTH_SCRIPTS)) {
                        error_log("[mysql_wwwauth::initialize] > System already initialized. (Table " . $name. " exists.)");
                        throw new \Exception(INTERNAL);
                    }
                }
                
            }
            
            // Create tables
            foreach ($WWWAUTH_SCRIPTS as $key => $tbl) {
                if ($stmt = $this->$my_->prepare($tbl)) {
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
        }
                
    };