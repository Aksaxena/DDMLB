<?php
/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';
require_once '../vendor/autoload.php';

class Admin_UserController extends Zend_Controller_Action {



    public function init() {     
        
    }

 
    public function userDetailsAction() {          
    $objUserModel =  Admin_Model_Users::getInstance();
    $userDetails = $objUserModel->getUsersDeatils();
//    echo "<pre>"; print_r($userDetails); echo "</pre>";die;
    $this->view->users = $userDetails;    
    }
    
    /**
    * Developer : Bhojraj Rawte
    * Date : 10/03/2014
    * Description : Edit User details
    */
    public function editUserAction(){

        $objUserModel = Admin_Model_Users::getInstance();
        $objCountryModel = Admin_Model_Countries::getInstance();
        $objStatesModel = Admin_Model_States::getInstance();
        
        $userID = $this->getRequest()->getParam('uid');
        
        $countryList = $objCountryModel->getCountries();
        $stateList = $objStatesModel->getStates();
        
        if ($this->getRequest()->isPost()) :
            $userdata = array();
            $userdata['email'] = $this->getRequest()->getPost('email');
            $userdata['country_id'] = $this->getRequest()->getPost('country_name');
            $userdata['state_id'] = $this->getRequest()->getPost('state_code');
            $password = $this->getRequest()->getPost('new_password');
             $check = $objUserModel->updateUserDetails($userID, $userdata); 
            if (trim($password) != null or trim($password) != ""){
                $userdata['password'] = md5($this->getRequest()->getPost('new_password'));
           
//          echo "<pre>"; print_r($userdata); echo "</pre>"; die;
            $check = $objUserModel->updateUserDetails($userID, $userdata);  
            if($check){
                $this->_redirect('/admin/user-details');
            }
            
            $userID = $this->getRequest()->getParam('uid');
            $this->view->success = 1;
            }
            else{
              $this->view->unsuccess="Please enter Password";  
            }
        endif;
//          * Developer     : Nikhil Aggarwal   
//          * Date          : 04/09/2014
//           * Description   : Showing error message when user is not given any password
//          **/
      
        $user = $objUserModel->getUsersDeatilsByID($userID);        
        $this->view->user = $user;
        $this->view->countryList = $countryList;
        $this->view->stateList = $stateList;
    }
    
    /**
    * Developer : Bhojraj Rawte
    * Date : 20/05/2014
    * Description : User Account details
    */
    public function userAccountDetailsAction(){
     $objUserAccountModel = Admin_Model_UserAccount::getInstance();   
     $accountDetails = $objUserAccountModel->getUserAccountsDeatils();
     if($accountDetails){
         $this->view->userAccountDetails = $accountDetails;
     }
    }    
    
    
    public function editAccountDetailsAction(){
     $objUserAccountModel = Admin_Model_UserAccount::getInstance(); 
        $userID = $this->getRequest()->getParam('uid');
        $userDetails = $objUserAccountModel->getUserAccountsDeatilsByID($userID);
        
        if ($this->getRequest()->isPost()) :
         $userdata = array();
            $userdata['balance_amt'] = $this->getRequest()->getPost('balance');
            $userdata['fpp'] = $this->getRequest()->getPost('fpp');
            $userdata['bonus_amt'] = $this->getRequest()->getPost('bonus');
            
            $check = $objUserAccountModel->updateUserAccountDetails($userID, $userdata);  
            if($check){
                $this->view->success=$check;
                //$this->_redirect('/admin/user-account-details');
            }   
        endif;
        if($userDetails){
            $this->view->user = $userDetails;
        }
        
    }
    
