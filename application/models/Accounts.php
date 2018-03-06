<?php

class Application_Model_Accounts extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'accounts';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_Accounts();
        return self::$_instance;
    }

    public function insertAccount() {

        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            try {
                $responseId = $this->insert($data);
            } catch (Exception $e) {
				
                return $e->getMessage();
            }

            if ($responseId) {
                return $responseId;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }
}