<?php

/**
 * AccountController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';
require_once '../vendor/autoload.php';

class User_AccountController extends Zend_Controller_Action {

    public function init() {
        
    }
 
    public function accountAction() {
        $objUserModel = Application_Model_Users::getInstance();
        $objAddressModel = Application_Model_BillingAddress::getInstance();
        $objTicketModel = Application_Model_TicketSystem::getInstance();
        $objUserAccModel = Application_Model_UserAccount::getInstance();
        $objCountries = Application_Model_Countries::getInstance();
        $objStates = Application_Model_States::getInstance();
     
        $settingsModel = Application_Model_Settings::getInstance();
        $settings = $settingsModel->getSettings();
        $this->view->settings = $settings;
         $userId = $this->view->session->storage->user_id;

        $userAccountDetails = $objUserModel->getUserDetailsByUserId($userId);
        $this->view->session->storage->userBalance = $userAccountDetails['balance_amt'];
        $this->view->session->storage->userBonus = $userAccountDetails['bonus_amt'];

        $txn_id = $this->getRequest()->getParam('txn_id');
        $objPaypal = Engine_Payment_Paypal_Paypal::getInstance();
        $info = $objPaypal->getTransactionDetails($txn_id);

        if (isset($txn_id) && !empty($txn_id)) {
            $this->view->txnamount = $this->getRequest()->getParam('mc_gross');
            $this->view->txninfo = 1;
        }
        if ($this->getRequest()->isPost()) {
           
		    if(!empty($this->getRequest()->isPost('wallet_address'))){
				$wallet_addr = trim($this->getRequest()->getPost('wallet_address'));
				if(!empty($wallet_addr)){
					$objUserModel->updateWalletAddress($wallet_addr,$userId);
				}
			}
            $upload = new Zend_File_Transfer();
            $upload->addValidator('Extension', false, array('png', 'jpg'));
            $files = $upload->getFileInfo();
           
            if (isset($files) && !empty($files)) {
                $errorNotify = 0;
                foreach ($files as $file => $info) :
                    if (!$upload->isUploaded($file)) :

                        $errmsg = "Please select file to Upload!";
                        $errorNotify = 1;
                        continue;
                    endif;

                    if (!$upload->isValid($file)) :

                        $errmsg = "Please upload only .jpg or .png file";
                        $errorNotify = 1;
                        continue;
                    endif;
                endforeach;
                if ($errorNotify == 0) {
                    $destination = getcwd() . '/assets/images/users/';
                    $destination = str_replace('\\', "/", $destination);
                    $upload->setDestination($destination);
                    $image_name = $files['userimagefile']['name'];
                    $image_name = 'users/' . $image_name;
					
                    if ($upload->receive()) {
                        $data = array('imageurl' => $image_name);
                       
                        $objUserAccModel->updateUserAccount($userId, $data);
                       
                        $this->view->successimg = "Image uploaded successfully";
                    }
                } else {
                    $this->view->errorimg = $errmsg;
                }
            }
        }

        $address = $objAddressModel->getActiveAddressByUserId($userId);

        $userAccountDetails = $objUserModel->getUserDetailsByUserId($userId);

        $accData = $objUserAccModel->getAccountByUserId($userId);
		//echo '<pre>';print_r($accData);die; 
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
     
        $this->view->ticketDetails = $allTktDetails;
        $this->view->userTickets = $tcount;
        $this->view->session->storage->userBalance = $userAccountDetails['balance_amt'];
        $this->view->session->storage->userBonus = $userAccountDetails['bonus_amt'];
        $this->view->userBalance = $userAccountDetails['balance_amt'];
        $this->view->userBonus = $userAccountDetails['bonus_amt'];
        $this->view->accdata = $accData;
        $this->view->userAccountDetails = $userAccountDetails;
       
        $this->view->userBillingAddress = $address;
        
		$countries = $objCountries->getCountries();
      
		//echo "<pre>"; print_r($this->view->session->storage); die;
       $userAddressDetails = $objAddressModel->getAddressByUserId($userId);
	   
	   if(!empty($this->view->session->storage->country_id)){
		   $states = $objStates->getStateByCountry($this->view->session->storage->country_id);
	   }else{
		   $states = $objStates->getStates();
	   }
	   
       $this->view->userAddress = $userAddressDetails;
       $this->view->userCountries = $countries;
       $this->view->userStates = $states;
    }

    public function manageAccountAction() {
        
    }

    /*
     * Name: Bhojraj Rawte
     * Date: 31/07/2014
     * Description: Blocked user
     */

    public function blockUserAction() {

        $objUserModel = Application_Model_Users::getInstance();
        $objBlockedModel = Application_Model_BlockUsers::getInstance();
        $userID = $this->view->session->storage->user_id;
        $blockdata = $objBlockedModel->getBlockedUserDetails($userID);

        $count = count($blockdata);

        if ($this->getRequest()->isPost()) {
            $this->view->required = "";
            $block = $this->getRequest()->getPost('block');
            if ($block == "block") {

                $data = array();
                $data['user_name'] = $this->getRequest()->getPost('username');
                $data['blocked_by'] = $userID;
                if ($data['user_name'] == NULL) {
                    $this->view->required = "Please enter username";
                  
                    //description: to show the message  if  username is already registerd,already blocked and succesfully blocked, and successfully unblocked
                    //date:15/10/2014
                } elseif (!empty($data['user_name'])) {
                    $userName = $data['user_name'];
                    $response = $objUserModel->validateUserName($userName);

                    if (empty($response)) {
                        $this->view->required = "This user does not exists";


                    } else {
                        if ($count < 3) {
                            $index = 0;
                            $user = array();
                            if (isset($blockdata) && is_array($blockdata)) {
                                foreach ($blockdata as $users) {
                                    $user[$index] = $users['user_name'];
                                    $index++;
                                }
                            }


                            if ($data['user_name']) {
                                if (in_array($data['user_name'], $user)) {
                                    $this->view->required = "user is already blocked";


                                } else {
                                    $objBlockedModel->inserBlockedUser($data);
                                    $this->view->required = "user is successfully blocked";


                                }
                            }
                        } else {
                            $this->view->message = "you cannot block more than three users. Unblock one user to block another";
                        }
                    }
                }
            }

            /**
             * Developer     : Nikhil Aggarwal   
             * Date          : 04/09/2014
             * Description   : Showing error message when user give null value in input box
             * */
//    ------------------------------------------------------------------------------------

            if ($block == "unblock") {
                $blockuser = $this->getRequest()->getPost('blockuser');
                $objBlockedModel->deleteBlockedUser($userID, $blockuser);
                $this->view->message = "user is successfully unblocked";
            }
        }
        $blockdata = $objBlockedModel->getBlockedUserDetails($userID);
        if ($blockdata) {
            $this->view->blocked = $blockdata;
        }
    }

    public function withdrawAction() {

        $objUserAccount = Application_Model_UserAccount::getInstance();
        $objAddressModel = Application_Model_BillingAddress::getInstance();
        $objUserModel = Application_Model_Users::getInstance();
        $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
        $objNotification = Application_Model_Notification::getInstance();
        $objCountries = Application_Model_Countries::getInstance();
        $objStates = Application_Model_States::getInstance();
        $objWithdrawalModel = Application_Model_Withdrawal::getInstance();

        $countries = $objCountries->getCountries();
        $states = $objStates->getStates();
        $this->view->userCountries = $countries;
        $this->view->userStates = $states;
//      --------------------------------------------------------
        $userId = $this->view->session->storage->user_id;
        $userAccountDetails = $objUserModel->getUserDetailsByUserId($userId);
        $address = $objAddressModel->getActiveAddressByUserId($userId);
        $this->view->userBillingAddress = $address;
        if ($userAccountDetails) {
            $this->view->userAccountDetails = $userAccountDetails;
			//echo "<pre>"; print_r($userAccountDetails); die;
        }
        $userAddressDetails = $objAddressModel->getAddressByUserId($userId);

        if ($this->getRequest()->isPost()) {
			
           // echo "<pre>"; print_r($userAccountDetails); die;
		   
            if (!empty($userAccountDetails)) {
                $pay_method = trim($this->getRequest()->getPost('pay_method'));
                $req_amount = trim($this->getRequest()->getPost('amount'));
				
                //$dfs_address = trim($this->getRequest()->getPost('dfs_address'));
                
				$dfs_address = trim($userAccountDetails['wallet_address']);
				if(!empty($dfs_address)) {
					if ($dfs_address && $dfs_address != "" && $req_amount != "") {

						if ($userAccountDetails['balance_amt'] >= $req_amount) {
							
							$requestData = array('user_id' => $userId,
								'name' => $userAccountDetails['user_name'],
								//'address' => json_encode($billingAddr, true),
								'requested_amt' => $req_amount,
								'account_no' => $dfs_address,
								'pay_method' => $pay_method,
								'request_date' => date('Y-m-d H:i:s'),
								'status' => 0,
								'transaction_id' => 0);

							$requestId = $objWithdrawalModel->insertWithdrawalRequest($requestData);

							if ($requestId) {
								$objUserAccount->updateUserBalanceWithdrawn($userId,$req_amount);

								$this->view->session->storage->userBalance = $userAccountDetails['balance_amt'] - $req_amount;

								$this->view->success = "Request has been sent";
								$data = array();
								$data['send_to'] = $userId;
								$data['sent_on'] = date('Y-m-d H:i:s');
								$data['message'] = 'Withdrawal Request has been sent Successfully!';
								$insert = $objNotification->insertNotification($data);
								
								$message = "A Dratdaily user requested for withdraw transaction. DFS amount is : $req_amount and user DFSCoin Wallet Address is : $dfs_address";
								
								$subject = "Dratdaily Withdraw Request";
								
								$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
								$postmark_config = $config->getOption('postmark');
								$email_id = "admin@draftdaily.com";
								try{
									$client = new Postmark\PostmarkClient($postmark_config['key']);
									$result = $client->sendEmailWithTemplate(
										$postmark_config['email'],
										$email_id,
										$postmark_config['Support'], 
										[
											"site_name" => $postmark_config['site_name'],
											"message" => $message,
											"subject" => $subject
										] 
									);
								} catch (Exception $e){
								   print_r($e);
								}
								
							}
						} else {
							$this->view->err = "Insufficient Balance!";
						}
					}
				} else {
					$this->view->err = "DFSCoin Wallet Address Can't be empty!";
				}

                $userAccountDetails = $objUserModel->getUserDetailsByUserId($userId);
                if ($userAccountDetails) {
                    $this->view->userAccountDetails = $userAccountDetails;
                }
            } else {
                $this->view->message = "please add your address to make a withdrawal";
            }
        }
    }

    public function transactionAction() {
        $userId = $this->view->session->storage->user_id;
        $objUserTransactionModal = Application_Model_UserTransactions::getInstance();
        
		$objUserAccount = Application_Model_UserAccount::getInstance();
       
	   $objWithdrawal = Application_Model_Withdrawal::getInstance();
		
        $all_data = $objUserTransactionModal->getTransactionDetailByOnlyUserId($userId);
		
        $win_data = $objUserTransactionModal->getWinTransactionDetailByUserId($userId); // request_type ==5
		
        $entry_data = $objUserTransactionModal->getEntryTxnByUserId($userId); // request_type ==6 , 4
		
        $withdraw_data = $objUserTransactionModal->getWithdrawalsTxnByUserId($userId); // request_type ==2
		
		$withdrawRequest = $objWithdrawal->getWithdrawalDeatilsByUserId($userId);
		//echo "<pre>"; print_r($withdrawRequest); die;
        $deposit_data = $objUserTransactionModal->getDepositsTxnByUserId($userId); // request_type ==1
		
        $bonus_data = $objUserTransactionModal->getBonusTxnByUserId($userId); // request_type ==3
        
		//echo "<pre>"; print_r($win_data); die;
        if ($all_data) {
            $this->view->all_data = $all_data;
        }
		
		if ($entry_data) {
            $this->view->entry_data = $entry_data;
        }
		
		if ($withdraw_data) {
            $this->view->withdraw_data = $withdraw_data;
        }
		
		if ($withdrawRequest) {
            $this->view->withdrawRequest = $withdrawRequest;
        }
		if ($deposit_data) {
            $this->view->deposit_data = $deposit_data;
        }
		
		if ($win_data) {
            $this->view->win_data = $win_data;
        }
		if(!empty($this->getRequest()->getParam('wid'))){
			$wid = $this->getRequest()->getParam('wid');
			$detail = $objWithdrawal->getWithdrawalDeatilsById($wid);
			$req_amount = $detail['requested_amt'];		
			$isUpdated = $objUserAccount->updateUserBalanceCancelWithdrawn($userId,$req_amount);
			$updatedAccData = $objUserAccount->getUserBalance($userId);
			$this->view->session->storage->userBalance = $updatedAccData['balance_amt'];
			if($isUpdated){	
				$wdrUpdate = array();
				$wdrUpdate['status'] = 1;	
				$wdrUpdate['pay_date'] = date('Y-m-d H:i:s');	
				$wdrUpdate['transaction_id'] = "Canceled";	
				$resp = $objWithdrawal->updateByID($wid, $wdrUpdate);	
				if($resp){						
					$transactions['user_id'] = $userId;	
					$transactions['transaction_type'] = 'DFSCoin';	
					$transactions['transaction_amount'] = $req_amount;		
					$transactions['confirmation_code'] = "Canceled";	
					$transactions['description'] = 'Withdraw Request Canceled';		
					$transactions['status'] = '1';			
					$transactions['request_type'] = '2';	
					$transactions['transaction_date'] = date('Y-m-d');	
					$objUserTransactionModal->insertUseTransactions($transactions);
					
					$this->_redirect('/transaction');   
				}	
			}
			
		}
		
    }

    public function offersAction() {
        
    }

    public function depositAction() {

        $objUserAccount = Application_Model_UserAccount::getInstance();
        $objUserModel = Application_Model_Users::getInstance();
        $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
        $objUsersDepositModel = Application_Model_UsersDeposit::getInstance();
        $objModelNotify = Application_Model_Notification::getInstance();
        $objUsers = Application_Model_Users::getInstance();
        $objAffiliteModel = Application_Model_Affiliate::getInstance();
        $objSettingsModel = Application_Model_Settings::getInstance();
        $objFacebookModel = Engine_Facebook_Facebookclass::getInstance();
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();

        $userId = $this->view->session->storage->user_id;
		$userAccountDetails = $objUserModel->getUserDetailsByUserId($userId);
		$user_state_id = $this->view->session->storage->state_id;
	
		$this->view->user_state_id = $user_state_id;
		
		if(!empty($this->_appSetting->noteligible->states) && !empty($user_state_id)){
			$not_eligible_states = explode(",", $this->_appSetting->noteligible->states);
			if(in_array($user_state_id,$not_eligible_states)){
				$this->view->not_eligible_states = 1;
			}
		}
		
		//echo "<pre>"; print_r($not_eligible_states); die;
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$dfsAddress = $config->getOption('dfs');
		
		$this->view->adminDfsAddress = $dfsAddress['admin-dfs-address'];
		
        if ($this->getRequest()->isPost()) {
			
			if(!empty($userAccountDetails['wallet_address'])) {
				
				$txid = $this->getRequest()->getPost('dfs_txn_id');
				$isUsed = $objUsersDepositModel->validateTxId($txid);
				
				if(!empty($txid) && !$isUsed){
					
					$url ="http://139.162.189.133:3001/v1/api/transaction/details";
					$res_data = $this->dfs_curl($url,$txid);
					$res_data = json_decode($res_data,true);
					$res_array = array();
					//echo "<pre>"; print_r($res_data); echo "</pre>"; die;
					if(empty($res_data['err'])){
						if(isset($res_data['help']['vout'][1]['scriptPubKey']['addresses'][0])){
							$to_address = trim($res_data['help']['vout'][1]['scriptPubKey']['addresses'][0]);
							$to_pay_addr = trim($dfsAddress['admin-dfs-address']);
							// master dfs addAddres
							
							if(($to_address==$to_pay_addr)){
								
								$txid_data['transaction_id'] = $txid;
								$txid_data['user_id'] = $userId;
								$isInserted = $objUsersDepositModel->insertDepositTransaction($txid_data);
								
								if($isInserted){
									
									$amnt = $res_data['help']['vout'][1]['value'];
									$transactions['user_id'] = $userId;
									$transactions['transaction_type'] = 'DFSCoin';
									$transactions['transaction_amount'] = $amnt;
									$transactions['confirmation_code'] = $res_data['help']['confirmations'];
									$transactions['description'] = 'Deposit Amount';
									$transactions['status'] = '1';
									$transactions['request_type'] = '1';
									$transactions['transaction_date'] = date('Y-m-d');
									$result = $objUserTransactionsModel->insertUseTransactions($transactions);
									
									if(!empty($result)){
										$accountData = array('balance_amt' => $amnt,'last_deposite'=>$amnt);
										$objUserAccount->updateUserBalanceOnly($userId, $accountData);
										
										$updatedAccData = $objUserAccount->getUserBalance($userId);
										$this->view->session->storage->userBalance = $updatedAccData['balance_amt'];
										$res_array['status'] = 1;
										$res_array['message'] = "* Transaction succesfully done check your balance";
									}
								} else {
									$res_array['status'] = 0;
									$res_array['message'] = "* Transaction aborted please try again";
								}
								
							}else {
								$res_array['status'] = 0;
								$res_array['message'] = "* Entered transaction id paid DFS address is not equal to draftdaily DFS address";
							}
						}else{
							$res_array['status'] = 0;
							$res_array['message'] = "* Invalid transaction id or pending transaction please try again later.";
						}
						
					} else {
						$res_array['status'] = 0;
						$res_array['message'] = "* No information available about this transaction Id";
					}
				} else {
					$res_array['status'] = 0;
					$res_array['message'] = "* This transaction Id has been already used.";
				}
			}else{
				$res_array['status'] = 0;
				$res_array['message'] = "* Please update your DFSCoin wallet address to account and try again.";
			}
			echo json_encode($res_array); die;			
        }
		
    }
	
	

    public function dfs_curl($url,$data) {
		$ch = curl_init();
		//echo $data['txid']; die;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "txid=$data");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 		
		curl_setopt($ch, CURLOPT_HEADER, false);		
		$result = curl_exec($ch);
		if($result === false){
			//die('Curl failed ' . curl_error());
		}	
		curl_close($ch);
		return $result;
    }

    public function bonusoffersAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $objoffer = Application_Model_Offers::getInstance();