    public function mailerAction(){
        
        $objUserModel = Admin_Model_Users::getInstance();
        $mailer = Engine_Mailer_Mailer::getInstance();
        $objEmaillog =Admin_Model_Emaillog::getInstance();
        
        $userDetails = $objUserModel->getUsersDeatils();
        $this->view->userdetails = $userDetails;  
        
        if ($this->getRequest()->isPost()){
            $this->_helper->_layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(TRUE);
            $method = $this->getRequest()->getPost('method');

            switch($method){
            case "sendmail":
			
					$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
					$postmark_config = $config->getOption('postmark');
		
                    $email = $this->getRequest()->getPost('email');
                    $subject = $this->getRequest()->getPost('subject');
                    $message = $this->getRequest()->getPost('message');
                    $username = $this->getRequest()->getPost('username');
                    
                    $useremail = $email;            
                    $message = $message; 
                    $subject = $subject;
                    $topic = "Message from DraftDaily Support";
                    $template_name = 'Support';
					
					try{
        				$client = new Postmark\PostmarkClient($postmark_config['key']);
        				
        				$result = $client->sendEmailWithTemplate(
        					$postmark_config['email'],
        					$useremail,
        					$postmark_config['Support'], 
        					[
        						"message" => $message,
        						"usermail" => $useremail,
        						"topic" => $topic,
								"subject"=>$subject
        					] 
        				);
        			} catch (Exception $e){
        			   print_r($e); die;
        			}
					
                    $response = new stdClass();
                    if(isset($result)){
                        if(isset($result->message) && $result->message!="OK"){
                            $response->code = "198";
                            $response->message = "Unable to send message :-".$result->message;
                            echo json_encode($response);
                        }else{
                            $insertdataemaillog = array(
                                'sent_email' => $email,
                                'sent_time' => date('Y-m-d H:i:s'),
                                'sent_template'=>$template_name,
                                'message'=>$subject
                            );  

                            $insertdataemaillog_result = $objEmaillog ->insertEmailLog($insertdataemaillog);
                            $response->code = "200";
                            $response->message = "Message Sent Successfully";
                            echo json_encode($response);
                        }
                    }else{
                        $response->code = "198";
                        $response->message = "Error Occurred, Unable to send message";
                        echo json_encode($response);
                    }               
                break;
            
            }
        }
        $emaillogDetails =$objEmaillog->getAllEmailLog();
        $this->view->emaillogDetails=$emaillogDetails;
        $findArr = array();
        array_walk($emaillogDetails, function($value, $key) use(&$findArr,$userDetails) {
            foreach ($userDetails as $heystack){
                if (array_search($value['sent_email'], $heystack)) {
                    array_push($findArr, $key);
                }
            }
        });
        $findArr = array_flip($findArr);
        $diffArr = array_diff_key($emaillogDetails,$findArr);  
//         echo "<pre>"; print_r($diffArr); echo "</pre>"; //die;
//        $diffArr = array_unique($diffArr);
//         echo "<pre>"; print_r($diffArr); echo "</pre>"; die;
       $this->view->unregister=$diffArr; 
    }
     public function manageBotsAction(){
        $objUserModel =  Admin_Model_Users::getInstance();
   
     if(!empty($_POST))
        {
//            print "Post Data:\n<pre>";print_r($_POST);print "</pre>";die();
            
            $type = $_POST['type'];
             if(isset($type) && $type!= -1){
          $userDetails = $objUserModel->getBotDetails() ;
        
             }
             else{
                 $userDetails = $objUserModel->getUsersDeatils();
          
             }
        }
      else {
           $userDetails = $objUserModel->getUsersDeatils();
      }
      if( $userDetails) :
    $this->view->users = $userDetails; 
      endif;
    }
    
     public function manageBotsAjaxAction(){
//         print_r($_POST);die("post");
        $objUserModel = Admin_Model_Users::getInstance();
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
         $reply = new stdClass();
         $method = $this->getRequest()->getPost('method');
        $userID = $this->getRequest()->getPost('uid');
       // echo "<pre>"; print_r($userID); echo "</pre>"; die;
        switch($method){
            case "vpass" :
            $userdata = array();
            $tpass = $this->getRequest()->getPost('tpass');
            
                if (trim($tpass) != null or trim($tpass) != ""){
                $userdata['fb_pwd'] = md5($this->getRequest()->getPost('tpass'));
                 $check = $objUserModel->updateUserDetails($userID, $userdata);  
               
            $reply->message = 'Sucessfully Password Update';
            $reply->code = 200;
        echo json_encode($reply);
          
                
            }
            
            else 
            {
                 $reply->message = 'Enter Password ';
                        $reply->code = 198;
                        echo json_encode($reply);
            }
            break;
        }
     }

     //vivek chaudhari (30Aug2015) => log details of reward point transaction users
     
     public function fppExchangeLogAction(){
         $settingsModel = Admin_Model_Settings::getInstance();
         $usrTrxnModel = Admin_Model_UserTransactions::getInstance();
         
         
         
         $userTxnData = $usrTrxnModel->getFppTxnDeatils();
//         echo "<pre>"; print_r($userTxnData); die;
         if(!empty($userTxnData)){
             $this->view->userlog = $userTxnData;
         }
         
         if($this->getRequest()->isPost()){
             $postData = $this->getRequest()->getPost();
             if(isset($postData['setting_id'])){
                 $settingId = $postData['setting_id'];
                if(intval($postData['fpp_exchange'])){
                $updateData['fpp_exchange'] = $postData['fpp_exchange'];
                $settingsModel->updateSettingsDeatils($updateData,$settingId);
                }
             }
         }
         $settings = $settingsModel->getSettingsDeatils();
         $this->view->settings = $settings;
     }
}
