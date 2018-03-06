<?php

class Admin_Model_Contests extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'contests';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Admin_Model_Contests();
        return self::$_instance;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 14/05/2014
     * Description : Set New Contests details
     */
    public function setContestsDetails() {

        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            try {
                $responseId = $this->insert($data);
            } catch (Exception $e) {
                throw new Exception('Unable To Insert Exception Occured :' . $e);
            }

            if ($responseId) {
                return $responseId;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 14/05/2014
     * Description : Get Contests details
     */    
    public function getContests() {
        $select = $this->select()->order('start_time DESC');
      
        $result = $this->getAdapter()->fetchAll($select);

        if ($result) {
            return $result;
        }
    }
    
    public function getContestsByType() {
         if (func_num_args() > 0){
            $type = func_get_arg(0);
            try {
                $select = $this->select()                        
                        ->where('con_status =?', $type)
                        ->order('start_time DESC');
                $result = $this->getAdapter()->fetchAll($select);
                if ($result) {
                    return $result;
                }
            } catch (Exception $e) {
                throw new Exception($e);
            }
         }else {
            throw new Exception('Argument Not Passed');
        }
    }
    
     /**
     * Developer : Bhojraj Rawte
     * Date : 14/05/2014
     * Description : Delete Contests details
     */    
    public function contestsDelete(){
        if (func_num_args() > 0):
            $cid = func_get_arg(0);
            try {
                $db = Zend_Db_Table::getDefaultAdapter();
                $where = (array('contest_id = ?' => $cid));
                $db->delete('contests', $where);
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
     * Date : 14/05/2014
     * Description : Get Contests details
     */    
    public function getContestsDetailsById()  {
        if (func_num_args() > 0):
            $contestID = func_get_arg(0);
            try {
                $select = $this->select()                        
                        ->where('contest_id =?', $contestID);

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
     * Date : 14/05/2014
     * Description : Update Contest Details
     */
    
    public function updateContestDetails() {

        if (func_num_args() > 0):
            $contestid = func_get_arg(0);
            $contestdata = func_get_arg(1);
            try {
                $result = $this->update($contestdata, 'contest_id = "' . $contestid . '"');
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
     * Date : 29/07/2014
     * Description : count Contest
     */
     public function getTotalContest() {

        $select = $this->select()
                ->from($this, array("Totalcontest" => "COUNT(*)"));

        $result = $this->getAdapter()->fetchRow($select);
        if ($result) {
            return $result['Totalcontest'];
        } else {
            return false;
        }
    }  
    /*
     * Developer : Vini Dubey
     * Date : 06/08/2014
     * Description : Update contests
    */
    public function contestsUpdate(){
        if (func_num_args() > 0):
            $cid = func_get_arg(0);
            try {
                $data = array('is_featured' => new Zend_DB_Expr('IF(is_featured=1, 0, 1)'));
                $result = $this->update($data, 'contest_id = "' . $cid . '"');
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
     * Date : 08/08/2014
     * Description : Get Not Featured Contests details
     */    
    public function getNotFeaturedContests() {
        $select = $this->select()
                       ->where('is_featured = ?',0)
                       ->where('status = ?',1)
                       ->order('contest_id DESC');
        $result = $this->getAdapter()->fetchAll($select);

        if ($result) {
            return $result;
        }
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 08/08/2014
     * Description : Get Featured Contests details
     */    
    public function getFeaturedContests() {
        $select = $this->select()
                       ->where('is_featured = ?',1)
                       ->where('status = ?',1)
                       ->order('contest_id DESC');
        $result = $this->getAdapter()->fetchAll($select);

        if ($result) {
            return $result;
        }
    } 
    
   public function getContestForTicket(){
        $select = $this->select()
                        ->from($this, array("contest_id","contest_name"))
                       ->where('ticket_id = ?',0)
                       ->where('con_status = ?',0)
                       ->where('status = ?',1)
                       ->order('contest_id DESC');
        $result = $this->getAdapter()->fetchAll($select);

        if ($result) {
            return $result;
        }
   } 
   
}