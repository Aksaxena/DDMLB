<?php

class Application_Model_Countries extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'countries';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_Countries();
		return self::$_instance;
    }
    
    public function getCountries(){
       
        $select = $this->select()
                       ->from($this)
                       ->where('status = 1');
                    
       
        
        $result = $this->getAdapter()->fetchAll($select);
        if($result){
            return $result;
        }
                
    }
    
    public function getBlockCountriesDetails(){
        if(func_num_args() > 0){
            $countryCode = func_get_arg(0);
            $countryName = func_get_arg(1);
        
            $select = $this->select()
                       ->from($this)
                        ->where('bonus = ?', '1')
                        ->where("country_code = '" . $countryCode . "' OR country_name = '" . $countryName . "'"); 
                    
            $result = $this->getAdapter()->fetchRow($select);
            if($result){
                return $result;
            }
        }
    
    }
    
}