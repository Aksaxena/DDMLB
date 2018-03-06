<?php

class Application_Model_UsersDeposit extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'users_deposit';

    private function __clone() {
        
    }

	//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_UsersDeposit();
        return self::$_instance;
    }

    public function insertDepositTransaction() {

        if (func_num_args() > 0) {
            $data = func_get_arg(0);

            try {
                $responseId = $this->insert($data);				
            } catch (Exception $e) {
				
                return $e->getMessage();
            }
            if ($responseId) {
                return true;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    public function validateTxId() {
        if (func_num_args() > 0) {
            $txid = func_get_arg(0);
            try {

                $select = $this->select()
                        ->from($this)
                        ->where('transaction_id = ?', $txid);

                $result = $this->getAdapter()->fetchRow($select);
            } catch (Exception $e) {
                throw new Exception('Unable To Insert Exception Occured :' . $e);
            }

            if ($result) {
                return true;
            } else {
				return false;
			}
        } else {
            throw new Exception('Argument Not Passed');
        }
    }
}