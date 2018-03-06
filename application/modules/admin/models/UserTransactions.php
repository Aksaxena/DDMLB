<?php

class Admin_Model_UserTransactions extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'user_transactions';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Admin_Model_UserTransactions();
		return self::$_instance;
    }
    
    
      public function insertUseTransactions() {

            if (func_num_args() > 0) {
                $data = func_get_arg(0);
                try {
                    $responseId = $this->insert($data);
                    if ($responseId) {
                        return $responseId;
                    }
                } catch (Exception $e) {
                    throw new Exception('Unable To Insert Exception Occured :' . $e);
                }
            } else {
                throw new Exception('Argument Not Passed');
            }
        }
    /**
     * Developer : Bhojraj Rawte
     * Date : 06/08/2014
     * Description : Get depositor Details
     */
     public function getdepositorDeatils() {         
          $select = $this->select()
                           ->from(array('ut' => 'user_transactions'))
                           ->setIntegrityCheck(false)
                           ->join(array('u' => 'users'),'u.user_id = ut.user_id',array("u.user_name"))  
                            ->order('ut.transaction_id DESC');
          
            $result = $this->getAdapter()->fetchAll($select);                        
            if ($result) :
                return $result;
            endif;
    }     
    /**
     * Developer : priyanka varanasi
     * Date : 25/11/2014
     * Description : getonly deposit Deatils
     */
     public function getonlydepositDeatils() {         
          $select = $this->select()
                           ->from(array('ut' => 'user_transactions'))
                           ->setIntegrityCheck(false)
                           ->join(array('u' => 'users'),'u.user_id = ut.user_id',array("u.user_name"))  
                           ->where('request_type=?',1)
                           ->order('ut.transaction_id DESC');
          
            $result = $this->getAdapter()->fetchAll($select);                        
            if ($result) :
                return $result;
            endif;
    }  
    /**
      Developer : priyanka varanasi
     * Date : 25/11/2014
     * Description : getonly withdraw Deatils
     */
     public function getonlywithdrawDeatils() {         
          $select = $this->select()
                           ->from(array('ut' => 'user_transactions'))
                           ->setIntegrityCheck(false)
                           ->join(array('u' => 'users'),'u.user_id = ut.user_id',array("u.user_name"))
                            ->where('request_type=?',2)
                           ->order('ut.transaction_id DESC');
          
            $result = $this->getAdapter()->fetchAll($select);                        
            if ($result) :
                return $result;
            endif;
    } 
    /**
     Developer : priyanka varanasi
     * Date : 25/11/2014
     * Description : getonly fppreward Deatils()
     */
     public function getonlyfpprewardDeatils() {         
          $select = $this->select()
                           ->from(array('ut' => 'user_transactions'))
                           ->setIntegrityCheck(false)
                           ->join(array('u' => 'users'),'u.user_id = ut.user_id',array("u.user_name")) 
                            ->where('request_type=?',3)
                           ->order('ut.transaction_id DESC');
          
            $result = $this->getAdapter()->fetchAll($select);                        
            if ($result) :
                return $result;
            endif;
    }
    /**
      Developer : priyanka varanasi
     * Date : 25/11/2014
     * Description :getonly refund Deatils
     */
     public function getonlyrefundDeatils() {         
          $select = $this->select()
                           ->from(array('ut' => 'user_transactions'))
                           ->setIntegrityCheck(false)
                           ->join(array('u' => 'users'),'u.user_id = ut.user_id',array("u.user_name"))  
                           ->where('request_type=?',4)
                           ->order('ut.transaction_id DESC');
          
            $result = $this->getAdapter()->fetchAll($select);                        
            if ($result) :
                return $result;
            endif;
    } 
    /**
      Developer : priyanka varanasi
     * Date : 25/11/2014
     * Description : getonly Winning Deatils()
     */
     public function getonlyWinningDeatils() {         
          $select = $this->select()
                           ->from(array('ut' => 'user_transactions'))
                           ->setIntegrityCheck(false)
                           ->join(array('u' => 'users'),'u.user_id = ut.user_id',array("u.user_name"))  
                           ->where('request_type=?',5)
                           ->order('ut.transaction_id DESC');
          
            $result = $this->getAdapter()->fetchAll($select);                        
            if ($result) :
                return $result;
            endif;
    }   
    /**
     Developer : priyanka varanasi
     * Date : 25/11/2014
     * Description :getonly entryfee Deatils()
     */
     public function getonlyentryfeeDeatils() {         
          $select = $this->select()
                           ->from(array('ut' => 'user_transactions'))
                           ->setIntegrityCheck(false)
                           ->join(array('u' => 'users'),'u.user_id = ut.user_id',array("u.user_name")) 
                           ->where('request_type=?',6)
                           ->order('ut.transaction_id DESC');
          
            $result = $this->getAdapter()->fetchAll($select);                        
            if ($result) :
                return $result;
            endif;
    } 
    /**
     Developer : priyanka varanasi
     * Date : 25/11/2014
     * Description : getonly bonus Deatils()
     */
    
     public function getonlybonusDeatils(){         
          $select = $this->select()
                           ->from(array('ut' => 'user_transactions'))
                           ->setIntegrityCheck(false)
                           ->join(array('u' => 'users'),'u.user_id = ut.user_id',array("u.user_name"))  
                          ->where('request_type=?',7)
                           ->order('ut.transaction_id DESC');
          
            $result = $this->getAdapter()->fetchAll($select);                        
            if ($result) :
                return $result;
            endif;
    }     
    
    //vivek
      public function getFppTxnDeatils(){         
          $select = $this->select()
                           ->from(array('ut' => 'user_transactions'))
                           ->setIntegrityCheck(false)
                           ->join(array('u' => 'users'),'u.user_id = ut.user_id')  
                           ->joinLeft(array('ua' => 'user_account'), 'u.user_id = ua.user_id')
                            ->where('ut.request_type=7')
                            ->where('ut.fpp_used!= 0')
                           ->order('ut.fpp_used DESC');
          
            $result = $this->getAdapter()->fetchAll($select);                        
            if ($result) :
                return $result;
            endif;
    } 

}