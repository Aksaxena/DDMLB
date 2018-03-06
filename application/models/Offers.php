<?php
class Application_Model_Offers extends Zend_Db_Table_Abstract{
    
    private static $_instance = null;
    protected $_name = 'offers';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_Offers();
		return self::$_instance;
    }
    /*
         * Developer    :   Vivek Chaudhari
         * Date         :   09/07/2014
         * Description  :  get active offers
         * params       :  No parameters required
         */
    public function getActiveOffer(){
        $select = $this->select()
                       ->from($this)
                       ->where('status =?','1');
        try{
            $result = $this->getAdapter()->fetchAll($select);
            if($result){
                return $result;
            }
        }catch(Exception $e){
            echo $this->getTraceAsString($e);
        }
    }
    /*
         * Developer    :   Vivek Chaudhari
         * Date         :   11/07/2014
         * Description  :  get active offers and relative contest details
         * params       :  No parameters required
         */
    public function getOfferAndContestDetails(){
         $select = $this->select()
                        ->setIntegrityCheck(false)
                       ->from(array('o'=>'offers'),array('o.offer_name'))
                       ->join(array('c'=>'contests'),'o.contest_id=c.contest_id',array('c.contest_id','c.contest_name','c.prize_pool'))
                       ->where('c.status =1')
                        ->where('o.status=1');
                try {
                    $result = $this->getAdapter()->fetchAll($select);
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }

        
        if($result):
            return $result;
        endif;
        
    }
    
    public function test($data){
        $d['image_url'] = 'test';
        $d['offer_name'] = time();
        $d['status'] = 1;
        $d['contest_id'] = 111;
        $d['offer_type'] = 1;
        $d['offer_end_date'] = date('Y-m-d');
        
        $d['description'] = $data;
        $this->insert($d);
        
//        $s = $this->select()->from($this)->where('offer_id=59');
//        $result = $this->getAdapter()->fetchAll($s);
//        return $result;
    }
}
?>
