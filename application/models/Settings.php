<?php
class Application_Model_Settings extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'settings';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_Settings();
		return self::$_instance;
    }
    public function getSettings(){
        $select = $this->select();
        $result = $this->getAdapter()->fetchRow($select);
        if($result){
            return $result;
        }
    }
}
?>
