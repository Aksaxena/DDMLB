<?php
class Static_Model_Store extends Zend_Db_Table_Abstract
{    
    private static $_instance = null;
    protected $_name = 'store_product';    
    
    private function  __clone() { } //Avoid Cloning
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  
		self::$_instance = new Static_Model_Store();
		return self::$_instance;
    }
    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : search Get all offer details
     */
    public function getStoreProductDetails(){
        $select = $this->select()
                ->setIntegrityCheck(false)
                ->from($this)
                ->where('status=1');
        $result = $this->getAdapter()->fetchAll($select);
     
        if($result):
            return $result;
        endif;
    }
    /**
     * Developer    : vivek Chaudhari
     * Date         : 12/07/2014
     * Description  : get ticket details for store
     */
    public function getStoreTicketDetails(){
        $select = $this->select()
                ->setIntegrityCheck(false)
                ->from('ticket_system')
                ->where('status=1');
        $result = $this->getAdapter()->fetchAll($select);
        if($result):
            return $result;
        endif;
    }
    /**
     * Developer    : vivek Chaudhari
     * Date         : 12/07/2014
     * Description  : get fpp points for current user
     * @params      : param1= current user Id
     */
    public function getFppForCurrentUser(){
        if(func_num_args()>0):
            $userId = func_get_arg(0);
            if($userId):
                $db = Zend_Db_Table::getDefaultAdapter();
                    $select = $db->select()
                            ->from('user_account','fpp')
                            ->where('user_id=?',$userId);
                    $result = $this->getAdapter()->fetchRow($select);
                    if($result):
                        return $result;
                    endif;
            endif;
        endif;
      }

      
      
}