<?php

    namespace mysql_base;

    // Includes
    // 1 - Defaults
    include_once(__DIR__ . '/../incl/globals.php');
    include_once(__DIR__ . '/../incl/constants.php');
    
    class MySQLBase {
        
        private $connected_ = false;
        private $intrans_   = false;
        private $my_        = null;
        
        function __construct() {
            
            // nop
            
        }
        
        function __destruct() {
            
            // Transaction rollback
            $this->rollback(false);
            
            return;
            
        }
        
        function connect() {
            
            // Connect database
            try {
                $my = new \mysqli( config("database.target",   null)
                                 , config("database.user",     null)
                                 , config("database.password", null)
                                 , config("database.name",     null)
                                 );
            } catch (\Exception $e) {
                error_log("[mysql_base] > Cannot connect to database.");
                throw new \Exception(DATABASE_ERR);
            }
            
            // Check logical connection error
            if ($my->connect_error) {
                error_log("[mysql_base] > Cannot connect to database.(Logical)");
                throw new \Exception(DATABASE_ERR);
            }
            $my->set_charset("utf8");

            // Conencted
            $this->$connected_ = true;
            $this->$my_        = $my;
                        
            return;
            
        }
        
        function getMySQLi() { return $this->$my_; }
        
        function disconnect() {
            
            // Disconnect database
            if (!$this->$connected_) return;
            $this->$my_->close();
            
            // Initialize
            $this->$my_         = null;
            $this->$connected_  = false;
            
            return;
            
        }
        
        function begin() {
            
            // Check connection
            if (!$this->$connected_) {
                error_log("[mysql_base] > Transaction couldn't start.");
                throw new \Exception(INTERNAL);
            }
            
            // Check transaction
            if ($this->$intrans_) return;
            
            // Start transaction
            //$this->$my_->begin_transaction();
            $this->$my_->autocommit(FALSE);
            $this->$intrans_ = true;
            
            return;
            
        }
        
        function commit() {
            
            // Check connection
            if (!$this->$connected_ || !$this->$intrans_) {
                error_log("[mysql_base] > Transaction couldn't commit.");
                throw new \Exception(INTERNAL);
            }
            
            // Commit transaction
            $this->$my_->commit();
            $this->$intrans_ = false;
            print_r($this->$my_);
            //$this->$my_->autocommit(TRUE);
            
            return;
            
        }

        function rollback($throw = true) {
            
            // Check connection
            if (!$this->$intrans_) {
                $this->$intrans_ = false;
                if ($throw) {
                    error_log("[mysql_base] > Transaction couldn't rollback.");
                    throw new \Exception(INTERNAL);
                }
                return;
            }
            
            // Commit transaction
            $this->$my_->rollback();
            //$this->$my_->autocommit(TRUE);
            
            return;
            
        }
                
    };