//        echo "test";
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
//            $request = "cmd=".urlencode('_notify-validate');
//            foreach($data as $key=>$value){
//                $value = urldecode(stripcslashes($value));
//                $request.="&$key=$value";
//            }
//            $response = $this->ipnrequest($request);
//            if(strcmp($request,""))
            $objoffer->test(json_encode($data, true));
//            $this->view->session->storage->paypal = $d;
        }


//        $objCountryModel = Application_Model_Countries::getInstance();
//        $objLocation = Engine_Maxmind_UserLocation::getInstance();
//        $ip = "101.0.63.129";
//        $record = $objLocation->getHostDetails($ip);
//        $data = $objCountryModel->getBlockCountriesDetails($record->country_code, $record->country_name);
//        if ($data) {
//            $this->view->offer = 1;
//        }
    }

    /*
     * Name: Abhinish Kumar Singh
     * Date: 23/07/2014
     * Description: This action is used to edit existing user address in
     *              My Account Section
     */

    public function editUserAddressAction() {
        $this->_helper->layout()->disableLayout();
        $objUserModel = Application_Model_BillingAddress::getInstance();
	
        $objCountries = Application_Model_Countries::getInstance();
        $objStates = Application_Model_States::getInstance();
        $countries = $objCountries->getCountries();
        $states = $objStates->getStates();
        $userId = $this->view->session->storage->user_id;
        $addressId = $this->getRequest()->getParam('addressid');
        $userAddressDetails = $objUserModel->getAddressByAddressId($addressId);
		
		
        $data = array();
        if ($this->getRequest()->isPost()) {
            $data['address1'] = $this->getRequest()->getPost('address1');
            $data['address2'] = $this->getRequest()->getPost('address2');
           
            $data['name'] = $this->getRequest()->getPost('name');
            $data['city'] = $this->getRequest()->getPost('city');
            $data['zip'] = $this->getRequest()->getPost('zip');
            $data['status'] = $this->getRequest()->getPost('status');
            if ($data['status'] == 1) {
                $objUserModel->changeStatus($userId);
            }
            $data['country'] = $this->getRequest()->getPost('country');
            $data['user_state'] = $this->getRequest()->getPost('user_state');
            $data['phone'] = $this->getRequest()->getPost('phone');
            $objUserModel->editAddress($addressId, $data);
            $name=$this->getRequest()->getPost('accountforum');
            if($name){
                if($name === "accountform1"){
                 $this->_redirect('/account');     
                }else{
                    $this->_redirect('/withdrawal');     
                }
            }else{
            $this->_redirect('/account/billing/saved');
            }
        }

        $this->view->userAddress = $userAddressDetails;
        $this->view->userCountries = $countries;
        $this->view->userStates = $states;
    }

    /*
     * Name: Abhinish Kumar Singh
     * Date: 23/07/2014
     * Description: This action is used to add new user address in
     *              My Account Section
     */

    public function addUserAddressAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $objUserModel = Application_Model_BillingAddress::getInstance();
        $userId = $this->view->session->storage->user_id;
        $data = array();
        if ($this->getRequest()->isPost()) {
            $data['address1'] = $this->getRequest()->getPost('address1');
            $data['address2'] = $this->getRequest()->getPost('address2');
            $data['name'] = $this->getRequest()->getPost('name');
            $data['city'] = $this->getRequest()->getPost('city');
            $data['zip'] = $this->getRequest()->getPost('zip');
            $data['status'] = $this->getRequest()->getPost('status');
            if ($data['status'] == 1) {
                $objUserModel->changeStatus($userId);
            }
            $data['country'] = $this->getRequest()->getPost('country');
            $data['user_state'] = $this->getRequest()->getPost('user_state');
            $data['phone'] = $this->getRequest()->getPost('phone');
            $data['user_id'] = $userId;						/* echo "<pre>"; print_r($data); die; */
            $userAccountDetails = $objUserModel->addAddress($data);
        }
        if ($userAccountDetails) {
			$formname =$this->getRequest()->getPost('accountforum2');
			if($formname){
				if($formname === "accountform2"){
					$this->_redirect('/account');
                }else{
                    $this->_redirect('/withdrawal');     
                }
            } else{
             $this->_redirect('/account/billing');    
            }
        }
    }

    /*
     * Name: Abhinish Kumar Singh
     * Date: 23/07/2014
     * Description: This action is used to add new user address in
     *              My Account Section
     */

    public function billingAction() {
        $objBillingAddressModel = Application_Model_BillingAddress::getInstance();
        $objCountries = Application_Model_Countries::getInstance();
        $objStates = Application_Model_States::getInstance();
        $countries = $objCountries->getCountries();
        $states = $objStates->getStates();
        $userId = $this->view->session->storage->user_id;

        $userAddressDetails = $objBillingAddressModel->getAddressByUserId($userId);
        $this->view->userAddress = $userAddressDetails;
        $this->view->userCountries = $countries;
        $this->view->userStates = $states;

        $message = $this->getRequest()->getParam('msg');
        if ($message) {
            $this->view->message = $message;
        }
        $can = $this->getRequest()->getParam('can');
        if ($can == "tr") {
            $this->view->cancel = "true";
        }
//      echo $message;
//      die;
    }

    /**
     * Developer     : Vivek Chaudhari   
     * Date          : 14/07/2014
     * Description   : in transaction history section
     * @param       : <int>transation day
     */
    public function transactionAjaxHandlerAction() {
        $objUserTransactionModel = Application_Model_UserTransactions::getInstance();
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $user_id = $this->view->session->storage->user_id;
        if ($this->getRequest()->isPost()):
            $day = $this->getRequest()->getParam('day');
            $curDate = Date('Y-m-d H:m:s');
            $subDate = $day;
            if ($day != '-1') {
                $subDate = Date('Y-m-d H:m:s', strtotime("-" . $day . "days"));
            }
            $filterData = $objUserTransactionModel->getFilteredTransaction($user_id, $curDate, $subDate);
            if ($filterData):
                echo json_encode($filterData);
            else:
                echo 0;
            endif;
        endif;
    }

    public function withdrawAjaxHandlerAction() { 
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $objUserModel = Application_Model_Users::getInstance();
        $objUserAccountModel = Application_Model_UserAccount::getInstance();
        $objEmaillog = Application_Model_Emaillog::getInstance();
        $objUsrTxnModel = Application_Model_UserTransactions::getInstance();
        $mailer = Engine_Mailer_Mailer::getInstance();
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
        
        if ($this->getRequest()->isPost()) {
            $method = $this->getRequest()->getPost('method');

            switch ($method) {
                case "getcode":
                    $user_id = $this->view->session->storage->user_id;
                    $userData = $objUserModel->getUserDetailsByUserId($user_id);
                    $code = $this->generateRandomString();

                    $username = $userData['user_name'];
                    $useremail = $userData['email'];

                    $message = "To perform your withdrawal transaction you need to use this code : '" . $code . "'. This code will expire within 15 minutes of receipt.  If you fail to act within the required timeframe, you can get another code , Just click on (get security code) on the withdrawal page. Please don't share this code with anyone.";
                    
                    $subject = "DFSCoin Secure Transaction code";
                    
                    $topic = "Message from " . $this->_appSetting->title . " Support";
                    $template_name = 'Support';
                    
                    // code by prince for send email by postmark
                    
                    $config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
					$postmark_config = $config->getOption('postmark');
                    try{
						$client = new Postmark\PostmarkClient($postmark_config['key']);
						$result = $client->sendEmailWithTemplate(
							$postmark_config['email'],
							$useremail,
							$postmark_config['Support'], 
							[
								"site_name" => $postmark_config['site_name'],
								"username" => $username,
								"message" => $message,
								"subject" => $subject
							] 
						);
					} catch (Exception $e){
					   print_r($e);
					}
				

                    $response = new stdClass();
                    if (isset($result)) {
                        if (isset($result->errorcode) && $result->errorcode != 0) {
                            $response->code = "198";
                            $response->message = "Unable to send message due to some issue";
                            echo json_encode($response);
                        } else {
                            $insertdataemaillog = array(
                                'sent_email' => $useremail,
                                'sent_time' => date('Y-m-d H:i:s'),
                                'sent_template' => $template_name,
                                'message' => $subject . "|" . $message
                            );

                            $insertdataemaillog_result = $objEmaillog->insertEmailLog($insertdataemaillog);

                            $secure = array('code' => $code,
                                'time' => strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . "+15 minutes")))
                            );
                            $accdata = array('secure_code' => json_encode($secure));
                            $check = $objUserAccountModel->updateUserAccount($user_id, $accdata);

                            $response->code = "200";
                            $response->message = "security code Sent Successfully to your email :- " . $useremail;
                            echo json_encode($response);
                        }
                    } else {
                        $response->code = "198";
                        $response->message = "Error Occurred, Unable to send message";
                        echo json_encode($response);
                    }

                    break;

                case "validatecode" :
                    $currTimeStamp = strtotime(date('y-m-d H:i:s'));
                    $inputcode = $this->getRequest()->getPost('inputcode');
                    $user_id = $this->view->session->storage->user_id;
                    $userData = $objUserModel->getUserDetailsByUserId($user_id);
                    $validity = new stdClass();
                    if (isset($userData['secure_code']) && $userData['secure_code'] != "" && $userData['secure_code'] != null) {
                        $decoded = json_decode($userData['secure_code'], true);

                        if (intval($currTimeStamp) < intval($decoded['time'])) {
                            if ($decoded['code'] == $inputcode) {
                                $validity->code = 200;
                            } else {
                                $validity->code = 196;
                                $validity->message = "Provided security code is invalid , please reenter";
                            }
                        } else {
                            $validity->code = 196;
                            $validity->message = "Provided security code has been expired, please get new one";
                        }
                    } else {
                        $validity->code = 198;
                        $validity->message = "Error occured, please try again";
                    }
                    echo json_encode($validity);
                    break;
               
               case "fppexchange": // bonus amount exchange by taking fpp
                    $t_amount = $this->getRequest()->getPost('t_amount');
                    $t_amount = intval($t_amount);
                    $user_id = $this->view->session->storage->user_id;
                    $userData = $objUserModel->getUserDetailsByUserId($user_id);
                    $settingsModel = Application_Model_Settings::getInstance();
                    $settings = $settingsModel->getSettings();
                    $myBonus = intval($userData['bonus_amt']);
                    $myFpp = intval($userData['fpp']);
                    $response = new stdClass();
                   
                    if($t_amount <= $myBonus){ //check bonus money is sufficient
                        $exchangeCost  = $settings['fpp_exchange'];
                        $fppRequired = intval(intval($exchangeCost) * intval($t_amount));
                        if($fppRequired <= $myFpp){ //check sufficient fpp available
                            
                            $check = $objUserAccountModel->updateExchangeBalance($user_id,$t_amount,$t_amount,$fppRequired);
                            if($check){ // add transaction history
                                $transactions['user_id'] = $user_id;
                                $transactions['transaction_type'] = 'Amount Credit';
                                $transactions['transaction_amount'] = $t_amount;
                                $transactions['fpp_used'] = $fppRequired;
                                $transactions['confirmation_code'] = 'N/A';
                                $transactions['description'] = 'FPP exchange';
                                $transactions['status'] = 1;
                                $transactions['request_type'] = '7';
                                $transactions['transaction_date'] = date('Y-m-d');
                                
                                $tCheck = $objUsrTxnModel->insertUseTransactions($transactions);
                                if($tCheck){
                                    $response->code = 200;
                                    $response->message = "Bonus exchange successfully";
                                }
                            }
                        }else{
							$response->code = 198;
							$response->message = "You have insufficient FPP for exchange, you need ".$fppRequired." FPP for transaction";
							}
                    }else{
                        $response->code = 198;
                        $response->message = "You have insufficient Bonus amount";
                    }
                    echo json_encode($response,true);
                    
                   break;
                    
               default :
                    break;
            }
        }
    }

    function generateRandomString($length = 7) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function checkWithdrawAjaxAction() {
        $mailer = Engine_Mailer_Mailer::getInstance();
        $objEmaillog = Application_Model_Emaillog::getInstance();
        $objWithdrawalModel = Application_Model_Withdrawal::getInstance();
        $objUserAccount = Application_Model_UserAccount::getInstance();
        $objUserModel = Application_Model_Users::getInstance();
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $userId = $this->view->session->storage->user_id;
        $userAccountDetails = $objUserModel->getUserDetailsByUserId($userId);
        $reply = new stdClass();
        $checkname = $this->getRequest()->getPost('checkname');
        $checkadd1 = $this->getRequest()->getPost('checkadd1');
        $checkemail = $this->getRequest()->getPost('checkemail');
        $checkacctno = $this->getRequest()->getPost('checkacctno');
        $checkamount = $this->getRequest()->getPost('checkamount');
        $checkzipcode = $this->getRequest()->getPost('checkzipcode');
        $datereq = date('Y-m-d H:i:s');
        if ((!empty($checkzipcode) || !empty($checkname) || !empty($checkadd1) || !empty($checkemail) || !empty($checkacctno) || !empty($checkamount)) && ($userAccountDetails['balance_amt'] >= $checkamount)) {

            //   echo json_encode($checkamount);
            $requestData = array('user_id' => $userId,
                'requested_amt' => $checkamount,
                'email' => $checkemail,
                'pay_method' => 2,
                'request_date' => date('Y-m-d H:i:s'),
                'status' => 0,
                'name' => $checkname,
                'account_no' => $checkacctno,
                'address' => $checkadd1 . " zip code : " . $checkzipcode);

            $requestId = $objWithdrawalModel->insertWithdrawalRequest($requestData);
            $objUserAccount->updateUserBalanceWithdrawn($userId, $checkamount);

            $this->view->session->storage->userBalance = $userAccountDetails['balance_amt'] - $checkamount;
            $objNotification = Application_Model_Notification::getInstance();
            $data = array();
            $data['send_to'] = $userId;
            $data['sent_on'] = date('Y-m-d H:i:s');
            $data['message'] = "Cheque Withdrawal Request has been sent Successfully on  $datereq ! ";
            $insert = $objNotification->insertNotification($data);
            if ($requestId) {
                $useremail = $checkemail;
                $message = "Cheque request of $$checkamount has been successfully submitted on  $datereq";
                $subject = "Cheque request";
                $topic = "Message from DraftOff Support";
                $template_name = 'support';
                $mergers = array(
                    array(
                        'name' => 'topic',
                        'content' => $topic
                    ),
                    array(
                        'name' => 'message',
                        'content' => $message
                    ),
                    array(
                        'name' => 'useremail',
                        'content' => $useremail
                    )
                );
//                    if(isset($template_name) && $template_name!="" )
                $result = $mailer->sendtemplate($template_name, $useremail, $checkname, $subject, $mergers);

                $insertdataemaillog = array(
                    'sent_email' => $useremail,
                    'sent_time' => date('Y-m-d H:i:s'),
                    'sent_template' => $template_name,
                    'message' => $message
                );

                $insertdataemaillog_result = $objEmaillog->insertEmailLog($insertdataemaillog);

                $reply->message = 'Withdraw request has been sent Successfully';
                $reply->code = 200;
                echo json_encode($reply);
            }
        } else {
            $reply->message = 'Enter amount within amount balance';
            $reply->code = 198;
            echo json_encode($reply);
        }
    }


    public function ipnListenerAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $objCore = Engine_Core_Core::getInstance();
        $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();

        $this->_appSetting = $objCore->getAppSetting();

         if ($this->_appSetting->paypal->sandboxFlag) {
                $paypalUrl = "https://www.sandbox.paypal.com/webscr";
            } else {
                $paypalUrl = "https://www.paypal.com/cgi-bin/webscr";
            }
