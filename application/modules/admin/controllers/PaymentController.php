<?php

/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class Admin_PaymentController extends Zend_Controller_Action {

    public function init() {
        
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 19/03/2014
     * Description : Get Payment details
     */
    public function paymentDetailsAction() {
        $objWithdrawalModel = Admin_Model_WithdrawalRequest::getInstance();
        $withdrawalDetails = $objWithdrawalModel->getPaymentDeatils();
        if ($withdrawalDetails) :
            $this->view->withdrawal = $withdrawalDetails;
        endif;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 19/03/2014
     * Description : Get Payment Approval details
     */
    public function paymentApprovalAction() {
        $objWithdrawalModel = Admin_Model_WithdrawalRequest::getInstance();
        $withdrawalDetails = $objWithdrawalModel->getPanddingPaymentDeatils();
        if ($withdrawalDetails) :
            $this->view->withdrawal = $withdrawalDetails;
        endif;
    }

    public function withdrawalDetailsAction() {
        $objWithdrawalModel = Admin_Model_WithdrawalRequest::getInstance();	
		$objUserTransactionsModel = Admin_Model_UserTransactions::getInstance();
		$objUserAccount = Admin_Model_UserAccount::getInstance();
		
        $withdrawalDetails = $objWithdrawalModel->getWithdrawalPaymentDeatils();
		
        $this->view->paypalWithdraw = $withdrawalDetails;	
		
		if ($this->getRequest()->isPost()) {	 
			$withdrawalIds = $this->getRequest()->getPost('withdrawalId');
			if(isset($withdrawalIds) && !empty($withdrawalIds)){	
				foreach($withdrawalIds as $wid){	
					$detail = $objWithdrawalModel->getWithdrawalDeatilsById($wid);	
					$userId = $detail['user_id'];		
					$req_amount = $detail['requested_amt'];		
					$isUpdated = $objUserAccount->updateUserBalanceCancelWithdrawn($userId,$req_amount);	
					if($isUpdated){	
						$wdrUpdate = array();
						$wdrUpdate['status'] = 1;	
						$wdrUpdate['pay_date'] = date('Y-m-d H:i:s');	
						$wdrUpdate['transaction_id'] = "Canceled";	
						$resp = $objWithdrawalModel->updateByID($wid, $wdrUpdate);	
						if($resp){						
							$transactions['user_id'] = $userId;	
							$transactions['transaction_type'] = 'DFSCoin';	
							$transactions['transaction_amount'] = $req_amount;		
							$transactions['confirmation_code'] = "Canceled";	
							$transactions['description'] = 'Withdraw Request Canceled';		
							$transactions['status'] = '1';			
							$transactions['request_type'] = '2';	
							$transactions['transaction_date'] = date('Y-m-d');	
							$objUserTransactionsModel->insertUseTransactions($transactions);	
						}	
					}
		
				}	
				$this->view->success = 1;	
			}	
		}
		//echo "<pre>"; print_r($withdrawalDetails); die;
		/* if ($withdrawalDetails) {
			$paypalWithdraw = $this->filterArray('1', $withdrawalDetails, 'pay_method');
			$chequeWithdraw = $this->filterArray('2', $withdrawalDetails, 'pay_method');
			$this->view->paypalWithdraw = $paypalWithdraw;
			$this->view->chequeWithdraw = $chequeWithdraw;
		} */
    }

    function filterArray($searchValue, $array, $searchKey) {
        if ($searchValue != "" && $searchKey != "") {
            $filter = function($array) use($searchValue, $searchKey) {
                        if ($array[$searchKey]) {
                            return $array[$searchKey] == $searchValue;
                        }
                    };
            $filtered = array_filter($array, $filter);
            return $filtered;
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 06/08/2014
     * Description : Get depositor details
     */
    public function depositorDetailsAction() {
        $objUserTransactionsModel = Admin_Model_UserTransactions::getInstance();
        $transactionDetails = $objUserTransactionsModel->getdepositorDeatils();
        if ($transactionDetails) {
            $this->view->transaction = $transactionDetails;
            //echo "<pre>"; print_r($transactionDetails); echo "</pre>"; die;
        }
        //dev:priyanka varanasi
        //desc:payment details
        if ($this->getRequest()->isPost()) {
            $radiosite = $this->getRequest()->getParam('checked_site_radio');
            switch ($radiosite) {
                case ($radiosite == 0):
                    $transactionDetails = $objUserTransactionsModel->getdepositorDeatils();
                    if ($transactionDetails) {
                        $this->view->transaction = $transactionDetails;
                    }
                    break;
                case ($radiosite == 1):
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);
                    $deposit = $objUserTransactionsModel->getonlydepositDeatils();
                    if ($deposit) {
                        echo json_encode($deposit);
                        //echo "<pre>"; print_r($deposit); echo "</pre>" ;die('test1');
//            $this->view->det = $deposit;
                    }
                    break;
                case ($radiosite == 2):
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);
                    $withdr = $objUserTransactionsModel->getonlywithdrawDeatils();
                    if ($withdr) {
                        echo json_encode($withdr);
                        //echo "<pre>"; print_r($withdr); echo "</pre>";die('test2');
//            $this->view->withdr = $withdr;
                    }
                    break;
                case ($radiosite == 3):
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);
                    $fppreward = $objUserTransactionsModel->getonlyfpprewardDeatils();
                    if ($fppreward) {
                        echo json_encode($fppreward);
                        // echo "<pre>"; print_r($fppreward); echo "</pre>";die('test3');
//            $this->view->fppreward = $fppreward;
                    }

                    break;
                case ($radiosite == 4):
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);
                    $refund = $objUserTransactionsModel->getonlyrefundDeatils();
                    if ($refund) {
                        echo json_encode($refund);
                        // echo "<pre>"; print_r($refund); echo "</pre>";die('test4');
//            $this->view->refund = $refund;
                    }
                    break;
                case ($radiosite == 5):
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);
                    $winning = $objUserTransactionsModel->getonlyWinningDeatils();
                    if ($winning) {
                        echo json_encode($winning);
                        // echo "<pre>"; print_r($winning); echo "</pre>";die('test5');
//            $this->view->winning = $winning;
                    }
                    break;
                case ($radiosite == 6):
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);
                    $onlyentry = $objUserTransactionsModel->getonlyentryfeeDeatils();
                    if ($onlyentry) {
                        echo json_encode($onlyentry);
                        // echo "<pre>"; print_r($onlyentry); echo "</pre>";die('test6');
//            $this->view->onlyentry = $onlyentry;
                    }
                    break;
                case ($radiosite == 7):
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);
                    $bonusdet = $objUserTransactionsModel->getonlybonusDeatils();
                    if ($bonusdet) {
                        echo json_encode($bonusdet);
                        //echo "<pre>"; print_r($bonusdet); echo "</pre>";die('test8');
                    }
                    break;
                //-----------------code ends-----------------------------------------------
            }
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 03/11/2014
     * Description : Get Profit details
     */
    public function profitStatsAction() {

        $objProfitModel = Admin_Model_Profit::getInstance();
        $currentProfitYear = date("Y");
        if ($this->getRequest()->getParam('profit')) {
            $currentProfitYear = $this->getRequest()->getParam('profit');
        }
        $profitStatics = $objProfitModel->adminProfitStatics($currentProfitYear);
		foreach ($profitStatics as $rec) {
			switch ($rec['month']){
				case "DEC" :
					$profitStat[11]['total']=$rec['total'];
					break;
				case "NOV" :
					$profitStat[10]['total']=$rec['total'];
					break;
				case "OCT" :
					$profitStat[9]['total']=$rec['total'];
					break;
				case "SEP" :
					$profitStat[8]['total']=$rec['total'];
					break;
				case "AUG" :
					$profitStat[7]['total']=$rec['total'];
					break;
				case "JUL" :
					$profitStat[6]['total']=$rec['total'];
					break;
				case "JUN" :
					$profitStat[5]['total']=$rec['total'];
					break;
				case "MAY" :
					$profitStat[4]['total']=$rec['total'];
					break;
				case "APR" :
					$profitStat[3]['total']=$rec['total'];
					break;
				case "MAR" :
					$profitStat[2]['total']=$rec['total'];
					break;
				case "FEB" :
					$profitStat[1]['total']=$rec['total'];
					break;
				case "JAN" :
					$profitStat[0]['total']=$rec['total'];
					break;
			} 
        }
		$profitStatics = $profitStat;
		ksort($profitStatics);
        $month = date("m");
        if ($this->getRequest()->getParam('monthprofit')) {
            $currentProfitYear = $this->getRequest()->getParam('monthprofit');
            $month = $this->getRequest()->getParam('mo');
        }
        $profitData = $objProfitModel->getProfitStaticsByMonth($currentProfitYear, $month);
        //echo "<pre>"; print_r($profitStatics); echo "</pre>"; die;

        foreach ($profitData as $key => $value) {

            $profitdate = $value['date'];
            $amount = $value['amount'];
            $total_profit = $value['total'];

            if (!isset($total[$profitdate])) {
                $total[$profitdate] = 0;
            }

            //$total[$profitdate] += $amount;
			$total[$profitdate] += $total_profit;
        }
        //echo "<pre>"; print_r($total); echo "</pre>"; die;

        $this->view->profityear = $currentProfitYear;
        $this->view->profitstatic = $profitStatics;
        $this->view->profitmonth = $month;
        $this->view->profitMonthstatic = $total;
    }

    public function editStatus() { 
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $mailer = Engine_Mailer_Mailer::getInstance();
        $objEmaillog = Admin_Model_Emaillog::getInstance();
        $objWithdrawalModel = Admin_Model_WithdrawalRequest::getInstance();
        $withdrawalId = $this->getRequest()->getParam('withdrawalId');
        $detail = $objWithdrawalModel->getWithdrawalDeatilsById($withdrawalId);
        $objUserAccount = Admin_Model_UserAccount::getInstance();
        $objUserTransactionsModel = Admin_Model_UserTransactions::getInstance();
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
        $userId = $detail['user_id'];
        $requested_amt = $detail['requested_amt'];
        $email = $detail['email'];
        $responseData = new stdClass();
//        echo "<pre>"; print_r($detail); //die;
        if ($detail['pay_method'] == 1) {
            $uid = $userId;
            $check = $objUserAccount->getUserAccountsDeatilsByID($uid);

            if ($check['balance_amt'] - $requested_amt > 19) {
                $objPaypal = Engine_Payment_Paypal_Paypal::getInstance();

                $recepientDetails = array();
                $recepientDetails[0]['email'] = $email;
                $recepientDetails[0]['amount'] = $requested_amt;

                $response = $objPaypal->MassPay($recepientDetails);
//                echo "<pre>"; print_r($response);
                $responsetransaction = $response['CORRELATIONID'];
                if ($response['ACK'] == "Success") {
                    $msg = "Support approved your withdraw request !!. An amount of $$requested_amt is successfully sent to provided paypal account.";
                    // echo "<pre>"; print_r($detail); echo "</pre>"; die;
                    $objNotification = Admin_Model_Notification::getInstance();
                    $data = array();
                    $data['send_to'] = $userId;
                    $data['sent_on'] = date('Y-m-d H:i:s');
                    $data['message'] = $msg;
                    $insert = $objNotification->insertNotification($data);
                    
                    $wdrUpdate['status'] = 1;
                    $wdrUpdate['pay_date'] = date('Y-m-d H:i:s');
                    $wdrUpdate['transaction_id'] = $responsetransaction;
                    $objWithdrawalModel->updateByID($withdrawalId, $wdrUpdate);
                    
                    $transactions['user_id'] = $userId;
                    $transactions['transaction_type'] = 'Paypal';
                    $transactions['transaction_amount'] = $requested_amt;
                    $transactions['confirmation_code'] = $response['CORRELATIONID'];
                    $transactions['description'] = 'Withdraw Amount';
                    $transactions['status'] = '1';
                    $transactions['request_type'] = '2';
                    $transactions['transaction_date'] = date('Y-m-d');
                    $objUserTransactionsModel->insertUseTransactions($transactions);
                    
                    $check = $objUserAccount->updateUserBalanceWithdrawn($userId, $requested_amt);
//                    var_dump($check);
                    $updateddetail = $objWithdrawalModel->getWithdrawalDeatilsById($withdrawalId);
                    $datereq = $updateddetail['pay_date'];
// echo "<pre>"; print_r($check);
                    $useremail = $email;
                    $message = "An amount of $$requested_amt has been successfully withdrawn on $datereq through paypal.";
                    $subject = "Withdraw request approved";
                    $topic = "Message from ".$this->_appSetting->title." Support";
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
                    $result = '';//$mailer->sendtemplate($template_name, $useremail, $checkname, $subject, $mergers);
// echo "<pre>"; print_r($result);die;
                    $insertdataemaillog = array(
                        'sent_email' => $useremail,
                        'sent_time' => date('Y-m-d H:i:s'),
                        'sent_template' => $template_name,
                        'message' => $message
                    );

                    $insertdataemaillog_result = $objEmaillog->insertEmailLog($insertdataemaillog);
                    
                    $responseData->code = 200;
                    $responseData->message = "Withrwal request completed successfully";
                }else{
                    $responseData->code = 198;
                    $responseData->message = "paypal request error";
                }
            } else {
                $responseData->code = 198;
                $responseData->message = "User account dont have sufficient amount to withdraw";
            }
        }
        
        echo json_encode($responseData);
    }

    public function cancelPaymentAction() {
        $mailer = Engine_Mailer_Mailer::getInstance();
        $objEmaillog = Admin_Model_Emaillog::getInstance();
        $objWithdrawalModel = Admin_Model_WithdrawalRequest::getInstance();
        $objUserAccount = Admin_Model_UserAccount::getInstance();
        if ($this->getRequest()->isPost()) {
            $this->_helper->_layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(TRUE);
            $method = $this->getRequest()->getPost('method');
            $withdrawalId = $this->getRequest()->getPost('uid');
            $requested_amt = $this->getRequest()->getPost('amt');

            $detail = $objWithdrawalModel->getWithdrawalDeatilsById($withdrawalId);
            $userId = $userId = $detail['user_id'];
            $result = $objWithdrawalModel->cancelstatus($withdrawalId);
            switch ($method) {
                case "sendmail":
                    $email = $this->getRequest()->getPost('email');
                    $subject = $this->getRequest()->getPost('subject');
                    $message = $this->getRequest()->getPost('message');
                    $username = $this->getRequest()->getPost('username');

                    $useremail = $email;
                    $message = $message;
                    $subject = $subject;
                    $topic = "Message from DraftDaily Support";
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
                    $result = '';//$mailer->sendtemplate($template_name, $useremail, $username, $subject, $mergers);

                    $response = new stdClass();
                    if (isset($result)) {
                        if (isset($result['reject_reason']) && $result['reject_reason'] != "") {
                            $response->code = "198";
                            $response->message = "Unable to send message :-" . $result['reject_reason'];
                            echo json_encode($response);
                        } else {
                            $insertdataemaillog = array(
                                'sent_email' => $email,
                                'sent_time' => date('Y-m-d H:i:s'),
                                'sent_template' => $template_name,
                                'message' => $subject
                            );

                            $insertdataemaillog_result = $objEmaillog->insertEmailLog($insertdataemaillog);
                            $response->code = "200";
                            $response->message = "Message Sent Successfully";
                            echo json_encode($response);
                            $msg = "Your request to withdraw money has been cancelled follow your mail for more details.";
                            // echo "<pre>"; print_r($detail); echo "</pre>"; die;
                            $objNotification = Admin_Model_Notification::getInstance();
                            $data = array();
                            $data['send_to'] = $userId;
                            $data['sent_on'] = date('Y-m-d H:i:s');
                            $data['message'] = $msg;
                            $insert = $objNotification->insertNotification($data);
                            // $result=$objWithdrawalModel->cancelstatus($withdrawalId) ;
                        }
                    } else {
                        $response->code = "198";
                        $response->message = "Error Occurred, Unable to send message";
                        echo json_encode($response);
                    }
                    break;
            }
        }
    }

    public function editStatusAction() { 
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
       
        $objEmaillog = Admin_Model_Emaillog::getInstance();
        $objWithdrawalModel = Admin_Model_WithdrawalRequest::getInstance();
        $objUserAccount = Admin_Model_UserAccount::getInstance();
        $objUserTransactionsModel = Admin_Model_UserTransactions::getInstance();
       
		$objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
		
        if ($this->getRequest()->isPost()) {
			
			$withdrawalIds = $this->getRequest()->getPost('withdrawalId');

			if(isset($withdrawalIds) && !empty($withdrawalIds)){
				
				
				foreach($withdrawalIds as $wid){
					
					$detail = $objWithdrawalModel->getWithdrawalDeatilsById($wid);
					$dfs_addr = $detail['account_no'];
					$amnt = $detail['requested_amt'];
					if(!empty($dfs_addr) && !empty($amnt)){
						$url ="http://139.162.189.133:3001/v1/api/make/withdraw";
						$res_data = $this->dfs_curl($url,$dfs_addr,$amnt);
						$res_data = json_decode($res_data,true);
						//echo "<pre>"; print_r($res_data); die;
						if(empty($res_data['err'])){
							
							if(!empty($res_data['data'])){
								$t_code = $res_data['data'];
							}else{
								$t_code = "0";
							}
							
							$userId = $detail['user_id'];
							$email = $detail['email'];
							$responseData = new stdClass();
						   
							$msg = "An amount of ".$amnt." DFSCoin has been successfully withdrawn on ".date('Y-m-d H:i:s');
							
							$objNotification = Admin_Model_Notification::getInstance();
							$data = array();
							$data['send_to'] = $userId;
							$data['sent_on'] = date('Y-m-d H:i:s');
							$data['message'] = $msg;
							$insert = $objNotification->insertNotification($data);
							
							$wdrUpdate['status'] = 1;
							$wdrUpdate['pay_date'] = date('Y-m-d H:i:s');
							$wdrUpdate['transaction_id'] = $t_code;
							
							$resp = $objWithdrawalModel->updateByID($wid, $wdrUpdate);
							if($resp){
								$transactions['user_id'] = $userId;
								$transactions['transaction_type'] = 'DFSCoin';
								$transactions['transaction_amount'] = $amnt;
								$transactions['confirmation_code'] = $t_code;
								$transactions['description'] = 'Withdraw Amount';
								$transactions['status'] = '1';
								$transactions['request_type'] = '2';
								$transactions['transaction_date'] = date('Y-m-d');
								$objUserTransactionsModel->insertUseTransactions($transactions);
							}
						}
						
					}
					
				}
				
			}
		}
		$this->_redirect('/admin/withdrawal-details');
	}
	
	public function dfs_curl($url,$toWallet,$amount) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "toWallet=$toWallet&amount=$amount");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 		
		curl_setopt($ch, CURLOPT_HEADER, false);		
		$result = curl_exec($ch);
		if($result === false){
			//die('Curl failed ' . curl_error());
		}	
		curl_close($ch);
		return $result;
    }
	
}
