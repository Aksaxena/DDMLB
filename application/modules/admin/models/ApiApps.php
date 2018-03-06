<?php


class Admin_Model_ApiApps extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'dd_api_apps';

    private function __clone() {
        
    }

    public static function getInstance() {
        if (!is_object(self::$_instance))
            self::$_instance = new Admin_Model_ApiApps();
        return self::$_instance;
    }
 
	/**
	* Developer : Prince kumar dwivedi
	* Date : 01/01/2018
	* Description : Get All App User Details
	*/
    public function getAppsByUserId() { 
		if (func_num_args() > 0):
            $id = func_get_arg(0);
            try {
                $select = $this->select()                        
                        ->where('user_id =?', $id);
                $result = $this->getAdapter()->fetchAll($select);
                if ($result) :
					return $result;
                endif;
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }
	public function getAppById() { 
		if (func_num_args() > 0):
            $id = func_get_arg(0);
            try {
                $select = $this->select()                        
                        ->where('id =?', $id);
                $result = $this->getAdapter()->fetchRow($select);
                if ($result) :
					return $result;
                endif;
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }
	
	public function getAppUpdate() {
		if (func_num_args() > 0):
            $id = func_get_arg(0);
            $status = func_get_arg(1);
            try {
				$data = array('status'=>$status);
                $result = $this->update($data, 'id = "' . $id . '"');
                if ($result) :
					return $result;
                endif;
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }
   
}
?>