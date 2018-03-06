<?php

class Admin_Model_Emaillog extends Zend_Db_Table_Abstract{
    
    private static $_instance = null;
    protected $_name = 'email_log';
    
    private function __clone(){
        
    }
    
    public static function getInstance(){
        if(!is_object(self::$_instance))
            self::$_instance = new Admin_Model_Emaillog();
       return self::$_instance;
     }
     
     public function getAllEmailLog(){
         $select = $this->select()->from($this);
         try {
             $result = $this->getDefaultAdapter()->fetchAll($select);
             if($result){
                 return $result;
             }
         } catch (Exception $exc) {
             echo $exc->getTraceAsString();
         }
      }
      
    public function insertEmailLog() {
        
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
}
?>
