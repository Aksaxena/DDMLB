<?php

/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';
require_once '../vendor/autoload.php';

class Authentication_AuthenticationController extends Zend_Controller_Action {

    public function init() {      
		/* set dfs amount balance  */
		$res_data = $this->dfs_curl();
		$this->view->dfsBalance = $res_data;
    }

    public function indexAction() {
        
    }
	
	public function dfs_curl() {
		 
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$dfsAddress = $config->getOption('dfs');
		
		$url ="http://139.162.189.133:3001/ext/getbalance/".$dfsAddress['admin-dfs-address'];
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 		
		curl_setopt($ch, CURLOPT_HEADER, false);		
		$result = curl_exec($ch);
		if($result === false){
			
		}	
		curl_close($ch);
		return $result;
    }
	
	public function addCsAccount(){
        if(func_num_args() > 0){
            $objAccntModel = Application_Model_Accounts::getInstance();
            $data = func_get_arg(0);
            $email = $data['email'];
            $username = $data['user_name'];
            $password = $data['password'];
            
            $accData['name'] = $username;
            $accData['email'] = $email;
            $accData['password'] = $password;
            $accData['datecreated'] = date('Y-m-d');
            $accData['ip'] = $_SERVER['REMOTE_ADDR'];
            $accData['host'] = $_SERVER['HTTP_HOST'];
            $accData['status'] = "Active";
            $accData['acctype'] = "Customer";
			
            $objAccntModel->insertAccount($accData);
        }
    }
	
	function verifyCaptcha($captcha){
		/* get verify response data */
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$google_config = $config->getOption('google-capcha');
		$fields = array(
			'secret'    =>  $google_config['secret-key'],
			'response'  =>  $captcha,
			'remoteip'  =>  $_SERVER['REMOTE_ADDR']
		);
		$ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		$responseData = json_decode(curl_exec($ch));
		curl_close($ch);
		return $responseData;
	}
	
