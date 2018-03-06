<?php

/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';
require_once '../vendor/autoload.php';

class Static_StaticController extends Zend_Controller_Action {

    public function init() {
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
	
    public function contactUsAction() {
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$postmark_config = $config->getOption('postmark');
		$google_config = $config->getOption('google-capcha');
		$this->view->site_key = $google_config['site-key'];
		//echo "<pre>"; print_r($google_config);
        //$mailer = Engine_Mailer_Mailer::getInstance();
        $objSettingsModel = Application_Model_Settings::getInstance();
        $settingsData = $objSettingsModel->getSettings();
        $objEmaillog = Application_Model_Emaillog::getInstance();
        /* Sarika nayak  date:16/8/2014 description: to solve bug number 200  */
        if (isset($this->view->session->storage->user_id)) {
            $objUsers = Application_Model_Users::getInstance();
            $userId = $this->view->session->storage->user_id;
            if (isset($userId) && $userId != "") {
                $userDetails = $objUsers->getUserdetailsByUserId($userId);
                $this->view->session->storage->userName = $userDetails['user_name'];
                $this->view->data = $userDetails;
            }
        }
        /* --------------------------------------------------- */
        if (isset($this->view->session->storage->user_id)) {
            $objUsers = Application_Model_Users::getInstance();
            $userId = $this->view->session->storage->user_id;
            if (isset($userId) && $userId != "") {
                $userDetails = $objUsers->getUserdetailsByUserId($userId);
                $this->view->session->storage->userName = $userDetails['user_name'];
                $this->view->data = $userDetails;
            }
        }
		$this->view->settings = $settingsData;
        /* --------------------------------------------------- */
		if ($this->getRequest()->isPost()) {
			$captcha =  @$this->getRequest()->getPost('g-recaptcha-response');
        	if(!empty($captcha)) {
        		
        		//get verify response data
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
                
        		if($responseData->success) {
        			$template_name = 'support';
        			$email = $settingsData['email'];
        			$topic = $this->getRequest()->getParam('topic');
        			$useremail = $this->getRequest()->getPost('email');
        			$username = $this->getRequest()->getPost('username');
        			$subject = $this->getRequest()->getPost('subject');
        			$message = $this->getRequest()->getPost('message');
        			try{
        				$client = new Postmark\PostmarkClient($postmark_config['key']);
        				
        				$result = $client->sendEmailWithTemplate(
        					$postmark_config['email'],
        					$email,
        					$postmark_config['Support'], 
        					[
        						"subject" => $subject,
        						"usermail" => $useremail,
        						"message" => $message,
        						"topic" => $topic,
        					] 
        				);
        			} catch (Exception $e){
        			   //print_r($e); die;
        			}
        			
					if(isset($result)){
                        if(isset($result->message) && $result->message!="OK"){
                            $this->view->result= "Unable to send message :-".$result->message;
                        }else{
                            
                            $this->view->result = "Message Sent Successfully";
                            
                        }
                    }
        			
        			
        			
        			$insertdataemaillog = array(
        				'sent_email' => $email,
        				'sent_time' => date('Y-m-d H:i:s'),
        				'sent_template' => $template_name,
        				'message' => $message
        			);
        
        			$insertdataemaillog_result = $objEmaillog->insertEmailLog($insertdataemaillog);
        		} else {
        			$this->view->errMsg = 'Robot verification failed, please try again.';
        		} 
        	} else {
        		 $this->view->errMsg = 'Please click on the reCAPTCHA box.';
        	}
        
        }
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 12/07/2014
     * Description  : get fpp store tickets and offer
     */
    public function storeAction() {
        $objStoreModel = Static_Model_Store::getInstance();
        $objTicketModel = Application_Model_TicketSystem ::getInstance();
        $userId = $this->view->session->storage->user_id;
        $fpp = $objStoreModel->getFppForCurrentUser($userId);
		
		$objUserAccModel = Application_Model_UserAccount::getInstance();
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
		
        if ($fpp) {
            $this->view->fpp = $fpp['fpp'];
        }
        $product = $objStoreModel->getStoreProductDetails();
        $ticket = $objTicketModel->ticketSaleDetails();

        if ((isset($product)) && isset($ticket)):
            $storeDetails = array_merge($product, $ticket);
        endif;
        if (isset($product) && !isset($ticket)):
            $storeDetails = $product;
        endif;

        if (!isset($product) && isset($ticket)):
            $storeDetails = $ticket;
        endif;

        if (isset($storeDetails)):
            shuffle($storeDetails);
            if ($storeDetails):
                $this->view->storedata = $storeDetails;

            endif;
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 12/07/2014
     * Description  : get details for available referals or else update referals
     */
    public function referFriendAction() {
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$postmark_config = $config->getOption('postmark');
		
        $objEmaillog = Application_Model_Emaillog::getInstance();
        //$mailer = Engine_Mailer_Mailer::getInstance();
        $userId = $this->view->session->storage->user_id;
        $objReferalModel = Static_Model_Referals::getInstance();
        $objSettingsModel = Application_Model_Settings::getInstance();
        $objUserModel = Application_Model_Users::getInstance();
        if ($this->getRequest()->isPost()) {


            $template_name = 'refer_a_friend';

            $email = $this->getRequest()->getPost('email');

            $message = $this->getRequest()->getPost('message');

            $sendfor = $this->getRequest()->getPost('send');
            $challenge = $this->getRequest()->getPost('btn-challange');

            $referlink = "http://" . $_SERVER['HTTP_HOST'] . "/affiliate/" . $this->view->session->storage->user_name;
            $username = $this->view->session->storage->user_name;
            if ($sendfor == "reminder") {
                $check = $this->getRequest()->getParam('checkbox');
                $inviteData = $objReferalModel->getReferalDataByIDs($check);
                foreach ($inviteData as $userDetails) {
                    $template_name = 'refer_a_friend';
                    $username = $this->view->session->storage->user_name;
                    $subject = 'Reminder for Refer-a-Friend Program from DrafDaily';
                    $referlink = "http://" . $_SERVER['HTTP_HOST'] . "/affiliate/" . $this->view->session->storage->user_name;

                    $email = $userDetails['email'];
					try{
						$client = new Postmark\PostmarkClient($postmark_config['key']);
						
						$result = $client->sendEmailWithTemplate(
							$postmark_config['email'],
							$email,
							$postmark_config['refer_a_friend'], 
							[
								"site_name" => $postmark_config['site_name'],
								"username" => $username,
								"message" => $message,
								"refer_link" => $referlink,
								"subject"=>$subject,
							] 
						);
					} catch (Exception $e){
					  // print_r($e); die;
					}
					
                    $insertdataemaillog = array(
                        'sent_email' => $email,
                        'sent_time' => date('Y-m-d H:i:s'),
                        'sent_template' => $template_name,
                        'message' => $subject
                    );

                    $insertdataemaillog_result = $objEmaillog->insertEmailLog($insertdataemaillog);
                }

                $objReferalModel->updateReminder($check);
            }


            if ($sendfor == "email") {
                $resultemail = $objReferalModel->validateReferFriendEmail($email);

                if ($email == $resultemail['email']) {
                    $this->view->messagemail = "Invitation already sent";
                } else {
                    $response = $objUserModel->validateUserEmail($email);

                    if (!empty($response)) {

                        $this->view->message = "your friend is already registered,invite another friend";
                    } else {
                        $template_name = "refer_a_friend";
                        $subject = 'Friend Referal from DraftDaily';
						try{
							$client = new Postmark\PostmarkClient($postmark_config['key']);
							$result = $client->sendEmailWithTemplate(
								$postmark_config['email'],
								$email,
								$postmark_config['refer_a_friend'], 
								[
									"site_name" => $postmark_config['site_name'],
									"username" => $username,
									"message" => $message,
									"refer_link" => $referlink,
									"subject"=>$subject,
								] 
							);
						} catch (Exception $e){
						   //print_r($e); die;
						}
					   
                        $insertdataemaillog = array(
                            'sent_email' => $email,
                            'sent_time' => date('Y-m-d H:i:s'),
                            'sent_template' => $template_name,
                            'message' => $subject
                        );

                        $insertdataemaillog_result = $objEmaillog->insertEmailLog($insertdataemaillog);
                        $referalData = array();
                        $referalData['email'] = $email;
                        $referalData['ref_by'] = $this->view->session->storage->user_id;
                        $referalData['ref_date'] = date('Y-m-d');
                        $referalData['req_count'] = 1;
                        $objReferalModel->addReferal($referalData);
                    }
                }
            }

            if ($challenge == "CHALLENGE >>") {
                $check = $this->getRequest()->getParam('checkbox');
                $inviteData = $objReferalModel->getReferalDataByIDs($check);
                foreach ($inviteData as $userDetails) {
                    $template_name = 'refer_a_friend';
                    $username = $this->view->session->storage->user_name;
                    $subject = 'You Friend Challenged You to play Fantasy sport on DraftDaily';
                    $referlink = "http://" . $_SERVER['HTTP_HOST'] . "/affiliate/" . $this->view->session->storage->user_name;
                    
                    $email = $userDetails['email'];
					try{
						$client = new Postmark\PostmarkClient($postmark_config['key']);
						
						$result = $client->sendEmailWithTemplate(
							$postmark_config['email'],
							$email,
							$postmark_config['refer_a_friend'], 
							[
								"site_name" => $postmark_config['site_name'],
								"username" => $username,
								"message" => $message,
								"refer_link" => $referlink,
								"subject"=>$subject,
							] 
						);
					} catch (Exception $e){
					   //print_r($e); die;
					}
                    $insertdataemaillog = array(
                        'sent_email' => $email,
                        'sent_time' => date('Y-m-d H:i:s'),
                        'sent_template' => $template_name,
                        'message' => $subject
                    );

                    $insertdataemaillog_result = $objEmaillog->insertEmailLog($insertdataemaillog);
                }
                $objReferalModel->updateReminder($check);
            }
        }


        $referDetails = $objReferalModel->getReferalDeatails($userId);
        if ($referDetails) {
            $this->view->referals = $referDetails;
        }
        $settingsData = $objSettingsModel->getSettings();
        $this->view->settings = $settingsData;
    }

    public function overviewAction() {
        
        
        
    }

    public function storeAjaxHandlerAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $objTicketModel = Application_Model_TicketSystem::getInstance();
        $objUsrAccModel = Application_Model_UserAccount::getInstance();

        $method = $this->getRequest()->getPost('method');
        $userId = $this->view->session->storage->user_id;
        switch ($method) {
            case "useTicket" :
                $tcode = $this->getRequest()->getPost('tcode');
                $response = $objTicketModel->getTicketByCode($tcode);
                $accData = $objUsrAccModel->getUserTickets($userId);
                $reply = new stdClass();
                if ($response) {
                    if (isset($response['ticket_for']) && $response['ticket_for'] != null) {
                        $ticketUsers = json_decode($response['ticket_for'], true);
                        $found = in_array($userId, $ticketUsers);  //search user already have this ticket or not
                        if ($found != false) {

                            $reply->message = 'You Already Used This Ticket Code';
                            $reply->code = 196;
                            echo json_encode($reply);
                        } else {
                            array_push($ticketUsers, $userId); // insert user id in ticket users data

                            $updateUsers = json_encode($ticketUsers, true);
                            $ok = $objTicketModel->updateTicketUsers($updateUsers, $response['ticket_id']); //update ticket users data

                            if ($ok) {
                                $userTickets = array();
                                if (isset($accData['available_tickets']) && $accData['available_tickets'] != null) {
                                    $userTickets = json_decode($accData['available_tickets'], true);

                                    array_push($userTickets, $response['ticket_id']);
                                } else {
                                    array_push($userTickets, $response['ticket_id']);
                                }

                                $updateAccData['available_tickets'] = json_encode($userTickets, true);
                                $ucheck = $objUsrAccModel->updateUserAccount($userId, $updateAccData);
                                if ($ucheck) {
                                    $reply->message = 'Ticket successFully added into your Account';
                                    $reply->code = 200;
                                    echo json_encode($reply);
                                }
                            }
                        }
                    } else {
                        $ticketUsers[] = $userId;
                        $updateUsers = json_encode($ticketUsers, true);
                        $ok = $objTicketModel->updateTicketUsers($updateUsers, $response['ticket_id']); //update ticket users data

                        if ($ok) {
                            $userTickets = array();
                            if (isset($accData['available_tickets']) && $accData['available_tickets'] != null) {
                                $userTickets = json_decode($accData['available_tickets'], true);
                                array_push($userTickets, $response['ticket_id']);
                            } else {
                                array_push($userTickets, $response['ticket_id']);
                            }
                            $updateAccData['available_tickets'] = json_encode($userTickets, true);
                            $ucheck = $objUsrAccModel->updateUserAccount($userId, $updateAccData);
                            if ($ucheck) {
                                $reply->message = 'Ticket successFully added into your Account';
                                $reply->code = 200;
                                echo json_encode($reply);
                            }
                        }
                    }
                } else {
                    $reply->message = 'Invalid Ticket Code';
                    $reply->code = 198;
                    echo json_encode($reply);
                }
                break;
            default :
                break;
        }
    }

    public function chatAction() {
        
    }

}
