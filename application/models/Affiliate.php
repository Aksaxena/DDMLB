<?php
class Application_Model_Affiliate extends Zend_Db_Table_Abstract{
    
    private static $_instance = null;
    protected $_name = 'affiliate';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_Affiliate();
		return self::$_instance;
    }
    
    public function insertAffiliate() {
        
        if(func_num_args() > 0){
            $data = func_get_arg(0);
            
            
            try{
                $responseId = $this->insert($data);
            }catch(Exception $e){               
                 return $e->getMessage(); 
            }
            
            if($responseId){
                return $responseId;
            }
        }else{
            throw new Exception('Argument Not Passed');
        }
        
        
   }
   
      public function getAffiliateDataByID() {
          if(func_num_args() > 0){
            $userid = func_get_arg(0);
            try{
                $select = $this->select()
                               ->from($this)
                               ->where('registred_user_id = ?',$userid)
                               ->where('status = ?','1');
                
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
      
      
  public function updateAffiliate(){
      if(func_num_args()>0){                
                $affId = func_get_arg(0);
                
                $data = array('status'=>'0');                   
                try {
                    $update = $this->update($data,'affiliate_id =' . $affId);
                } catch (Exception $exc) {
                    throw new Exception('Unable to update, exception occured'.$exc);
                }
                if($update){
                    return $update;
                }
            }else{
                 throw new Exception('Argument not passed');
            }
  }
   
}
?>
