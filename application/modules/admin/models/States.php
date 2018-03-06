<?php

class Admin_Model_States extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'states';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Admin_Model_States();
		return self::$_instance;
    }
    
    public function getStates(){
        
        $select = $this->select()
                       ->from($this)
                       ->where('status = 1');        
        $result = $this->getAdapter()->fetchAll($select);
        if($result){
            return $result;
        }        
        
    }
    

    
    
    
    
}