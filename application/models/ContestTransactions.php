<?php

class Application_Model_ContestTransactions extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'contest_transactions';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_ContestTransactions();
        return self::$_instance;
    }
//vivek
    public function insertConTransaction() {
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            try {
                $responseId = $this->insert($data);
                if ($responseId) {
                    return $responseId;
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }
	
	
	public function getContestEnteryByUserIdAndContestId() {
        if (func_num_args() > 0) :
            $contestId = func_get_arg(0);
            $user_id = func_get_arg(1);
            try {
                 $select = $this->select()
                ->where('contest_id = ?', $contestId)  
                ->where('user_id = ?', $user_id);
				$result = $this->getAdapter()->fetchRow($select);
				if ($result) {
					return $result;
				}
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        else:
            throw new Exception('Argument not passed');
        endif;
    }
}