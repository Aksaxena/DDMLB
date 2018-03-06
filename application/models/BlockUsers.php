<?php

class 
Application_Model_BlockUsers extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'block_users';

    private function __clone() {
        
    }



    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_BlockUsers();
        return self::$_instance;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 31/07/2014
     * Description : Insert Blocked user
     */
    public function inserBlockedUser($userlineup) {
        $id = $this->insert($userlineup);
        if ($id) {
            return $id;
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 31/07/2014
     * Description : Get Blocked user details
     */
    public function getBlockedUserDetails() {
        if (func_num_args() > 0) {
            $userID = func_get_arg(0);
            
            $select = $this->select()                    
                           ->where('blocked_by =?', $userID);
            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 01/08/2014
     * Description : Delete Blocked user details
     */    
    public function deleteBlockedUser(){
       if (func_num_args() > 0) {
            $userid = func_get_arg(0);
            $user_name = func_get_arg(1);
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $where[] = $this->getAdapter()->quoteInto('user_name IN(?)', $user_name);
            $where[] = $this->getAdapter()->quoteInto('blocked_by = ?', $userid);
            $result = $db->delete('block_users', $where);
            
            if ($result) {
                return $result;
            }
        } 
        
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 01/08/2014
     * Description : Get Blocked user details by username
     */
    public function getBlockedUserDetailsByName() {
        if (func_num_args() > 0) {
            $username = func_get_arg(0);
            $userID = func_get_arg(1);
            $select = $this->select()                    
                           ->where('user_name =?', $username)
                           ->where('blocked_by =?', $userID);
//            echo $select;die;
            $result = $this->getAdapter()->fetchRow($select);

            if ($result) {
                return $result;
            }
        }
    }    
     
            
}

?>
