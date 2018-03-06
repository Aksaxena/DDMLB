<?php
/*
 * Name: Abhinish Kumar Singh
 * Date: 23/07/2014
 * Description: This class houses functions that assist in various kinds of
 *              operations related to user_billing_address table.
 */
class Application_Model_BillingAddress extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'user_billing_address';
    
    private function  __clone() { } //Prevent any copy of this object
    
    /*
     * Name: Abhinish Kumar Singh
     * Date: 23/07/2014
     * Description: This static function makes sure that only one object of this
     *              class is created and prevents multiple object creation
     *              thereby implementing singleton pattern.
     */
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_BillingAddress();
		return self::$_instance;
    }
    
    /*
     * Name: Abhinish Kumar Singh
     * Date: 23/07/2014
     * Description: This function returns all the address associated with an
     *              user.
     */
    public function getAddressByUserId() {
        
        if(func_num_args() > 0){
            $userid = func_get_arg(0);
            try{
                $select = $this->select()
                               ->from($this)
                               ->where('user_id = ?',$userid);
                
                $result = $this->getAdapter()->fetchAll($select);
            }catch(Exception $e){
                throw new Exception('Unable To Insert Exception Occured :'.$e);
            }
            
            if($result){
                return $result;
            }
        }else{
            throw new Exception('Argument Not Passed');
        }
        
        
   }
   
   /*
    * Name: Abhinish Kumar Singh
    * Date: 23/07/2014
    * Description: This function inserts new address into user's address list.
    */
    public function addAddress() {
        
        if(func_num_args() > 0){
            $data = func_get_arg(0);
            try{
                $responseId = $this->insert($data);
            }catch(Exception $e){
                throw new Exception('Unable To Insert Exception Occured :'.$e);
            }
            
            if($responseId){
                return $responseId;
            }
        }else{
            throw new Exception('Argument Not Passed');
        }
        
        
   }   
    
   /*
    * Name: Abhinish Kumar Singh
    * Date: 23/07/2014
    * Description: This function is used to edit address details based on
    *              address id.
    */
   public function editAddress(){
       if(func_num_args()>0):
                $addressId = func_get_arg(0);
                $data = func_get_arg(1);
                try {
                    $update = $this->update($data,"address_id =".$addressId);
                } catch (Exception $exc) {
                    throw new Exception('Unable to update, exception occured'.$exc);
                }
                    return $update;
                else:
                 throw new Exception('Argument not passed');
            endif;
   }   
    
    /*
     * Name: Abhinish Kumar Singh
     * Date: 23/07/2014
     * Description: This function returns address based on address_id
     */
    public function getAddressByAddressId() {
        
        if(func_num_args() > 0){
            $addressid = func_get_arg(0);
            try{
                $select = $this->select()
                               ->from($this)
                               ->where('address_id = ?',$addressid);
                
                $result = $this->getAdapter()->fetchRow($select);
            }catch(Exception $e){
                throw new Exception('Unable To Insert Exception Occured :'.$e);
            }
            
            if($result){
                return $result;
            }
        }else{
            throw new Exception('Argument Not Passed');
        }
        
        
   }
   
   
   /*
    * Name: Abhinish Kumar Singh
    * Date: 24/07/2014
    * Description: This function changes the status of all active addresses to
    *              suspended.
    */
   public function changeStatus(){
           if(func_num_args() > 0){
               $userid = func_get_arg(0); 
               $data['status'] = 0;
                try {
                    $update = $this->update($data,"user_id = ".$userid);
                } catch (Exception $exc) {
                    throw new Exception('Unable to update, exception occured'.$exc);
                }
                    return $update;
           }else{
               throw new Exception('Argument Not Passed');
           }
   }
   
   /*
    * Name: Sarika Nayak
    * Date: 26/08/2014
    * Description: This function removes the address of a user based on address id.
    */
   public function removeAddress() {
        if(func_num_args()>0){
          $addressid = func_get_arg(0); 
           try 
           {
               $db = Zend_Db_Table::getDefaultAdapter();
               $where = array('address_id=?'=> $addressid);
               $ok = $db->delete('user_billing_address',$where);
               return $ok;
           }  catch (Exception $e){
               throw new Exception($e);
           }
       }
        else{
            throw new Exception('Argument not passed');
        }
         
   }
   
   
   
   
   
   
   
   /*
    * Name: Abhinish Kumar Singh
    * Date: 24/07/2014
    * Description: This function sets the status of selected address as active
    *              and takes address id as parameter.
    */
   public function changeStatusByAddressId(){
           if(func_num_args() > 0){
               $addressid = func_get_arg(0); 
               $data['status'] = 1;
                try {
                    $update = $this->update($data,"address_id = ".$addressid);
                } catch (Exception $exc) {
                    throw new Exception('Unable to update, exception occured'.$exc);
                }
                    return $update;
           }else{
               throw new Exception('Argument Not Passed');
           }
   }
   
   
   /*
     * Name: Abhinish Kumar Singh
     * Date: 24/07/2014
     * Description: This function returns active address of the current user.
     */
    public function getActiveAddressByUserId() {
        
        if(func_num_args() > 0){
            $userid = func_get_arg(0);
            try{
                $select = $this->select()
                               ->from($this)
                               ->where('status = ?', 1 )
                               ->where('user_id = ?',$userid);
                
                $result = $this->getAdapter()->fetchAll($select);
            }catch(Exception $e){
                throw new Exception('Unable To Insert Exception Occured :'.$e);
            }
            
            if($result){
                return $result;
            }
        }else{
            throw new Exception('Argument Not Passed');
        }
        
        
   }
    
}