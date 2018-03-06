<?php

class Admin_Model_ContestsType extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'contests_type';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Admin_Model_ContestsType();
		return self::$_instance;
    }

    /**
    * Developer : Bhojraj Rawte
    * Date : 10/03/2014
    * Description : Get all contest type that be active details
    */    
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
    * Date : 25/03/2014
    * Description : Set New Contest Type details
    */     
    public function setContestTypeDetails(){
        
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
    * Description : Get all contest type  details
    */    
    public function getAllContestTypeDetails(){        
        $select = $this->select()
                       ->from($this);                       
        $result = $this->getAdapter()->fetchAll($select);
        if($result){
            return $result;
        }        
        
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 14/05/2014
     * Description : Delete Contests details
     */    
    public function contestsTypeDelete(){
        if (func_num_args() > 0):
            $cid = func_get_arg(0);        
            try {
                $db = Zend_Db_Table::getDefaultAdapter();
                $where = (array('con_type_id = ?' => $cid));
                $db->delete('contests_type', $where);
            } catch (Exception $e) {
                throw new Exception($e);
            }
            return $cid;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
    
    /**
    * Developer : Bhojraj Rawte
    * Date : 25/03/2014
    * Description : Set New Contest Type details
    */     
    public function getContestTypeDetailsById(){
        
        if(func_num_args() > 0){
            $contestId = func_get_arg(0);
            $select = $this->select()
                    ->where('con_type_id = ?', $contestId);

            $result = $this->getAdapter()->fetchRow($select);

            if ($result) {
                return $result;
            }
            
        }       
    }  
   
    /**
     * Developer : Bhojraj Rawte
     * Date : 15/05/2014
     * Description : Update Contest Details by id
     */
    
    public function updateContestType() {

        if (func_num_args() > 0):            
            $data = func_get_arg(0);
            $cid = func_get_arg(1);
            try {
                $result = $this->update($data, 'con_type_id = "' . $cid . '"');
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
     * Description : Contest Active Deactive
     */    
    public function contestActiveDeactive() {
        if (func_num_args() > 0):
            $cid = func_get_arg(0);
            try {
                $data = array('status' => new Zend_DB_Expr('IF(status=1, 0, 1)'));
                $result = $this->update($data, 'con_type_id = "' . $cid . '"');
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
    
}