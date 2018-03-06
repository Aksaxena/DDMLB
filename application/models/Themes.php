<?php
class Application_Model_Themes extends Zend_Db_Table_Abstract{
    
    private static $_instance = null;
    protected $_name = 'themes';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_Themes();
		return self::$_instance;
    }
    
    public function getActiveTheme(){
        $select = $this->select()
                       ->from($this,array('name'))
                       ->where('active =?','1');
        
        $result = $this->getAdapter()->fetchRow($select);
        if($result){
            return $result;
        }
        
    }
}
?>