//        $objoffer = Application_Model_Offers::getInstance();
        if ($this->getRequest()->isPost()) {
//            $postData = $this->getRequest()->getPost();
//            $objoffer->test(json_encode($postData,true));
            
            $raw_post_data = file_get_contents('php://input');
            $raw_post_array = explode('&', $raw_post_data);
            $myPost = array();
            foreach ($raw_post_array as $keyval) {
                $keyval = explode('=', $keyval);
                if (count($keyval) == 2)
                    $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
            $req = 'cmd=_notify-validate';

            foreach ($myPost as $key => $value) {
                $value = urlencode($value);
                $req .= "&$key=$value";
            }
            $txn_id = $_POST['txn_id'];
            $receiver_email = $_POST['receiver_email'];
            $payer_email = $_POST['payer_email'];
            $userId = $_POST['custom'];
            $payFee = $_POST['payment_fee'];
            $payGross = $_POST['payment_gross'];
            $amount = floatval($payGross);// - floatval($payFee);
//            Completed
            if ($_POST['payment_status'] == 'Completed') {
                $status = 1;
            } else {
                $status = 0;
            }
            $transactions['user_id'] = $userId;
            $transactions['transaction_type'] = 'Paypal';
            $transactions['transaction_amount'] = $amount;
            $transactions['confirmation_code'] = $txn_id;
            $transactions['description'] = 'Deposit Amount';
            $transactions['status'] = $status;
            $transactions['request_type'] = '1';
            $transactions['transaction_date'] = date('Y-m-d');

            if ($this->_appSetting->paypal->sandboxFlag == 'true') {
                $paypalUrl = "https://www.sandbox.paypal.com/webscr";
            } else {
                $paypalUrl = "https://www.paypal.com/cgi-bin/webscr";
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $paypalUrl);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            $response = curl_exec($ch);
            if (!($response = curl_exec($ch))) {
                curl_close($ch);
                exit;
            }
            //curl_close($ch);

            if (strcmp($response, "VERIFIED") == 0) {
                $txDetails = $objUserTransactionsModel->getIpnTransaction($txn_id);
                $objUserAccModel = Application_Model_UserAccount::getInstance();
                $settingsModel = Application_Model_Settings::getInstance();
                $objUserAccount = Application_Model_UserAccount::getInstance();
                $settings = $settingsModel->getSettings();
                if (empty($txDetails)) {
                    if ($status) { //check ipn complete
                        if ($receiver_email == $this->_appSetting->paypal->recieveremail) {
                            $accountData = array('balance_amt' => $amount);
                            $objUserAccModel->updateUserBalanceOnly($userId, $accountData);
                            $objUserTransactionsModel->insertUseTransactions($transactions);
                            if ($settings['bonus_status'] == 1) {
                                //check users first transaction
                                $usrDTxn = $objUserTransactionsModel->getUserDepositTransaction($userId);
                                if (empty($usrDTxn)) {
                                    $transactions['user_id'] = $userId;
                                    $transactions['transaction_type'] = 'N/A';
                                    $transactions['transaction_amount'] = $amount;
                                    $transactions['confirmation_code'] = "N/A";
                                    $transactions['description'] = 'Bonus Amount';
                                    $transactions['status'] = 1;
                                    $transactions['request_type'] = '7';
                                    $transactions['transaction_date'] = date('Y-m-d');
                                    $objUserTransactionsModel->insertUseTransactions($transactions);
                                    $objUserAccount->addUserBonusAmount($userId, $amount);
                                    
                                }
                            }
                            //affiliate system-----------------------------------
//                            $objAffiliteModel = Application_Model_Affiliate::getInstance();
//                            $affiliteData = $objAffiliteModel->getAffiliateDataByID($userId);
//                            if($affiliteData){
//                                if($settings['affilate_commission']!=0){
//                                 $commission = ($amount * $settings['affilate_commission'])/100;
//                                 $transactions['user_id'] = $affiliteData['affiliate_user_id'];
//                                 $transactions['transaction_type'] = 'Paypal';
//                                 $transactions['transaction_amount'] = $commission;
//                                 $transactions['confirmation_code'] = 'N/A';
//                                 $transactions['description'] = 'Referal amount';
//                                 $transactions['status'] = '1';
//                                 $transactions['request_type'] = '8';                
//                                 $transactions['transaction_date'] = date('Y-m-d');
//                                 $objUserTransactionsModel->insertUseTransactions($transactions);
//                                 $objUserAccount->updateBalance($affiliteData['affiliate_user_id'],$commission);
//                                }
//                                 $objAffiliteModel->updateAffiliate($affiliteData['affiliate_id']);
//                            }
                            //======================================
                        }
                    }
                } else if (!empty($txDetails)) {
                    $data['status'] = $status;
                    $objUserTransactionsModel->updateTransaction($txDetails['transaction_id'], $data);
                    if ($status) { //check ipn complete
                        if (!$txDetails['status']) {
                            $accountData = array('balance_amt' => $amount);
                            $objUserAccModel->updateUserBalanceOnly($userId, $accountData);
                            if ($settings['bonus_status'] == 1) {
                                //check users first transaction
                                $usrDTxn = $objUserTransactionsModel->getUserDepositTransaction($userId);
                                if (empty($usrDTxn)) {
                                    $transactions['user_id'] = $userId;
                                    $transactions['transaction_type'] = 'N/A';
                                    $transactions['transaction_amount'] = $amount;
                                    $transactions['confirmation_code'] = "N/A";
                                    $transactions['description'] = 'Bonus Amount';
                                    $transactions['status'] = 1;
                                    $transactions['request_type'] = '7';
                                    $transactions['transaction_date'] = date('Y-m-d');
                                    $objUserTransactionsModel->insertUseTransactions($transactions);
                                    $objUserAccount->addUserBonusAmount($userId, $amount);
                                }
                            }
                            //affiliate data---------------------------------------------------
//                             $objAffiliteModel = Application_Model_Affiliate::getInstance();
//                            $affiliteData = $objAffiliteModel->getAffiliateDataByID($userId);
//                            if($affiliteData){
//                                if($settings['affilate_commission']!=0){
//                                 $commission = ($amount *$settings['affilate_commission'])/100;
//                                 $transactions['user_id'] = $affiliteData['affiliate_user_id'];
//                                 $transactions['transaction_type'] = 'Paypal';
//                                 $transactions['transaction_amount'] = $commission;
//                                 $transactions['confirmation_code'] = 'N/A';
//                                 $transactions['description'] = 'Referal amount';
//                                 $transactions['status'] = '1';
//                                 $transactions['request_type'] = '8';                
//                                 $transactions['transaction_date'] = date('Y-m-d');
//                                 $objUserTransactionsModel->insertUseTransactions($transactions);
//                                 $objUserAccount->updateBalance($affiliteData['affiliate_user_id'],$commission);
//                                }
//                                 $objAffiliteModel->updateAffiliate($affiliteData['affiliate_id']);
//                            }
                            //================================================
                        }
                    }
                }
            }
            
        }
    }
}
