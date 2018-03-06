<?php

class Application_Model_ContestsType extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'contests_type';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_ContestsType();
		return self::$_instance;
    }
    
    public function getContestTypeDetails(){
        
        $select = $this->select()
                       ->from($this)
                       ->where('status = 1');        
        $result = $this->getAdapter()->fetchAll($select);
        if($result){
            return $result;
        }        
        
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 06/06/2014
     * Description : Get Contests ID By Name
     */    

    public function getContestIdByName() {
        if (func_num_args() > 0) {
            $contestName = func_get_arg(0);
            $select = $this->select()
                      ->from($this,array('con_type_id'))
                      ->where('display_name LIKE ?',$contestName);            
            $result = $this->getAdapter()->fetchRow($select);
            if ($result) {
                return $result;
            }
        }
    }
    
    
    
}