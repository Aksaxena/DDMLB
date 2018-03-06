<?php
	
	/**
	* ApiController
	*
	* @author
	* @version
	*/
	
	require_once 'Zend/Controller/Action.php';
	require_once '../vendor/autoload.php';

	class Admin_ApiController extends Zend_Controller_Action {
	 
		public function appUserDetailsAction() {          
			$objappUserModel =  Admin_Model_AppUsers::getInstance();
			$appUserDetails = $objappUserModel->getAppUsersDeatils();
			$this->view->appUserDetails = $appUserDetails;     
		} 
		
		public function userAppListAction() {          
			$objApiAppsModel =  Admin_Model_ApiApps::getInstance();
			$id = $this->getRequest()->getParam('user_id');
			$appDetails = $objApiAppsModel->getAppsByUserId($id);
			$this->view->appDetails = $appDetails;     
		} 
		
		public function appUserEditAction() {          
			$objappUserModel =  Admin_Model_AppUsers::getInstance();
			$id = $this->getRequest()->getParam('user_id');
			if($id){
				$appUserDetails = $objappUserModel->getAppUserById($id);
				if(!empty($appUserDetails)){
					$this->view->appUserDetails = $appUserDetails; 
				}else{
					$this->redirect('/admin/api/app-user-details');
				}			    
			}
			if($this->getRequest()->isPost()) {  
				$status = $this->getRequest()->getPost('status');
				$appUserDetails = $objappUserModel->getupdate($id,$status);
				$this->redirect('/admin/api/app-user-details');
			}			
		}
		
		public function changeAppStatusAction() {          
			$objApiAppsModel =  Admin_Model_ApiApps::getInstance();
			$user_id = $this->getRequest()->getParam('user_id');
			$app_id = $this->getRequest()->getParam('app_id');
			if($app_id){
				$appDetails = $objApiAppsModel->getAppById($app_id);
				if(!empty($appDetails)){
					$this->view->appDetails = $appDetails; 
				}else{
					$this->redirect('/admin/user/app-list/'.$user_id);
				}			    
			}
			if($this->getRequest()->isPost()) {  
				$status = $this->getRequest()->getPost('status');
				$ApiApps = $objApiAppsModel->getAppUpdate($app_id,$status);
				$this->redirect('/admin/user/app-list/'.$user_id);
			}
		}    
	}
?>