    public function signupAction() {
 
        $affuser = $this->getRequest()->getParam('affuser');
		
        $config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$postmark_config = $config->getOption('postmark');	
		
		$google_config = $config->getOption('google-capcha');
		$this->view->site_key = $google_config['site-key'];
		
        if ($this->view->auth->hasIdentity()) {
            $this->_redirect('/home');
        }
		
        $objAffiliateModel = Application_Model_Affiliate::getInstance();
        $objUserModel = Application_Model_Users::getInstance();
        $objUserAccountModel = Application_Model_UserAccount::getInstance();
        $objCountryModel = Application_Model_Countries::getInstance();
        $objStatesModel = Application_Model_States::getInstance();
        $objSecurity = Engine_Vault_Security::getInstance();
        $objReferalModel = Static_Model_Referals::getInstance();
        $objFacebookModel = Engine_Facebook_Facebookclass::getInstance();
       
		$objEmaillog =Application_Model_Emaillog::getInstance();
        $objSettingsModel = Application_Model_Settings::getInstance();
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
        $objReferalModel = Static_Model_Referals::getInstance();
        $countryList = $objCountryModel->getCountries();
        foreach($countryList as $ckey=>$country){
            if($country['country_code'] == "US"){
                $temp = $countryList[0];
                $countryList[0] = $country;
                $countryList[$ckey] = $temp;
            }
        }
	/******************user Ip address *******************/
        	$client  = @$_SERVER['HTTP_CLIENT_IP'];
            $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
            $remote  = $_SERVER['REMOTE_ADDR'];
        
            if(filter_var($client, FILTER_VALIDATE_IP))
            {
                $ipAdd = $client;
            }
            elseif(filter_var($forward, FILTER_VALIDATE_IP))
            {
                $ipAdd = $forward;
            }
            else
            {
                $ipAdd = $remote;
            }
            $url = json_decode(file_get_contents("http://api.ipinfodb.com/v3/ip-city/?key=be5336879b02e51ead35a86b5c74fc447c629f3dd5f521b60a6ac4b3da1fe5af&ip=182.68.73.37&format=json"));
            //echo "<pre>"; print_r($url); die;
            if(isset($url) && !empty($url))
            {
                $this->view->userip             = $ipAdd;
                $this->view->CurrentCity        = $url->cityName;
                $this->view->CurrentState       = $url->regionName;
                
                $stateId = $objStatesModel->getStateIdByStateName($url->regionName);
                if(isset($stateId)){
                    $this->view->CurrentStateCode       = $stateId['id'];
                }
                
                
            }
	/*********************end here ***********************/
        $stateList = $objStatesModel->getStateByCountry($countryList[0]['country_id']);
        
        $this->view->countryList = $countryList;
        $this->view->stateList = $stateList;
        
        $this->_settings = $objSettingsModel->getSettings();

        if(isset($affuser) && $affuser!=""){
            $affuser = base64_decode($affuser);
            $this->view->affuser = $affuser;
        }

        $url = $objFacebookModel->getLoginUrl();
        $this->view->fbLogin = $url;
		
        if (isset($this->view->session->fbsession)) {
            $fbUserDetails = $objFacebookModel->getUserDetails();
            
            if ($fbUserDetails) {
                $email = "";
                $fbUserDetails = $fbUserDetails->asArray();

                $password = md5(strtotime(date('Y-m-d H:i:s')));
                $username = $fbUserDetails['first_name'];
                $fullname = $fbUserDetails['name'];
                if(isset($fbUserDetails['email'])){
                    $email = $fbUserDetails['email'];
                }
                $fbid = $fbUserDetails['id'];
                $fbToken = $objFacebookModel->gefbToken();
                $this->view->session->fbid = $fbid;
                $fbData = $objUserModel->checkFBUserExist($fbid, $email);

                if ($fbData) {
                    $fbupdatedata = array('fb_id' => $fbid,'fb_token'=>$fbToken);
					 
                    $objUserModel->updateFBID($fbupdatedata, $fbData['user_id']);
                    $authStatus = $objSecurity->authenticate($fbData['email'], $fbData['password']);
					
                } else {
                    $checkUserName = $objUserModel->validateUserName($username);
                    if ($checkUserName) {
                        $username.=rand(1, 99);
                    }
                    $fbinsertdata = array('user_name' => $username,
                        /* 'full_name' => $fullname, */
                        'password' => $password,
                        'email' => $email,
                        'fb_id' => $fbid,
                        'fb_token'=>$fbToken,
                        'status' => '1',
                        'email_verify_status' => '1',
                        'role' => '1',
                        'reg_date' => date('Y-m-d')
                    );
					
                    if(!isset($fbUserDetails['email'])){
                        $this->view->session->fbuserdetails = $fbinsertdata;
                         $this->_redirect('/facebookauth');
                    }
					
                    $insertionResult = $objUserModel->insertUser($fbinsertdata);
					
                    if ($insertionResult) {
                        $this->addCsAccount($fbinsertdata);
                            $link = $this->_appSetting->hostLink;            
                            $message = "The best place to play daily fantasy sports for prizes.";
                            $name = $this->_appSetting->appName;   
                            $description = $this->_settings['fb_desc'];    
                            $objFacebookModel->autopost($fbid,$fbToken,$link,$message,$name,$description);
                            $userBalance = array('user_id' => $insertionResult,
                                'bonus_amt' => 0);
                            $objUserAccountModel->insertBalance($userBalance);

                            $email = $email;
                            
                            $subject = 'Welcome Mail';
							
							
							$template_name = "Welcome_template";
							try{
								$client = new Postmark\PostmarkClient($postmark_config['key']);
								$result = $client->sendEmailWithTemplate(
									$postmark_config['email'],
									$email,
									$postmark_config['Welcome_template'], 
									[
										"site_name" => $postmark_config['site_name'],
										"username" => $username,
									] 
								);
							} catch (Exception $e){
							   print_r($e); die;
							}
							
							
                            $insertdataemaillog = array(
                                'sent_email' => $email,
                                'sent_time' => date('Y-m-d H:i:s'),
                                'sent_template' => $template_name,
                                'message' => $subject
                            );
                            $insertdataemaillog_result = $objEmaillog ->insertEmailLog($insertdataemaillog);
                            $authStatus = $objSecurity->authenticate($username, $password);
                            if ($authStatus) {
                                $this->_redirect('/deposit');
                            }
                        }
                }
                if ($authStatus) {
                    $this->_redirect('/deposit');
                }
            }
        }
        
        
        if ($this->getRequest()->isPost()) {
            $methodSelector = $this->getRequest()->getPost('formbutton');
			/* echo "<pre>"; print_r($this->getRequest()->getPost()); die; */
            if ($methodSelector == 'signup') {
				
				$captcha =  @$this->getRequest()->getPost('g-recaptcha-response');
				if(!empty($captcha)) {
					$responseData = $this->verifyCaptcha($captcha);
					if($responseData->success) {
						$username = $this->getRequest()->getPost('username');
						$email = $this->getRequest()->getPost('email');
						$walletAdd = $this->getRequest()->getPost('wallet_address');
						$password = md5($this->getRequest()->getPost('password'));
						$confirmPassword = md5($this->getRequest()->getPost('confirmPassword'));
						$cityoption = $this->getRequest()->getPost('cityoption');
						$address = $this->getRequest()->getPost('address');
						$dob = $this->getRequest()->getPost('dob');
						$countryoption = $this->getRequest()->getPost('countryoption');
						$agreeterms = $this->getRequest()->getPost('agreeterms');
						$ageconfirm = $this->getRequest()->getPost('ageconfirm');
						$affuserId = $this->getRequest()->getPost('affuser');
					   
						if (isset($username) && isset($email) && isset($address) && isset($dob) && isset($password) && isset($confirmPassword) && isset($countryoption) && isset($agreeterms) && isset($ageconfirm)) {

							if ($agreeterms == 'on' && $ageconfirm == 'on' && $password == $confirmPassword) {

								$data = array('user_name' => $username,
									'password' => $password,
									'email' => $email,
									'wallet_address' => $walletAdd,
									'address' => $address,
									'dob' => $dob,
									'state_id' => $cityoption,
									'country_id' => $countryoption,
									'status' => '1',
									'role' => '1',
									'reg_date' => date('Y-m-d')
								);
								/* echo "<pre>"; print_r($data); die; */
								$insertionResult = $objUserModel->insertUser($data);
								
								if ($insertionResult) {
									$this->addCsAccount($data); 
									
									$userBalance = array('user_id' => $insertionResult,
										'bonus_amt' => 0);
									$objUserAccountModel->insertBalance($userBalance);
									
									$activationKey = base64_encode($insertionResult . '@' . $random = mt_rand(10000000, 99999999));
									$verificationLink = 'https://' . $this->_appSetting->host . '/verify-email/' . $activationKey;
									$objUserModel->updateActivationLink($activationKey, $insertionResult);
									
									
									$template_name = 'verify_email_template';                     
									$subject = 'Email Verification DraftDaily';
									
									try{
										$client = new Postmark\PostmarkClient($postmark_config['key']);
										
										$result = $client->sendEmailWithTemplate(
											$postmark_config['email'],
											$email,
											$postmark_config['verify_email_template'], 
											[
												"subject" => $subject,
												"username" => $username,
												"verification_link" => $verificationLink,
											] 
										);
									} catch (Exception $e){
									   
									}
									$this->view->message = 'Registration Successfully Done. Verification Link has been sent to your email Please Verify Your Email.';
									
									/* $authStatus = $objSecurity->authenticate($username, $password); */
									$insertdataemaillog = array(
										'sent_email' => $email,
										'sent_time' => date('Y-m-d H:i:s'),
										'sent_template' => $template_name,
										'message' => $subject
									);

									$insertdataemaillog_result = $objEmaillog ->insertEmailLog($insertdataemaillog);
									$status=1;
									$acceptance=1;
									$resultemail=$objReferalModel->validateReferFriendEmail($email);
									if(!empty($resultemail))
									{
										$updateStatus=$objReferalModel->updateReferFriend($email,$acceptance,$status);
									}
									/* affiliate */
									if(isset($affuserId) && $affuserId!=""){
										  /* add referal for affiliate */
										$referCheck = $objReferalModel->validateReferEmailByUser($email,$affuserId);
										if(empty($referCheck)){
											$referData['email'] = $email;
											$referData['ref_by'] = $affuserId;
											$referData['status'] = 1;
											$referData['acceptance'] = 1;
											$referData['ref_date'] = date('Y-m-d');
											$referData['req_count'] = 1;
											$objReferalModel->addReferal($referData);
										}
										  
										$useraffiliate = array('affiliate_user_id' => $affuserId,
															   'registred_user_id' => $insertionResult);
										$objAffiliateModel->insertAffiliate($useraffiliate);
									}
									$this->_redirect('/verify-email/sent');
									/* end affiliate */
									/* if ($authStatus) {
										$this->_redirect('/deposit');
									} */
								}
							}
						}
					}else{
						$this->view->errMsg = 'Robot verification failed, please try again.';
					}
				}else{
					$this->view->errMsg = 'Please click on the reCAPTCHA box.';
				}
            } else if ($methodSelector == 'login') {
                $username = $this->getRequest()->getPost('username');
                $password = md5($this->getRequest()->getPost('password'));      
                    if ($username != "" && $password != "") { 
                    $authStatus = $objSecurity->authenticate($username, $password);
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) { 
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            $this->_helper->layout()->disableLayout();
                            $this->_helper->viewRenderer->setNoRender(true);
                            if ($authStatus->code == 200) {
                                $response = new stdClass();
                                $response->message = 'success';
                                $response->code = 200;
                                echo json_encode($response);
                            } else if ($authStatus->code == 196) {

                                $response = new stdClass();
                                $response->message = 'Your account has been blocked. Please contact admin for further information.';
                                $response->code = 196;
                                echo json_encode($response);
                            } else if ($authStatus->code == 198) {

                                $response = new stdClass();
                                $response->message = 'No identity found,verify your credentials you have entered';
                                $this->view->message = 'No identity found,verify your credentials you have entered';
                                $response->code = 198;
                                echo json_encode($response);
                            }
                        } else {
                            if ($authStatus) {
                                $this->_redirect('/home');
                            }
                        }
                       
                    } else {

                        if ($authStatus) {
                            $this->_redirect('/home');
                        }
                    }
                } else {
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            $this->_helper->layout()->disableLayout();
                            $this->_helper->viewRenderer->setNoRender(true);
                            $response = new stdClass();
                            $response->message = 'Please enter valid username or email id  and password.';
                            $response->code = 198;
                            echo json_encode($response);
                        }
                    }
                }
            } else if ($methodSelector == 'reset') { 
                $email = $this->getRequest()->getPost('email');
                
                if ($email != "") { 
                    $result = $objUserModel->validateUserEmail($email);
                   
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                       
					   if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                          
							$this->_helper->layout()->disableLayout();
                            $this->_helper->viewRenderer->setNoRender(true);
							if($result){
 
                                $objCore = Engine_Core_Core::getInstance();
                                $this->_appSetting = $objCore->getAppSetting();


                                $userID = $result['user_id'];
                                $activationKey = base64_encode($result['user_id'] . '@' . $random = mt_rand(10000000, 99999999));
                                $link = 'https://' . $this->_appSetting->host . '/reset/' . $activationKey;
                                $objUserModel->updateActivationLink($activationKey, $userID);
                                $template_name = 'password-reset';
                                $username = $email;
                                $subject = 'Password Reset Mail';
								
								try{
									$client = new Postmark\PostmarkClient($postmark_config['key']);
									$result = $client->sendEmailWithTemplate(
										$postmark_config['email'],
										$email,
										$postmark_config['password_reset'], 
										[
											"site_name" => $postmark_config['site_name'],
											"username" => $username,
											"passwordlink"=>$link,
										] 
									);
								} catch (Exception $e){
								   //print_r($e); die;
								}
                              
                                $insertdataemaillog = array(
									'sent_email' => $email,
									'sent_time' => date('Y-m-d H:i:s'),
									'sent_template'=>$template_name,
									'message'=>$subject
								   
									
								);  
                                                          
								$insertdataemaillog_result = $objEmaillog ->insertEmailLog($insertdataemaillog);
                                if ($client) {
                                   
                                    $this->view->success = 'send';
                                }
                                $response = new stdClass();
                                $response->message = 'successfully submitted refer your email';
                                $response->code = 200;
                                echo json_encode($response);
                            } else {

                                $response = new stdClass();
                                $response->message = "Email id doesn't exist";
                                $response->code = 198;
                                echo json_encode($response);
                            }
                        }
                    }
                } else {

                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            $this->_helper->layout()->disableLayout();
                            $this->_helper->viewRenderer->setNoRender(true);
                            $response = new stdClass();
                            $response->message = 'Please enter email id.';
                            $response->code = 198;
                            echo json_encode($response);
                        }
                    }              
                }
            }elseif($methodSelector == 'getCityByCountry'){
				$objStatesModel = Application_Model_States::getInstance();
				$country_id = $this->getRequest()->getPost('country_id');
				$stateList = $objStatesModel->getStateByCountry($country_id);
				echo json_encode($stateList); die;
				 				
			}
        }
    }
  

    public function loginAction() {

        if ($this->view->auth->hasIdentity()) {
            $this->_redirect('/home');
        }
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$postmark_config = $config->getOption('postmark');	
		
        $objAffiliateModel = Application_Model_Affiliate::getInstance();
        $objEmaillog =Application_Model_Emaillog::getInstance();
        $objUserAccountModel = Application_Model_UserAccount::getInstance();
        $objUserModel = Application_Model_Users::getInstance();
        $objCountryModel = Application_Model_Countries::getInstance();
        $objStatesModel = Application_Model_States::getInstance();
        $objSecurity = Engine_Vault_Security::getInstance();
        
		$objReferalModel = Static_Model_Referals::getInstance();
       
		$objFacebookModel = Engine_Facebook_Facebookclass::getInstance();
        $url = $objFacebookModel->getLoginUrl();
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
        $this->view->fbLogin = $url;
		
		$objTwitterModel = Engine_Twitter_TwitterOAuth::getInstance();
		$request_token = $objTwitterModel->getRequestToken("https://draftdaily.com/home");
		$twitter_res = $objTwitterModel->getAuthorizeURL($request_token['oauth_token']);
		$this->view->twitter_res = $twitter_res;
		
        $countryList = $objCountryModel->getCountries();
		foreach($countryList as $ckey=>$country){
            if($country['country_code'] == "US"){
                $temp = $countryList[0];
                $countryList[0] = $country;
                $countryList[$ckey] = $temp;
            }
        }
        $stateList = $objStatesModel->getStateByCountry($countryList[0]['country_id']);
        $this->view->countryList = $countryList;
        $this->view->stateList = $stateList;

        if ($this->getRequest()->isPost()) {

            $methodSelector = $this->getRequest()->getPost('subbuttonpop');
           
            if ($methodSelector == 'signup') {

                $username = $this->getRequest()->getPost('username');
				
				/* $fname = $this->getRequest()->getPost('fname');
				$lname = $this->getRequest()->getPost('lname'); */
				
                $email = $this->getRequest()->getPost('email');
				$walletAdd = $this->getRequest()->getPost('wallet_address');
                $password = md5($this->getRequest()->getPost('password'));
                $confirmPassword = md5($this->getRequest()->getPost('confirmPassword'));
                $countryoption = $this->getRequest()->getPost('countryoption');
				
				/* $cityoption = $this->getRequest()->getPost('cityoption'); */
				
                $agreeterms = $this->getRequest()->getPost('agreeterms');
                $ageconfirm = $this->getRequest()->getPost('ageconfirm');
                
                $affuserId = $this->getRequest()->getPost('modalaffuser');
                if (isset($username) && isset($email) && isset($password) && isset($confirmPassword) && isset($countryoption)  && isset($agreeterms) && isset($ageconfirm)) {

                    if ($agreeterms == 'on' && $ageconfirm == 'on' && $password == $confirmPassword) {

                        $data = array('user_name' => $username,
                            /* 'fname'=>$fname,
                            'lname'=>$lname, */
							
                            'password' => $password,
                            'wallet_address' => $walletAdd,
                            'email' => $email,
                            'country_id' => $countryoption,
                            /* 'state_id' => $cityoption, */
                            'status' => '1',
                            'role' => '1',
                            'reg_date' => date('Y-m-d')
                        );

                        $insertionResult = $objUserModel->insertUser($data);


                        if ($insertionResult) {
                            
                            $userBalance = array('user_id' => $insertionResult,
                                'bonus_amt' => 0);
                            $objUserAccountModel->insertBalance($userBalance);
                            
                            $username = $this->getRequest()->getPost('username');
							
							$activationKey = base64_encode($insertionResult . '@' . $random = mt_rand(10000000, 99999999));
							$verificationLink = 'https://' . $this->_appSetting->host . '/verify-email/' . $activationKey;
							$objUserModel->updateActivationLink($activationKey, $insertionResult);
							
							
                            $template_name = 'verify_email_template';                     
                            $subject = 'Email Verification DraftDaily';
							
                            try{
								$client = new Postmark\PostmarkClient($postmark_config['key']);
								
								$result = $client->sendEmailWithTemplate(
									$postmark_config['email'],
									$email,
									$postmark_config['verify_email_template'], 
									[
										"subject" => $subject,
										"username" => $username,
										"verification_link" => $verificationLink,
									] 
								);
							} catch (Exception $e){
							   
							}

							/* $authStatus = $objSecurity->authenticate($username, $password); */
                            $insertdataemaillog = array(
                                'sent_email' => $email,
                                'sent_time' => date('Y-m-d H:i:s'),
                                'sent_template'=>$template_name,
                                'message'=>$subject
                            );  
                                                          
                            $insertdataemaillog_result = $objEmaillog ->insertEmailLog($insertdataemaillog);
                            $status=1;
                            $acceptance=1;
                            $resultemail=$objReferalModel->validateReferFriendEmail($email);
                             if(!empty($resultemail))
                             {
                                 $updateStatus=$objReferalModel->updateReferFriend($email,$acceptance,$status);
                             }
                             
                            /* affiliate */
                            if (isset($affuserId) && $affuserId != "") {
                                $useraffiliate = array('affiliate_user_id' => $affuserId,
                                                       'registred_user_id' => $insertionResult);
                                $objAffiliateModel->insertAffiliate($useraffiliate);
                            }
                            /* end affiliate */
							
                            $this->_redirect('/verify-email/sent');
                            /* if ($authStatus) {
                                $this->_redirect('/deposit');
                            } */
                        }
                    }
                }
            } else if ($methodSelector == 'login') {

                $username = $this->getRequest()->getPost('username');
                $password = md5($this->getRequest()->getPost('password'));

                if ($username != "" && $password != "") {
                    $authStatus = $objSecurity->authenticate($username, $password);
					
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            $this->_helper->layout()->disableLayout();
                            $this->_helper->viewRenderer->setNoRender(true);
							if($authStatus->code == 190){
								$response = new stdClass();
                                $response->message = 'Please Verify Your Email Address.';
                                $response->code = 190;
                                echo json_encode($response);
							}elseif ($authStatus->code == 200) {
                                $response = new stdClass();
                                $response->message = 'success';
                                $response->code = 200;
								/* add session value; */
								$objUserAccModel = Application_Model_UserAccount::getInstance();
								$objTicketModel = Application_Model_TicketSystem::getInstance();
								$userId = $this->view->session->storage->user_id;
								$accData = $objUserAccModel->getAccountByUserId($userId);
		
								$tcount = 0;
								$allTktDetails = array();
								if (isset($accData['available_tickets']) && $accData['available_tickets'] != null) {
									$ticketIds = json_decode($accData['available_tickets'], true);
									 
									if (is_array($ticketIds)) {
										foreach ($ticketIds as $tkey => $tval) {
											$tData = $objTicketModel->getTicketDetailsById($tval);
											$allTktDetails[] = $tData;
											$tcount++;
										}
									}
								}
								
								$this->view->session->storage->NoOfTickets = $tcount;
								$this->view->session->storage->TktDetails = $allTktDetails;
                                echo json_encode($response);
                            } else if ($authStatus->code == 196) {

                                $response = new stdClass();
                                $response->message = 'Your account has been blocked. Please contact admin for further information.';
                                $response->code = 196;
                                echo json_encode($response);
                            } else if ($authStatus->code == 198) {

                                $response = new stdClass();
                                $response->message = 'No identity found,verify your credentials you have entered';
                                $this->view->message = 'No identity found,verify your credentials you have entered';
                                $response->code = 198;
                                echo json_encode($response);
                            }
                        } else {
                            if ($authStatus) {
                                $this->_redirect('/home');
                            }
                        }
                        die;
                    } else {

                        if ($authStatus) {
                            $this->_redirect('/home');
                        }
                    }
                } else {
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            $this->_helper->layout()->disableLayout();
                            $this->_helper->viewRenderer->setNoRender(true);
                            $response = new stdClass();
                            $response->message = 'Please enter username and password.';
                            $response->code = 198;
                            echo json_encode($response);
                        }
                    }
                }
            } else if ($methodSelector == 'reset') { 
                $email = $this->getRequest()->getPost('email');
                
                if ($email != "") { 
                    $result = $objUserModel->validateUserEmail($email);
                    

                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            $this->_helper->layout()->disableLayout();
                            $this->_helper->viewRenderer->setNoRender(true);
                            if ($result) {

                                $objCore = Engine_Core_Core::getInstance();
                                $this->_appSetting = $objCore->getAppSetting();


                                $userID = $result['user_id'];
                                $activationKey = base64_encode($result['user_id'] . '@' . $random = mt_rand(10000000, 99999999));
                                $link = 'https://' . $this->_appSetting->host . '/reset/' . $activationKey;
                                $objUserModel->updateActivationLink($activationKey, $userID);
                                $template_name = 'password-reset';
                                $username = $result['user_name'];
                                $subject = 'Password Reset Mail';
                               
							   try{
									$client = new Postmark\PostmarkClient($postmark_config['key']);
									$result = $client->sendEmailWithTemplate(
										$postmark_config['email'],
										$email,
										$postmark_config['password_reset'], 
										[
											"site_name" => $postmark_config['site_name'],
											"username" => $username,
											"passwordlink"=>$link,
										] 
									);
								} catch (Exception $e){
								   
								}
								
                                $insertdataemaillog = array(
                                    'sent_email' => $email,
                                    'sent_time' => date('Y-m-d H:i:s'),
                                    'sent_template'=>$template_name,
                                    'message'=>$subject
                                );  
                                                          
								$insertdataemaillog_result = $objEmaillog ->insertEmailLog($insertdataemaillog);
                                if ($client) {
                                    $this->view->success = 'send';
                                }
                                $response = new stdClass();
                                $response->message = 'Password reset link sent to your email';
                                $response->code = 200;
                                echo json_encode($response);
                            } else {

                                $response = new stdClass();
                                $response->message = "Email id doesn't exist";
                                $response->code = 198;
                                echo json_encode($response);
                            }
                        }
                    }
                } else {

                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            $this->_helper->layout()->disableLayout();
                            $this->_helper->viewRenderer->setNoRender(true);
                            $response = new stdClass();
                            $response->message = 'Please enter email id.';
                            $response->code = 198;
                            echo json_encode($response);
                        }
                    }               
                }
            }

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                
            }
        }
    }

    public function logoutAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        if ($this->view->auth->hasIdentity()) {

            $this->view->auth->clearIdentity();

            Zend_Session::destroy(true);
            $this->_redirect('/home');

        }
    }

    public function ajaxhandlerAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $ajaxMethod = $this->getRequest()->getParam('ajaxMethod');
        if ($ajaxMethod) {

            switch ($ajaxMethod) {
				
                case 'validateUsername':
                    $userName = $this->getRequest()->getParam('username');
                    $formtype = $this->getRequest()->getParam('formtype');
                    $objUserModel = Application_Model_Users::getInstance();
                    $response = $objUserModel->validateUserName($userName);
                    if ($response) {
                        if ($formtype == "login") {
                            echo json_encode(true);
                        }
                        else {
                            $arr = array("Username already exists");
                            echo json_encode($arr);
                        }
                    } else {
                        echo json_encode(true);
                    }
                    break;
					
                case 'validateEmail':

                    $userEmail = $this->getRequest()->getParam('email');
                    $objUserModel = Application_Model_Users::getInstance();
                    $response = $objUserModel->validateUserEmail($userEmail);

                    if ($response) {
                        $arr = array("Email already exists");
                        echo json_encode($arr);
                    } else {
                        echo json_encode(true);
                    }
                    break;
  
                case 'changeStatus':
                    $userId = $this->view->session->storage->user_id;
                    $addressId = $this->getRequest()->getParam('addressid');
                    $objAddressModel = Application_Model_BillingAddress::getInstance();
                    $objAddressModel->changeStatus($userId);
                    $objAddressModel->changeStatusByAddressId($addressId);

                    break;

                case 'changeTracking' :
                    $accountId = $this->getRequest()->getParam('acId');
                    $status = $this->getRequest()->getParam('settings');
                    $objAddressModel = Application_Model_UserAccount::getInstance();
                    $objAddressModel->updateUserSettings($accountId, $status);

                    break;
                
                case 'removeAddress':
                    $addressId = $this->getRequest()->getParam('address_id');
                    $objAddressModel = Application_Model_BillingAddress::getInstance();
                    $result = $objAddressModel->removeAddress($addressId);
                    echo $result;
                    break;
					
                case 'getstates':
                    $response = new stdClass();
                    $countryId = $this->getRequest()->getParam('countryId');
                    $objStatesModel = Application_Model_States::getInstance();
                    $states  = $objStatesModel->getStateByCountry($countryId);
                    if($states){
                        $response->code = 200;
                        $response->data = $states;
                    }else{
                        $response->code = 198;
                        $response->data = null;
                    }
                    echo json_encode($response,true);
                    break;           
            }
        }
    }

    public function resetAction() {

        $objUserModel = Application_Model_Users::getInstance();

        $key = $this->getRequest()->getParam('code');
        if ($key) {
            $decodeKey = base64_decode($key);
            $userId = explode('@', $decodeKey);

            $result = $objUserModel->checkActivationKey($userId[0], $key);
            if ($result) {
                $this->view->userData = $result;
            }
            if ($this->getRequest()->isPost()) {
                $newPassword = $this->getRequest()->getParam('password');
                $newPassword = md5($newPassword);
                $resultData = $objUserModel->changePassword($newPassword, $userId[0]);
                if ($resultData) {
                    $this->view->success = $resultData;
                    $this->view->message = "Password has been changed successfully";
                }
            }
        }
    }
    
    public function affiliateAction() {
        $username = $this->getRequest()->getParam('uname');
        
        $objUserModel = Application_Model_Users::getInstance();
        $user = $objUserModel->getUseridByName($username);
       $encUid =  base64_encode($user['user_id']);
        if($user){
            $this->view->affuser = $user['user_id'];
            $this->_redirect('/signup/'.$encUid);
        }
    }    
    
    public function affloginAction() {
        if ($this->getRequest()->getParam('id') ) {
            $id = base64_decode($this->getRequest()->getParam('id'));
           $this->view->affiliateID = $id;
        }
        
        $objCountryModel = Application_Model_Countries::getInstance();
        $objStatesModel = Application_Model_States::getInstance();
        $countryList = $objCountryModel->getCountries();
        $stateList = $objStatesModel->getStates();
        $objFacebookModel = Engine_Facebook_Facebookclass::getInstance();
        $this->view->countryList = $countryList;
        $this->view->stateList = $stateList;        
        
        $url = $objFacebookModel->getLoginUrl();
        $this->view->fbLogin = $url;
    }
    
    public function facebookauthAction(){
        
        $objFacebookModel = Engine_Facebook_Facebookclass::getInstance();
        $objUserModel = Application_Model_Users::getInstance();
        $objSecurity = Engine_Vault_Security::getInstance();
        $objBillingModel = Application_Model_BillingAddress::getInstance();
        $objUserAccountModel = Application_Model_UserAccount::getInstance();
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
		//echo "<pre>"; print_r($this->view->session->twUserData); echo "</pre>"; die;
		if(isset($this->view->session->fbuserdetails)){
			$this->view->fbData = $this->view->session->fbuserdetails;
			unset($this->view->session->fbuserdetails);
		}
        
        if ($this->getRequest()->isPost()) {
            $data['email'] = $this->getRequest()->getParam('email');
            $data['user_name'] = $this->getRequest()->getParam('username');
            $data['password'] = $this->getRequest()->getParam('password');
            $data['fb_id'] = $this->getRequest()->getParam('facebookrid');
            $data['status'] = '1';
            $data['role'] = '1';
            $data['reg_date'] = date('Y-m-d');
            $username = $data['user_name'];
            $password =  $data['password'];
            $insertionResult = $objUserModel->insertUser($data);

			if ($insertionResult) {
				
				$link = $this->_appSetting->hostLink;            
				$message = "The best place to play daily fantasy sports for prizes. We offer the biggest pools for all of the major pro sports. ";
				$name = "DraftDaily";            
				$objFacebookModel->wallPost($link,$message,$name);
				$userBalance = array('user_id' => $insertionResult, 'balance_amt' => 0, 'bonus_amt' => 0);
				$objUserAccountModel->insertBalance($userBalance);


				$authStatus = $objSecurity->authenticate($username, $password);




				if ($authStatus) {
					$userID = $this->view->session->storage->user_id;
					$balanceDetails = $objUserAccountModel->getUserBalance($userID);
					$this->view->session->storage->balance_amt = $balanceDetails['balance_amt'];
					$this->view->session->storage->bonus_amt = $balanceDetails['bonus_amt'];
					if ($balanceDetails['available_tickets'] = '') {
						$this->view->session->storage->available_tickets = 0;
					}

					$this->_redirect('/home');
				}
			}
        }
    }  

	/****
	Author : Alok kumar saxena
	Description : this twitter login
	Date : 22-11-2017
	Action : twitterLoginAction
	Params : Request
	Return : object
	*****/
	public function twitterLoginAction(){
        
		$this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $objTwitterModel = Engine_Twitter_TwitterOAuth::getInstance();
        $objUserModel = Application_Model_Users::getInstance();
        $objSecurity = Engine_Vault_Security::getInstance();   
		$objEmaillog =Application_Model_Emaillog::getInstance();
        $objUserAccountModel = Application_Model_UserAccount::getInstance();
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
		
		if(isset($this->view->session->twuserdetails)){
			$this->view->twData = $this->view->session->fbuserdetails;
			unset($this->view->session->twuserdetails);
		}
		if($this->getRequest()->getParam('login') !== null)
		{
			//Fresh authentication
			$request_token = $objTwitterModel->getRequestToken("https://draftdaily.com/twitter-login");
			$twitter_res = $objTwitterModel->getAuthorizeURL($request_token['oauth_token']);


			//Received token info from twitter
			$this->view->session->storage->token = $request_token['oauth_token'];
			$this->view->session->storage->token_secret = $request_token['oauth_token_secret'];
			
			//Any value other than 200 is failure, so continue only if http code is 200
			if($objTwitterModel->http_code == '200')
			{
				//redirect user to twitter
				$twitter_url = $objTwitterModel->getAuthorizeURL($request_token['oauth_token']);
			
				//header('Location: ' . $twitter_url);
				$this->_redirect($twitter_url);					
			}else{
				die("error connecting to twitter! try again later!");
			}
		}
		// success login
		if(($this->getRequest()->getParam('oauth_token') !== null) && $this->view->session->storage->token == $this->getRequest()->getParam('oauth_token')){
		
		    $connection = Engine_Twitter_TwitterOAuth::SuccgetInstance($this->view->session->storage->token , $this->view->session->storage->token_secret);
		
			$access_token = $connection->getAccessToken($this->getRequest()->getParam('oauth_verifier'));
		
			
			if($connection->http_code == '200')
			{
                $params = array('include_email' => 'true', 'include_entities' => 'false', 'skip_status' => 'true');
				$user_data = $connection->get('account/verify_credentials', $params); 
				

                $this->view->session->twuserdetails = $user_data;
                $this->view->twData = $this->view->session->twuserdetails;
                $user = $objUserModel->validateTwitterUser($user_data['id']);
           
				if(count($user) == 0 ){
					$data['email'] = $user_data['email'];
					$data['user_name'] = $user_data['screen_name'];
					$data['password'] = md5($user_data['id']);
					$data['email_verify_status'] = 1;
					$data['oauth_provider'] = 'twitter';
					$data['t_oauth_uid'] = $user_data['id'];
					$data['t_oauth_token'] = $this->view->session->storage->token;
					$data['t_oauth_secret'] = $this->view->session->storage->token_secret;
					
					$data['status'] = '1';
					$data['role'] = '1';
					$data['reg_date'] = date('Y-m-d');
					
					$insertionResult = $objUserModel->insertUser($data);
					$authStatus = $objSecurity->authenticate($data['user_name'], $data['password']);
					
					if ($authStatus) {
							$userID = $this->view->session->storage->user_id;
							$balanceDetails = $objUserAccountModel->getUserBalance($userID);
							$this->view->session->storage->balance_amt = $balanceDetails['balance_amt'];
							$this->view->session->storage->bonus_amt = $balanceDetails['bonus_amt'];
							if ($balanceDetails['available_tickets'] = '') {
								$this->view->session->storage->available_tickets = 0;
							}
							
							$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
							$postmark_config = $config->getOption('postmark');
							$subject = 'Welcome Mail';
							$template_name = "Welcome_template";
							try{
								$client = new Postmark\PostmarkClient($postmark_config['key']);
								$result = $client->sendEmailWithTemplate(
									$postmark_config['email'],
									$data['email'],
									$postmark_config['Welcome_template'], 
									[
										"site_name" => $postmark_config['site_name'],
										"username" => $data['user_name'],
									] 
								);
							} catch (Exception $e){
							   print_r($e); die;
							}
							
                            $insertdataemaillog = array(
                                'sent_email' => $data['email'],
                                'sent_time' => date('Y-m-d H:i:s'),
                                'sent_template' => $template_name,
                                'message' => $subject
                            );
                            $insertdataemaillog_result = $objEmaillog ->insertEmailLog($insertdataemaillog);

							$this->_redirect('/home');
						}
							
				} else {
					// Update the tokens
				
					$data['t_oauth_uid'] = $user_data['id'];
					$data['t_oauth_token'] = $this->view->session->storage->token;
					$data['t_oauth_secret'] = $this->view->session->storage->token_secret;
					$Result = $objUserModel->updateUser($data,$user['user_id']);
					
					$authStatus = $objSecurity->authenticate($user['user_name'], $user['password']);
					
					if ($authStatus) {
						$userID = $this->view->session->storage->user_id;
						$balanceDetails = $objUserAccountModel->getUserBalance($userID);
						$this->view->session->storage->balance_amt = $balanceDetails['balance_amt'];
						$this->view->session->storage->bonus_amt = $balanceDetails['bonus_amt'];
						if ($balanceDetails['available_tickets'] = '') {
							$this->view->session->storage->available_tickets = 0;
						}

						$this->_redirect('/home');
					}
				}
			
			}else{
			       die("error, try again later!");
			}
			
		}else{
		
		}			
    }
	/*********************************end twitter login here ***********************************/
	public function verifyEmailAction(){ 
		$objUserModel = Application_Model_Users::getInstance();
		$token = $this->getRequest()->getParam('token');
		if($token == "sent"){
			$this->view->action_msg = "Registration Successfully Done";
			$this->view->message = "Registration Successfully Done. Verification Link has been sent to your email Please Verify Your Email.";
		}else{
			if ($token) {
				$decodeKey = base64_decode($token);
				$userId = explode('@', $decodeKey);

				$result = $objUserModel->checkActivationKey($userId[0], $token);
				
				if ($result) {
					$objUserModel->updateEmailVerifyStatus($userId[0]);
					$this->view->action_msg = "Email Verification Done";
					$this->view->message = "Email Verified Successfully. Now you can proceed to login";					
				} else {
					$this->_redirect('/home');
				}
			}
		}
		
		$objFacebookModel = Engine_Facebook_Facebookclass::getInstance();
        $url = $objFacebookModel->getLoginUrl();
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
        $this->view->fbLogin = $url;
        
	}
}
