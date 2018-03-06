<?php
class Application_Model_TicketSystem extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'ticket_system';

    private function __clone() {
        
    }

    public static function getInstance() {
        if (!is_object(self::$_instance))  
            self::$_instance = new Application_Model_TicketSystem();
        return self::$_instance;
    }
    /**
     * Developer    : Vivek chaudhari
     * Date         : 25/08/2014
     * Description  : update ticket user for ticket
     * @param       : <int>ticket Id.
     * @return      : <array>ticket details
     */ 
    public function getTicketDetailsById(){
        if(func_num_args()>0){
            $tId = func_get_arg(0);
            
            $select = $this->select()->where('ticket_id=?',$tId);
           
            try {
                $result = $this->getAdapter()->fetchRow($select);
                if($result){
                    return $result;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }else{
            throw new Exception("Argument not passed");
        }
    }
    /**
     * Developer    : Vivek chaudhari
     * Date         : 25/08/2014
     * Description  : update ticket user for ticket
     * @param       : <array>json encoded ticket users, <int>user Id.
     * @return      : <int>affected rows
     */ 
    public function updateTicketUsers(){
        if(func_num_args()>0){
            $edit = func_get_arg(0);
            $tid = func_get_arg(1);
            $data = array("ticket_for"=>$edit);
            $where = "ticket_id=".$tid; 
            try {
                $result = $this->update($data,$where);
                if($result){
                    return $result;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }else{
            throw new Exception("Argument not passed");
        }
    }
    
     /**
     * Developer    : Vivek Chaudhari
     * Date         : 25/09/2014
     * Description  : ticket details for store
     */
    public function ticketSaleDetails(){
        
            $select = $this->select()
                      ->from(array('t' => 'ticket_system'))
                      ->setIntegrityCheck(false)
                      ->joinLeft(array('c' => 'contests'), 't.ticket_id = c.ticket_id',array('c.sports_id','c.con_status'))
                      ->where('t.selling_status = 1')
                      ->where('c.con_status = 0');
        
        
              try {
                  $result = $this->getAdapter()->fetchAll($select);
                  if($result){
                    return $result;
                  }
              } catch (Exception $exc) {
                  echo $exc->getTraceAsString();
              }
        }
    
     /**
     * Developer    : Bhojraj Rawte
     * Date         : 29/09/2014
     * Description  : get user ticket details
     */
     public function getUserTickets() {
        if (func_num_args() > 0) {
            $userID = func_get_arg(0);
            try {
//                $select = $this->select()
//                        ->from($this)
//                        ->where(new Zend_Db_Expr('valid_upto >=  NOW()'))                        
//                        ->where('ticket_for LIKE ?', '%' . $userID . '%');
                     $select = $this->select()
                      ->from(array('t' => 'ticket_system'),array('code','bonus_amt','selling_status','fpp','ticket_id','ticket_for','valid_upto'))
                      ->setIntegrityCheck(false)
                      ->joinLeft(array('c' => 'contests'), 't.ticket_id = c.ticket_id',array('c.contest_name'))
                      ->where(new Zend_Db_Expr('t.valid_upto >=  NOW()'))                        
                        ->where('t.ticket_for LIKE ?', '%' . $userID . '%');
                      $result = $this->getAdapter()->fetchAll($select);

                if ($result) {
                    return $result;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        } else {
            throw new Exception("Argument not passed");
        }
    }
    
   /*
     * Name: Chandra Sekhara Reddy
     * Date: 13/09/2014
     * Description: Getting ticket details by ticket id
     */
   public function getTicketDetailsTicketById($tickets){
//       foreach ($tickets as $ticket_id){
       //echo"<pre>";print_r($tickets);echo"</pre>";die('test');
       $select = $this->select()
                      ->from($this)
                      ->where('ticket_id IN (?)',$tickets);
       
       $result = $this->getAdapter()->fetchAll($select);
      // }
//       echo"<pre>";print_r($result);echo"</pre>";die('test');
            if ($result) {
                return $result;
            }
                     
   }    
    
         /**
     * Developer    : vivek Chaudhari
     * Date         : 19/12/2014
     * Description  : get ticket details by ticket code
     * @params      : ticket code
     */
    public function getTicketByCode(){
        if(func_num_args()>0){
            $tcode= func_get_arg(0);
                $select = $this->select()                        
                        ->from($this)
                        ->where('code=?',$tcode);
                try {
                    $result = $this->getAdapter()->fetchRow($select);
       
                    if ($result) {
                        return $result;
                    }else{
                        return 0;
                    }
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
        }else{
               throw new Exception('Argument Not Passed'); 
            }
        }
}