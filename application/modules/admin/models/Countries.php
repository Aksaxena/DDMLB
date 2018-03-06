<?php

class Admin_Model_Countries extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'countries';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Admin_Model_Countries();
		return self::$_instance;
    }
    
    public function getCountries(){
       
        $select = $this->select()
                       ->from($this);        
        $result = $this->getAdapter()->fetchAll($select);
        if($result){
            return $result;
        }
        
        
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 11/03/2014
     * Description : Country Active Deactive
     */    
    public function countryActiveDeactive() {
        if (func_num_args() > 0):
            $uid = func_get_arg(0);
            try {
                $data = array('status' => new Zend_DB_Expr('IF(status=1, 0, 1)'));
                $result = $this->update($data, 'country_id = "' . $uid . '"');
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
     * Date : 11/03/2014
     * Description : Country Delete
     */        
    public function countryDelete() {
        if (func_num_args() > 0):
            $cid = func_get_arg(0);
            try {
                $db = Zend_Db_Table::getDefaultAdapter();
                $where = (array('country_id = ?' => $cid));
                $db->delete('countries', $where);
            } catch (Exception $e) {
                throw new Exception($e);
            }
            return $uid;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 08/03/2014
     * Description : Get User Details by user id
     */
    public function getCountryDeatilsByID() {
        if (func_num_args() > 0):
            $country_id = func_get_arg(0);
            try {
        $select = $this->select()
                       ->from($this)
                       ->where('country_id = ?',$country_id); 

                $result = $this->getAdapter()->fetchRow($select);
                if ($result) :
                    return $result;
                endif;
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 19/03/2014
     * Description : Update User Details by user id
     */
    
    public function updateCountryDetails() {

        if (func_num_args() > 0):
            $country_id = func_get_arg(0);
            $country_data = func_get_arg(1);
            try {
                $result = $this->update($country_data, 'country_id = "' . $country_id . '"');
                if ($result) {
                    return $result;
                } else {
                    return 0;
                }
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }    
    
    
     /**
      *  Name:Sarika Nayak
      *  date:2/8/2014
      * description: to update bonus section "country" table  
      */

    public function updateBonus(){
        
      if (func_num_args() > 0) {
            $list = func_get_arg(0);
            print_r($list);
             $action = func_get_arg(1);
        foreach ($list as $country_id) {
          
	  $data = array('bonus' =>$action);
	 $result= $this->update($data, 'country_id = '. (int)$country_id);
          
        }
        if ($result) {
                    return $result;
                }
    }
    } 
}