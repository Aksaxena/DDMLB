<?php

class Application_Model_Withdrawal extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'withdrawal_request';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_Withdrawal();
		return self::$_instance;
    }
    
    public function insertWithdrawalRequest($data){
        
        $requestId = $this->insert($data);
        
        if($requestId){
            return $requestId;
        }
    }		public function getWithdrawalDeatilsByUserId() {        if (func_num_args() > 0):            $uid = func_get_arg(0);            try {                $select = $this->select()                        ->from(array('wr' => 'withdrawal_request'))                         ->setIntegrityCheck(false)                        ->join(array('u' => 'users'), 'u.user_id = wr.user_id', array("u.user_name"))                        ->where('wr.user_id =?', $uid)												->where('wr.status =?', '0');                $result = $this->getAdapter()->fetchAll($select);            } catch (Exception $e) {                throw new Exception($e);            }            if ($result) :                return $result;            endif;        else:            throw new Exception('Argument Not Passed');        endif;    }		public function getWithdrawalDeatilsById() {        if (func_num_args() > 0):            $wid = func_get_arg(0);            try {                $select = $this->select()                        ->from(array('wr' => 'withdrawal_request'))                        ->setIntegrityCheck(false)                        ->join(array('u' => 'users'), 'u.user_id = wr.user_id', array("u.user_name"))                        ->where('wr.withdrawal_id =?', $wid);                $result = $this->getAdapter()->fetchRow($select);            } catch (Exception $e) {                throw new Exception($e);            }            if ($result) :                return $result;            endif;        else:            throw new Exception('Argument Not Passed');        endif;    }	 public function updateByID() {        if (func_num_args() > 0):            $wid = func_get_arg(0);			$updateData = func_get_arg(1);            try {                $result = $this->update($updateData, "withdrawal_id = '" . $wid . "'");            } catch (Exception $e) {                throw new Exception($e);            }            if ($result):                return $wid;            else:                return 0;            endif;        else:            throw new Exception('Argument Not Passed');        endif;    }
}
?>
