<?php
require_once 'Zend/Controller/Action.php';

class User_NotificationController extends Zend_Controller_Action
{
    public function init(){
        
    }
    
    /*
         * Developer    :   Vivek Chaudhari
         * Date         :   03/07/2014
         * Description  :  Common action to perform operations for notification like update & insert
         * 
         */
    public function addNotificationAction(){
            $this->_helper->layout()->disableLayout();
            
            //dummy values for inserting notification
            $data = array();
            $data['send_by'] = 8; //$this->getRequest()->getParam('send_by');
            $data['send_to'] = 3;  //$this->getRequest()->getParam('send_to');
            $data['message'] = 'This is sample notification 2 ';     //$this->getRequest()->getParam('message');
            $data['sent_on'] = date('Y-m-d H:i:s');
            $data['contest_id'] = 65;
            $objModelNotify = Application_Model_Notification::getInstance();
//            $objModelNotify->insertNotification($data);
            
            
            if($this->getRequest()->isPost()):
                 $condition = $this->getRequest()->getParam('condition');
//              seitch case is used because of dynamic update function, depending on condition it uses update function
//              dynamic update function depending upon condition values and data values  
                 switch($condition):
                    
                     case 'updateAcceptByNotificationId' : 
                         
                         $status = $this->getRequest()->getParam('status');
                         $notifyId = $this->getRequest()->getParam('notifyId');
                         
                         if(isset($status)&&isset($notifyId)):
                            $condColumn = 'notification_id';  //table column where you want to pu condition
                            $dataColumn = 'accept';           //column where you want to change data

                            $condValue = $notifyId;          //condition column value
                            $dataValue = $status;           //data column value to change
                            
                            $check = $objModelNotify->updateCondition($dataColumn,$dataValue,$condColumn,$condValue);
                            if($check):
                               echo $condValue;
                            endif;
                        endif;
                         
                     break;
                     
                     case 'updateStatusBySendBy' : 
                         
//                         $getCondValue = $this->getRequest()->getParam('');
//                         $getDataValue = $this->getRequest()->getParam('');
                         
                         if(isset($getCondValue)&&isset($getDataValue)):
                            $condColumn = 'send_by';  //table column where you want to pu condition
                            $dataColumn = 'status';           //column where you want to change data

                            $condValue = $getCondValue;          //condition column value
                            $dataValue = $getDataValue;           //data column value to change
                            
                            $check = $objModelNotify->updateCondition($dataColumn,$dataValue,$condColumn,$condValue);
                            if($check):
                               echo $condValue;
                            endif;
                        endif;
                         
                     break;
                     
                     case 'updateAcceptBySendTo' : 
                         
//                         $getCondValue = $this->getRequest()->getParam('');
//                         $getDataValue = $this->getRequest()->getParam('');
                         
                         if(isset($getCondValue)&&isset($getDataValue)):
                            $condColumn = 'send_to';  //table column where you want to pu condition
                            $dataColumn = 'accept';           //column where you want to change data

                            $condValue = $getCondValue;          //condition column value
                            $dataValue = $getDataValue;           //data column value to change
                            
                            $check = $objModelNotify->updateCondition($dataColumn,$dataValue,$condColumn,$condValue);
                            if($check):
                               echo $condValue;
                            endif;
                        endif;
                         
                     break;
                  endswitch;
            endif;
           
     }
     
     /*
         * Developer    :   Vivek Chaudhari
         * Date         :   03/07/2014
         * Description  :  get session user notification
         * 
         */
    public function userNotificationAction(){
        $this->_helper->layout()->disableLayout();
        
        $userId = $this->view->session->storage->user_id;
        $objModelNotify = Application_Model_Notification::getInstance();
        
        $result = $objModelNotify->getNotification($userId);
        if($result):
           
            $this->view->notification = $result;
       
       
         endif;  
//    }
    }
     //dev:priyanka varanasi
     //description:action for updating the status of the notification  
     //date:25/10/2014
       public function userReadNotificationAction(){
        $notid = $this->getRequest()->getParam('notid');
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $userId = $this->view->session->storage->user_id;
        $objModelNotify = Application_Model_Notification::getInstance();
        $result =  $objModelNotify->updateReadNotification($notid,$userId);
        $notifications = $objModelNotify->getNotification($userId);
        $response = new stdClass();
        $response->count =0;
        if($notifications){
            $this->view->session->storage->notecount = count($notifications);
            $response->count  = count($notifications);
        }
        else{
             $this->view->session->storage->notecount = 0;
        }
            
        if($result){
            $response->code = 200;
            $response->data = $result;
        }
        echo json_encode($response,true);
      }
}

?>
