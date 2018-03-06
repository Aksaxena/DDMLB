<?php

class Application_Model_ReferFriends extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'refer_friends';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_ReferFriends();
        return self::$_instance;
    }

    public function insertReferal() {
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            try {
                $responseId = $this->insert($data);
            } catch (Exception $e) {
                throw new Exception('Unable To Insert Exception Occured :' . $e);
            }
            if ($responseId) {
                return $responseId;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }
	public function getReferByUserEmail() {
        if (func_num_args() > 0) {
            $email = func_get_arg(0);
            try {
                $select = $this->select()
                        ->from($this)
                        ->where('email = ?', $email)
                        ->where('status = ?', 1)
                        ->where('acceptance = ?', 1);
                $result = $this->getAdapter()->fetchRow($select);
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
            if ($result) {
                return $result;
            }
        } else {
            throw new Exception('Argument not passed');
        }
    }
//vivek : 23oct2015 / for getting user referal details according to emails
    public function insertNewReferal() {
        if (func_num_args() > 0) {
            $referData = func_get_arg(0);
//print_r($referData);
            $select = $this->select()
                    ->from($this)
                    ->where('ref_by =?', $referData['ref_by'])
                    ->where('email IN (?)', $referData['email']);

            $result = $this->getAdapter()->fetchRow($select);
            if ($result) {
                $update = array('req_count' => new Zend_Db_Expr('req_count + 1'));
                $this->update($update, "ref_id =".$result['ref_id']);
            }else{
                $this->insert($referData);
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

}

?>
