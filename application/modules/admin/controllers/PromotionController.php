<?php
/**
 * PromotionController
 *
 * Name: Abhinish Kumar Singh
 * Date: 16/07/2014
 * Description: This controller contains various actions for contest promotion
 *              section.
 */
require_once 'Zend/Controller/Action.php';
require_once '../vendor/autoload.php';

class Admin_PromotionController extends Zend_Controller_Action {



    public function init() {     
        
    }

    /*
     * Name: Abhinish Kumar Singh
     * Date: 16/07/2014
     * Description: This action sets up the view data for contest-promotion
     */
    public function contestPromotionAction() {          
   
		$objUserModel =  Admin_Model_Users::getInstance();
		$objPromotions = Admin_Model_Promotions::getInstance();
		$objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
		
        $result = $objPromotions->getPromotions();
        if($result){
			$this->view->promotion = $result;
        }
		if($this->getRequest()->isPost()) {  
		
			$promotion_url = $this->getRequest()->getPost('promotion_id');
			if(!empty($promotion_url)){
				
				$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
				$postmark_config = $config->getOption('postmark');
				
				$userDetails = $objUserModel->getUsersEmailsDeatils();
				
				if(!empty($userDetails)){
					
					$message = "Welcome to DraftDaily.com You are invited to Take Part in this Special Promotion";
					$subject = "Draftdaily Promotion";
					$link = 'https://'.$this->_appSetting->host.'/'.$promotion_url;
					
					$client = new Postmark\PostmarkClient($postmark_config['key']);
					//$user['email'] = "princekd07pk@gmail.com";
					//$user['user_name'] = "Prince";
					foreach($userDetails as $user){
						
						try{
							$result = $client->sendEmailWithTemplate(
								$postmark_config['email'],
								$user['email'],
								$postmark_config['promotion'], 
								[
									"message" => $message,
									"username" => $user['user_name'],
									"promotion_link" => $link,
									"subject"=>$subject
								] 
							);
						} catch (Exception $e){
						   
						}
					}
					$this->view->success = 1;
				}
			}    
        }
    }
    
    /*
     * Name: Abhinish Kumar Singh
     * Date: 16/07/2014
     * Description: This actions edits the contest promotions data as set by the user
     */
    public function editContestPromotionAction() {    
        
        $id = $this->getRequest()->getParam('preid');
        $objPromotions = Admin_Model_Promotions::getInstance();
        $getDetails = $objPromotions->getPromotionsDetailsById($id);
        
        if ($this->getRequest()->isPost()):
            $data = array();
                  $data['promotion_content'] = $this->getRequest()->getPost('promotion_content');
                  $data['status'] = $this->getRequest()->getPost('status');
            $result = $objPromotions->updatePromotionDetails($id, $data);
           // print_r($result);die;
            if($result){    
                 $this->view->success=$result;
          
            // $this->_redirect('/admin/contest-promotion');
           
            }
           endif;
        
        if($getDetails):
            $this->view->editpromotion = $getDetails;
       
        endif;
   
    }
    
    
    /*
     * Name: Abhinish Kumar Singh
     * Date: 16/07/2014
     * Description: This action allows the admin to add contest-promotions
     */
    public function addContestPromotionAction() {          
   
        $objPromotions = Admin_Model_Promotions::getInstance();
        
        if ($this->getRequest()->isPost()) :		
			if ($this->getRequest()->getPost('method') =="file_upload" && !empty($_FILES)) {	
				$_FILES['file']['name'] = time()."_".$_FILES['file']['name'];    
				$upload = new Zend_File_Transfer();	
				$files = $upload->getFileInfo();	
				$res = array();	
				$destination =  getcwd() .'/assets/images/promotion_img';
				$destination = str_replace('\\', "/", $destination);
				$upload->setDestination($destination);	
				if($upload->receive()){		
					$data['path'] = '/assets/images/promotion_img/'.$_FILES['file']['name'];	
				}			
				echo json_encode($data); die;
			}else{		
				$data = array();	
				$data['promotion_url'] = strtolower(str_replace(" ","-",$this->getRequest()->getPost('promotion_display_name')));	
				$data['promotion_display_name'] = $this->getRequest()->getPost('promotion_display_name');			
				$data['promotion_content'] = $this->getRequest()->getPost('promotion_content');	
				
				$data['status'] = $this->getRequest()->getPost('status');	
				$response = $objPromotions->addPromotionDetails($data);	
				if($response):	
					$this->view->success = $response;	
				endif;			
			}			
		endif;
    }

}
