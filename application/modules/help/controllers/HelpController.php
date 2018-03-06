<?php
/**
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class Help_HelpController extends Zend_Controller_Action {



    public function init() {
		// set dfs amount balance 
		$res_data = $this->dfs_curl();
		$this->view->dfsBalance = $res_data;
    }
	
	public function dfs_curl() {
		 
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$dfsAddress = $config->getOption('dfs');
		
		$url ="http://139.162.189.133:3001/ext/getbalance/".$dfsAddress['admin-dfs-address'];
		
		$ch = curl_init();
		//echo $data['txid']; die;
		curl_setopt($ch, CURLOPT_URL, $url);	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 		
		curl_setopt($ch, CURLOPT_HEADER, false);		
		$result = curl_exec($ch);
		if($result === false){
			//die('Curl failed ' . curl_error());
		}	
		curl_close($ch);
		return $result;
    }

    public function howToPlayAction(){
       
    }
    
    public function contestRulesAction(){
        
        $id = $this->getRequest()->getParam('id');
        $this->view->id = $id;
    }
    public function faqAction(){
        
    }
    
    public function contestLobbyAction(){
        
    }
    public function termsOfUseAction(){
        
    }
    public function privacyNoticeAction(){
        
    }
    public function whyIsItLegelAction(){
        
    }
    
    public function affiliatesAction(){
//        $username = $this->getRequest()->getParam('user');
//        
//        $objUserModel = Application_Model_Users::getInstance();
//        $user = $objUserModel->getUseridByName($username);
//        
//        if($user){
//            $this->view->affuser = $user['user_id'];
//            $this->_redirect('/signup/'.$user['user_id']);
//        }
    }
    public function referAFriendAction(){
        
    }
    public function playbookAction(){
        
    }
    
    public function depositAction(){
        
    }
    public function careersAction(){
        
    }
    /*dev:priyanka varanasi 
     * desc:added action for static page
     * date:31/7/2015
     */
       public function aboutUsAction(){
        
    }
    
       public function whyDraftoffAction(){
        
    }
        public function depositNoticeAction(){
        
    }
}
