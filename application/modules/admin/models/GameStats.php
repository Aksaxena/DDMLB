<?php

class Admin_Model_GameStats extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'game_stats';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Admin_Model_GameStats();
		return self::$_instance;
    }
    

    
    public function getGameStatsByID(){
        if (func_num_args() > 0){
            $sports_id = func_get_arg(0);
            $currentDate = func_get_arg(1);
            $extendedDate = func_get_arg(2);
            
            $sql = $this->select()
                       ->from($this)
                       ->where('sports_id = ?',$sports_id)
                       ->where('game_date >= ?',$currentDate)
                       ->where('game_date <= ?',$extendedDate);
        $result = $this->getAdapter()->fetchAll($sql);
       // print_r($result);die('tfdyc');
        if($result){
            return $result;
        }
    }
    }
}
?>
