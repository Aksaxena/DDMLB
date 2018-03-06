<?php

class Admin_Model_UserAccount extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'user_account';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Admin_Model_UserAccount();
        return self::$_instance;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 08/03/2014
     * Description : Get All User Details
     */
    public function getUserAccountsDeatils() {
        $select = $this->select()
                ->from(array('uc' => 'user_account'))
                ->setIntegrityCheck(false)
                ->joinLeft(array('u' => 'users'), 'u.user_id = uc.user_id', array("u.user_name"));

        $result = $this->getAdapter()->fetchAll($select);
        if ($result) :
            return $result;
        endif;
    }

    
   public function getUserAccountsDeatilsByID() {
        if (func_num_args() > 0){
            $uid = func_get_arg(0);
        $select = $this->select()
                ->from(array('uc' => 'user_account'))
                ->setIntegrityCheck(false)
                ->joinLeft(array('u' => 'users'), 'u.user_id = uc.user_id', array("u.user_name"))
                ->where('u.user_id = ?',$uid);

        $result = $this->getAdapter()->fetchRow($select);
        if ($result) :
            return $result;
        endif;
    }
   }
   
   
    public function updateUserAccountDetails() {

        if (func_num_args() > 0):
            $uid = func_get_arg(0);
            $userdata = func_get_arg(1); 
            try {
                $result = $this->update($userdata, 'user_id = "' . $uid . '"');
                if ($result) {
                    return $result;
                } else {
                    return 0;
                }
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }      
   

    public function deleteuserAccnt() {
        if (func_num_args() > 0):
            $uid = func_get_arg(0);
            try {
                $db = Zend_Db_Table::getDefaultAdapter();
                $where = (array('user_id = ?' => $uid));
                $db->delete('user_account', $where);
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
	
    public function updateUserBalanceWithdrawn($userid,$req_amount){  
		$data = array('balance_amt' => new Zend_Db_Expr('balance_amt - '.$req_amount));  
		$result = $this->update($data, 'user_id =' . $userid);   
		if ($result) {        
			return $result; 
		}  
	}
	
	
    public function updateUserBalanceCancelWithdrawn($userid,$req_amount){
        $data = array('balance_amt' => new Zend_Db_Expr('balance_amt + '.$req_amount));
        $result = $this->update($data, 'user_id =' . $userid);
        if ($result) {
            return $result;
        }
    }  
}