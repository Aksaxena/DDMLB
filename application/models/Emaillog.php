<?php 
class Application_Model_Emaillog extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'email_log';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_Emaillog();
		return self::$_instance;
    }
    
   
       
   
    public function insertEmailLog() {
        
        if(func_num_args() > 0){
            $data = func_get_arg(0);
            
           //  print_r($data);die('vhfvfv');
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
   
   public function getEmaillog()  {
          $select = $this->select();
        $result = $this->getAdapter()->fetchAll($select);
//echo "<pre>"; print_r($result); echo "</pre>"; die;
        if ($result) {
            return $result;
        }
    }
   
}
?>