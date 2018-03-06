<?php

class Admin_Model_Settings extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'settings';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Admin_Model_Settings();
        return self::$_instance;
    }

    
    /**
     * Developer : Bhojraj Rawte
     * Date : 29/07/2014
     * Description : Get Settings details
     */    
    public function getSettingsDeatils() {
        $select = $this->select();
        $result = $this->getAdapter()->fetchRow($select);

        if ($result) {
            return $result;
        }
    }    
  
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 29/07/2014
     * Description : Update Settings details
     */    
    public function updateSettingsDeatils() {
         if(func_num_args()>0):
            $data = func_get_arg(0);
            $sid = func_get_arg(1);
            
            try{
                $this->update($data, 'setting_id='.$sid);
                }catch(Exception $e){
                    throw new Exception($e);
                    }
          
        endif;
    }    
    
}