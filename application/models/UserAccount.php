<?php
class Application_Model_UserAccount extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'user_account';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_UserAccount();
		return self::$_instance;
    }
    
    /**
     * developer    :- vivek chaudhari
     * date         :- 
     * description  :- update user balance
     * @params      :- <int> userId <array>userdata
     */
    public function updateUserBalance(){
        
        if(func_num_args()>0):
            $userid = func_get_arg(0);
            $accountData = func_get_arg(1);
			
            if(isset($accountData['last_deposite'])) {
				$data = array('balance_amt' => new Zend_Db_Expr('balance_amt + '.$accountData['balance_amt']),
                          'bonus_amt' =>  new Zend_Db_Expr('bonus_amt + '.$accountData['bonus_amt']),'last_deposite'=>$accountData['last_deposite']);
			} else {
				$data = array('balance_amt' => new Zend_Db_Expr('balance_amt + '.$accountData['balance_amt']),
                          'bonus_amt' =>  new Zend_Db_Expr('bonus_amt + '.$accountData['bonus_amt']));
			}
            
                  try {
                    $result = $this->update($data, 'user_id =' . $userid);
                    if ($result) :
                         return $result;
                    endif;
                  } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                  }
        else:
            throw new Exception("Argument not passed");
        endif;
    }
    
    public function updateUserAccount(){
        if(func_num_args()>0){
            $userid = func_get_arg(0);
            $data = func_get_arg(1);
            try {
                $result = $this->update($data, 'user_id =' . $userid);
                if ($result) {
                    return $result;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }else{
            throw new Exception("Argument not passed");
        }
    }
    
    public function updateUserBalanceWithdrawn($userid,$req_amount){
        
        $data = array('balance_amt' => new Zend_Db_Expr('balance_amt - '.$req_amount));
//                             print_r($data); die;
        $result = $this->update($data, 'user_id =' . $userid);
        if ($result) {
            return $result;
        }
    }    
    /**
     * developer    :- vivek chaudhari
     * date         :- 20/06/2014
     * description  :- update user balance
     * @params      :- <int> userId 
     * @return      :- <int> user balance amount
     */
    public function getUserBalance(){
        if(func_num_args()>0):
            $user_id = func_get_arg(0);
            try {
                $select = $this->select()
                        ->from($this,array('balance_amt','bonus_amt'))
                        ->where('user_id =?',$user_id);
                $result = $this->getAdapter()->fetchRow($select);

                if($result):
                     return $result;
                else:    
                    return 0;
                endif;
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        else:
            throw new Exception("Argument not passed");
        endif;          
    }
    
    public function updateUserSettings(){
        if(func_num_args() > 0){
               $accountId = func_get_arg(0);
               $data['settings'] = func_get_arg(1);
                try {
                    $update = $this->update($data,"account_id = ".$accountId);
                } catch (Exception $exc) {
                    throw new Exception('Unable to update, exception occured'.$exc);
                }
                    return $update;
           }else{
               throw new Exception('Argument Not Passed');
           }
                       
    }
    
    /**
     * developer    :- Bhojraj Rawte
     * date         :- 19/08/2014
     * description  :- update user balance
     * @params      :- <int> userId <array>userdata
     */
    public function updateBalance(){
        
        if(func_num_args()>0):
            $userid = func_get_arg(0);
            $price = func_get_arg(1);
            
            $data = array('balance_amt' => new Zend_Db_Expr('balance_amt + '.$price));
                  try { 
                    $result = $this->update($data, 'user_id =' . $userid);
                    if ($result) :
                         return $result;
                    endif;
                  } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                  }
        else:
            throw new Exception("Argument not passed");
        endif;
    } 
	public function updatePurchaseBalance(){
        
        if(func_num_args()>0):
            $userid = func_get_arg(0);
            $accdata = func_get_arg(1);
            
            $data = array('balance_amt' => new Zend_Db_Expr('balance_amt + '.$accdata['balance_amt']),'last_deposite'=>$accdata['last_deposite']);
                  try { 
                    $result = $this->update($data, 'user_id =' . $userid);
                    if ($result) :
                         return $result;
                    endif;
                  } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                  }
        else:
            throw new Exception("Argument not passed");
        endif;
    } 
	
        /**
     * developer    :- vivek chaudhari
     * date         :- 08/09/2014
     * description  :- get users available tickets
     * @params      :- <int> userId 
     * @return      :- <int> user available tickets
     */
    public function getUserTickets(){
        if(func_num_args()>0):
            $user_id = func_get_arg(0);
            try {
                $select = $this->select()
                        ->from($this,array('available_tickets','used_tickets'))
                        ->where('user_id =?',$user_id);
                $result = $this->getAdapter()->fetchRow($select);

                if($result){
                   return $result; 
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        else:
            throw new Exception("Argument not passed");
        endif;          
    }
      /**
     * developer    :- vivek chaudhari
     * date         :- 15/09/2014
     * description  :- add fpp of contest when user enter into any contest
     * @params      :- <int> userId 
     * @return      :- <int> fpp points of entered contest
     */
    public function updateUserFppAdded(){
        if(func_num_args()>0){
            $userid = func_get_arg(0);
            $fpp = func_get_arg(1);
            try { 
                    $data = array('fpp' => new Zend_Db_Expr('fpp + '.$fpp));
                    $result = $this->update($data, 'user_id =' . $userid);
                    if ($result) {
                        return $result;
                    }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }else{
            throw new Exception("Argument not passed");
        }  
        
    }
    
    /**
     * developer    :- Bhojraj Rawte
     * date         :- 23/09/2014
     * description  :- intial user balance     
     */
    public function insertBalance(){
        
        if(func_num_args()>0):
            $data = func_get_arg(0);            
                  try { 
                    $this->insert($data);
                  } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                  }
        else:
            throw new Exception("Argument not passed");
        endif;
    }
    

          /**
     * developer    :- Bhojraj Rawte
     * date         :- 25/09/2014
     * description  :- Subtract fpp of contest when user buy the ticket
     * @params      :- <int> userId 
     * @return      :- <int> fpp points of entered contest
     */
    public function updateUserFppBalance(){
        if(func_num_args()>0){
            $userid = func_get_arg(0);
            $fpp = func_get_arg(1);
            try {
                    $data = array('fpp' => new Zend_Db_Expr('fpp - '.$fpp));
                    $result = $this->update($data, 'user_id =' . $userid);
//                    if ($result) {
                        return 1;
//                    }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }else{
            throw new Exception("Argument not passed");
        }  
        
    }
    
      public function updateUserBonusAmount($userid,$req_amount){
        
        $data = array('bonus_amt' => new Zend_Db_Expr('bonus_amt - '.$req_amount));
//                             print_r($data); die;
        $result = $this->update($data, 'user_id =' . $userid);
        if ($result) {
            return $result;
        }
    }  
    
    public function addUserBonusAmount($userid,$req_amount){
        
        $data = array('bonus_amt' => new Zend_Db_Expr('bonus_amt + '.$req_amount));
//                             print_r($data); die;
        $result = $this->update($data, 'user_id =' . $userid);
        if ($result) {
            return $result;
        }
    }  
    
    public function getAccountByUserId(){
        if(func_num_args()>0){
            $user_id = func_get_arg(0);
            try {
                $select = $this->select()
                        ->from($this)
                        ->where('user_id =?',$user_id);
                $result = $this->getAdapter()->fetchRow($select);

                if($result){
                   return $result; 
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }else{
            throw new Exception("Argument not passed");
        }          
    }
      /**
     * developer    :- vivek chaudhari
     * date         :- 
     * description  :- update user balance
     * @params      :- <int> userId <array>userdata
     */
    public function updateUserBalanceOnly(){
        
        if(func_num_args()>0):
            $userid = func_get_arg(0);
            $accountData = func_get_arg(1);
            if(isset($accountData['last_deposite'])) {
				 $data = array('balance_amt' => new Zend_Db_Expr('balance_amt + '.$accountData['balance_amt']),'last_deposite'=>$accountData['last_deposite']);
			} else {
				 $data = array('balance_amt' => new Zend_Db_Expr('balance_amt + '.$accountData['balance_amt']));
			}
           
			  try {
				$result = $this->update($data, 'user_id =' . $userid);
				if ($result) :
					 return $result;
				endif;
			  } catch (Exception $exc) {
				echo $exc->getTraceAsString();
			  }
        else:
            throw new Exception("Argument not passed");
        endif;
    }
    
    //vivek chaudhari (30Aug2015) => To update the user balance while fpp transaction
    public function updateExchangeBalance(){
        
        if(func_num_args()>0):
            $userid = func_get_arg(0);
            $cashAmt = func_get_arg(1);
            $bonusAmt = func_get_arg(2);
            $fppReqd = func_get_arg(3);
            
            $data = array('balance_amt' => new Zend_Db_Expr('balance_amt + '.$cashAmt),
                          'bonus_amt' =>  new Zend_Db_Expr('bonus_amt - '.$bonusAmt),
                          'fpp' =>  new Zend_Db_Expr('fpp - '.$fppReqd));
            try {
               $result = $this->update($data, 'user_id =' . $userid);
               if ($result) :
                    return $result;
               endif;
             } catch (Exception $exc) {
               echo $exc->getTraceAsString();
             }
        else:
            throw new Exception("Argument not passed");
        endif;
    }		public function updateUserBalanceCancelWithdrawn($userid,$req_amount){        $data = array('balance_amt' => new Zend_Db_Expr('balance_amt + '.$req_amount));        $result = $this->update($data, 'user_id =' . $userid);        if ($result) {            return $result;        }    }
    
}
?>
