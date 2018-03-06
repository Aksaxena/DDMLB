<?php
class Application_Model_Notification extends Zend_Db_Table_Abstract
{
    private static $_instance = null;
    protected $_name = 'notification';
    
    private function __clone() {
        //Avoid cloning
    }
    //singleton pattern
    public static function getInstance(){
        if(!is_object(self::$_instance))
            self::$_instance = new Application_Model_Notification();
        return self::$_instance;
    }
    
    /*
         * Developer    :   Vivek Chaudhari
         * Date         :   03/07/2014
         * Description  :  Insert notification
         * @params       :  param1 <array> = data array consisting message, send_by ,send_to values
         */
    public function insertNotification(){
        if(func_num_args()>0){
            $data = func_get_arg(0);
            try {
                $this->insert($data);
            } catch (Exception $exc) {
                throw new Exception($exc);
            }
        }else{
            throw new Exception("Argument not passed");
        }
    }
  /*
         * Developer    :   Vivek Chaudhari
         * Date         :   03/07/2014
         * Description  :   get notification depending on userId
         *@params       : param1<int> = session user id
         */
     public function getNotification(){
         if(func_num_args()>0):
             $userId = func_get_arg(0);
             $select = $this->select()
                     ->from($this,array('message','notification_id','sent_on','contest_id'))
                     ->where('send_to=?',$userId)
                     ->where('status=?',1)
                     ->where('accept!=?',2);
             $result = $this->getDefaultAdapter()->fetchAll($select);
             if($result):
                 return $result;
             endif;
         endif;
         
     }
     
     
     /*
         * Developer    :   Vivek Chaudhari
         * Date         :   04/07/2014
         * Description  :   (Dynamic update)
         * @params <int>: param1 = updating data column,    param2 = updating data value,
         *                param3 = condition column,         param4 = condition column value
         */
     public function updateCondition(){
         if(func_num_args()>0):
            $dataColumn = func_get_arg(0);
            $dataValue = func_get_arg(1);
            $condColumn = func_get_arg(2);
            $condValue = func_get_arg(3);
            
            $data = array($dataColumn=>$dataValue);
            $where = $condColumn.'='.$condValue;
            
            $result = $this->update($data ,$where);
            return 1;
         else:
             throw new Exception('Argument not passed');
        endif;
     }
     //dev:priyanka varanasi
     //description:to show the count  of unread notification whose status is '1' in header
     //date:25/10/2014
//     public function countofUnreadNotification(){
//          
//          if(func_num_args()>0):
//          $userId = func_get_arg(0);
//           //print_r($userId);die('test');
//           $select = $this->select()
//                     ->from( $this, 'COUNT(*)')                    
//                     ->where('status = ?',1)
//                     ->where('send_to=?',$userId);
//        $rows =  $this->getAdapter()->fetchOne($select);
//         //print_r($rows);die('test');
//        return($rows);
//           endif;
//       
//     }
     //dev:priyanka varanasi
     //description:to update the count value clicking on the notification 
     //date:25/10/2014
     public function updateReadNotification($notid, $userId){
          if(func_num_args()>0):
          $notId = func_get_arg(0);
          //print_r($notId);
          $userid= func_get_arg(1);
           $data['status'] = 0;
                  try {
                   $update = $this->update($data,"notification_id=".$notId,"user_id = ".$userid);
//                         $select = $this->select()
//                        ->from("notification", array("num"=>"COUNT(*)"))
//                        ->where('status=?',1);
//                 $checkrequest = $this->getDefaultAdapter()->fetchAll($select);
                   return $update;
                    
               } 
               catch (Exception $exc) {
                    throw new Exception('Unable to update, exception occured'.$exc);
                }
                    return $update;
                else:
                 throw new Exception('Argument not passed');
            endif;
   }  
          
}

?>