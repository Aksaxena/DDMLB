<?php

class Admin_Model_Promotions extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'promotions';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Admin_Model_Promotions();
        return self::$_instance;
    }

    /**
     * Developer : Abhinish Kumar Singh
     * Date : 14/07/2014
     * Description : Set New Contests details
     */
    public function addPromotionDetails() {

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
     * Developer : Abhinish Kumar Singh
     * Date : 14/07/2014
     * Description : Get Promotion details
     */    
    public function getPromotions() {
        $select = $this->select();
        $result = $this->getAdapter()->fetchAll($select);

        if ($result) {
            return $result;
        }
    }
    
     /**
     * Developer : Abhinish Kumar Singh
     * Date : 14/07/2014
     * Description : Delete Promotion details
     */    
    public function promotionsDelete(){
        if (func_num_args() > 0):
            $cid = func_get_arg(0);
            try {
                $db = Zend_Db_Table::getDefaultAdapter();
                $where = (array('promotion_id = ?' => $cid));
                $db->delete('promotions', $where);
            } catch (Exception $e) {
                throw new Exception($e);
            }
            return $cid;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
    
    /**
     * Developer : Abhinish Kumar Singh
     * Date : 14/07/2014
     * Description : Get Promotion details by Id
     */    
    public function getPromotionsDetailsById()  {
        if (func_num_args() > 0):
            $contestID = func_get_arg(0);
            try {
                $select = $this->select()                        
                        ->where('promotion_id =?', $contestID);

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
     * Developer : Abhinish Kumar Singh
     * Date : 16/07/2014
     * Description : Get Active Promotion details
     */    
    public function getActivePromotions()  {
        
            try {
                $select = $this->select()                        
                        ->where('status =?', 1);

                $result = $this->getAdapter()->fetchAll($select);
                if ($result) :
                    return $result;
                endif;
            } catch (Exception $e) {
                throw new Exception($e);
            }
    }
    
    /**
     * Developer : Abhinish Kumar Singh
     * Date : 14/07/2014
     * Description : Get Promotion details by Id
     */    
    public function getPromotionsDetailsByDisplayName()  {
        if (func_num_args() > 0):
            $promotionName = func_get_arg(0);
            try {
                $select = $this->select()                        
                        ->where('promotion_display_name =?', $promotionName);

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
     * Developer : Abhinish Kumar Singh
     * Date : 14/07/2014
     * Description : Update Promotion Details
     */
    
    public function updatePromotionDetails() {

        if (func_num_args() > 0):
            $promotionid = func_get_arg(0);
            $promotiondata = func_get_arg(1);
            try {
                $result = $this->update($promotiondata, 'promotion_id = "' . $promotionid . '"');
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
     * Developer : Abhinish Kumar Singh
     * Date : 14/07/2014
     * Description : Promotion Active Deactive
     */
    public function promotionActiveDeactive() {
        if (func_num_args() > 0):
            $pid = func_get_arg(0);
            try {
                $data = array('status' => new Zend_DB_Expr('IF(status=1, 0, 1)'));
                $result = $this->update($data, 'promotion_id = "' . $pid . '"');
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
     * Description : count Promotion
     */
     public function getTotalPromotion() {

        $select = $this->select()
                ->from($this, array("Totalpromotion" => "COUNT(*)"));

        $result = $this->getAdapter()->fetchRow($select);
        if ($result) {
            return $result['Totalpromotion'];
        } else {
            return false;
        }
    }        
    
}