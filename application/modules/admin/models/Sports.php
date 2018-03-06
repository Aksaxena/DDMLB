<?php

class Admin_Model_Sports extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'sports';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Admin_Model_Sports();
		return self::$_instance;
    }
    
    public function getSportsDetails(){
        
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
    * Date : 25/03/2014
    * Description : Set New Sports details
    */     
    public function setSportsDetails(){
        
        if(func_num_args() > 0){
            $data = func_get_arg(0);
            try{
                $responseId = $this->insert($data);
            }catch(Exception $e){
                throw new Exception('Unable To Insert Exception Occured :'.$e);
            }
            
            if($responseId){
                return $responseId;
            }
        }else{
            throw new Exception('Argument Not Passed');
        }
        
        
   }    
   
   
   /**
    * Developer : Bhojraj Rawte
    * Date : 25/03/2014
    * Description : Get all game details
    */    
    public function getAllSportsDetails(){        
        $select = $this->select()
                       ->from($this);                       
        $result = $this->getAdapter()->fetchAll($select);
        if($result){
            return $result;
        }        
        
    }
    

    /**
    * Developer : Bhojraj Rawte
    * Date : 15/05/2014
    * Description : Get Sports Name By ID
    */     
    public function getSportsDetailsByID(){
        
        if(func_num_args() > 0){
            $sportsID = func_get_arg(0);
            try{
               $select = $this->select()                        
                        ->where('sports_id =?', $sportsID);

                $result = $this->getAdapter()->fetchRow($select);
                if ($result) :
                    return $result;
                endif;
            }catch(Exception $e){
                throw new Exception('Unable To Insert Exception Occured :'.$e);
            }        
        }else{
            throw new Exception('Argument Not Passed');
        }
        
        
   }      
   
     /**
     * Developer : Bhojraj Rawte
     * Date : 15/05/2014
     * Description : Delete Sports details
     */    
    public function sportsDelete(){
        
        if (func_num_args() > 0):
            $gid = func_get_arg(0);
            try {
                $db = Zend_Db_Table::getDefaultAdapter();
                $where = (array('sports_id = ?' => $gid));
                $db->delete('sports', $where);
            } catch (Exception $e) {
                throw new Exception($e);
            }
            return $gid;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }   
 
    /**
     * Developer : Bhojraj Rawte
     * Date : 15/05/2014
     * Description : Update Sports Details
     */
    
    public function updateSportsDetails() {

        if (func_num_args() > 0):
            $sportsid = func_get_arg(0);
            $data = func_get_arg(1);
            try {
                $result = $this->update($data, 'sports_id = "' . $sportsid . '"');
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
     * Developer : Bhojraj Rawte
     * Date : 15/05/2014
     * Description : Sports Active Deactive
     */    
    public function sportsActiveDeactive() {
        if (func_num_args() > 0):
            $sid = func_get_arg(0);
            try {
                $data = array('status' => new Zend_DB_Expr('IF(status=1, 0, 1)'));
                $result = $this->update($data, 'sports_id = "' . $sid . '"');
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
     * Date : 29/07/2014
     * Description : count Sports
     */
     public function getTotalSports() {

        $select = $this->select()
                ->from($this, array("Totalsports" => "COUNT(*)"));

        $result = $this->getAdapter()->fetchRow($select);
        if ($result) {
            return $result['Totalsports'];
        } else {
            return false;
        }
    }        
    
}