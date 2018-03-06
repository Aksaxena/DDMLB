<?php

class Admin_Model_Themes extends Zend_Db_Table_Abstract
{    
    private static $_instance = null;
    protected $_name = 'themes';    
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Admin_Model_Themes();
		return self::$_instance;
    }
    /**
     * Developer : Bhojraj Rawte
     * Date : 08/03/2014
     * Description : search Get all themes details
     */
     public function getThemesDeatils() {
            $select = $this->select()
                    ->from($this);            
            $result = $this->getAdapter()->fetchAll($select);            
            if ($result) :
                return $result;
            endif;
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 08/03/2014
     * Description : Themes Active 
     */
    public function themeActive() {
        if (func_num_args() > 0):
            $tid = func_get_arg(0);
            try {
                $data = array('active' => '1');
                $result = $this->update($data, 'theme_id = "' . $tid . '"');
                $data = array('active' => '0');
                $this->update($data, 'theme_id != "' . $tid . '"');
            } catch (Exception $e) {
                throw new Exception($e);
            }
            if ($result):
                return $result;
            else:
                return 0;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 08/03/2014
     * Description : Themes Deactive 
     */
    public function themeDeactive() {
        if (func_num_args() > 0):
            $tid = func_get_arg(0);
            try {
                $data = array('active' => '0');
                $result = $this->update($data, 'theme_id = "' . $tid . '"');
            } catch (Exception $e) {
                throw new Exception($e);
            }
            if ($result) :
                return $result;
            else :
                return 0;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
}
?>
