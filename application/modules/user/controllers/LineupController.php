<?php

/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class User_LineupController extends Zend_Controller_Action {

    public function init() {
        
    }

    public function createLineupAction() {
        
    }

    /**
     * Developer     : Vivek Chaudhari   
     * Date          : 21/08/2014
     * Description   : save lineup and update lineup and samelineupcontests functions 
     * ------------------------------------------------------------------------------------------------- 
     */
     public function lineupAction() {

        if ($li = $this->getRequest()->getParam('li')) {
            $this->view->createlineup = "1";
        }

        $objLineupModel = Application_Model_Lineup::getInstance();
        $objGamePlayerModel = Application_Model_GamePlayers::getInstance();
        $objUserLineupModel = Application_Model_UserLineup::getInstance();
        $objContestModel = Application_Model_Contests::getInstance();
        $objPlayerStatsModel = Application_Model_PlayerStats::getInstance();
        $userId = $this->view->session->storage->user_id;

        
        $currentTime = date('Y-m-d H:i:s',time());
        $diffTime = date('Y-m-d H:i:s',strtotime($currentTime."-1 day"));
        $userLineups = $objLineupModel->getLineupByUid($userId,$diffTime);
		//echo "<pre>"; print_r($userLineups); die;
        if(isset($userLineups) && !empty($userLineups)){
            foreach($userLineups as $key=>$lineup){ 
                $lineupId = $lineup['lineup_id'];
                $userLineups[$key]['l_status'] = 0;
                $lineupEntry = $objUserLineupModel->getLineupUseCount($lineupId);
                if($lineupEntry!=0){//entry exist
                   $conData = $objUserLineupModel->getAllContestByLineupId($lineupId);
                   if(!empty($conData)){
                       $userLineups[$key]['l_status'] = $conData[0]['con_status'];
                       $userLineups[$key]['contest_name'] = $conData[0]['contest_name'];
                   }
                }else{
					$userLineups[$key]['contest_name'] = "Contest Not Available";
				}
                $userLineups[$key]['entries'] = $lineupEntry;
                $playerIds = json_decode($lineup['player_ids'], true);
                $position = json_decode($lineup['pos_details'], true);
				if(!empty($playerIds)){
					$playerDetails = $objGamePlayerModel->getPlayerListBySport($playerIds,$lineup['sports_id']);
					$playerDetail = array();
					foreach ($playerDetails as $pvalue) {
						$payerDetailsArray = json_decode($pvalue['plr_details'], true);
						$teamname = $payerDetailsArray['team_name'];
						$team = $objPlayerStatsModel->getTeamDetailsTeamName($teamname);
						$pkey = array_search($payerDetailsArray['id'], $playerIds);
						if (!empty($team)) {
							$playerDetail[$pkey]['team_id'] = $team['team_id'];
						}
						$playerDetail[$pkey]['id'] = $payerDetailsArray['id'];
						$playerDetail[$pkey]['name'] = $payerDetailsArray['name'];
						$playerDetail[$pkey]['position'] = $position[$payerDetailsArray['id']]; //$payerDetailsArray['pos_code'];
						$playerDetail[$pkey]['fppg'] = $pvalue['fppg'];
					}
					$objParser = Engine_Utilities_GameXmlParser::getInstance();
					 if ($lineup['sports_id'] == '1') {
						$playerDetail = $objParser->arrangeNFLineUp($playerDetail);
					 }else if ($lineup['sports_id'] == '2') {
						$playerDetail = $objParser->arrangeMLBLineUp($playerDetail);
					} else if ($lineup['sports_id'] == '3') {
						$playerDetail = $objParser->arrangeNBALineUp($playerDetail);
					} else if ($lineup['sports_id'] == '4') {
						$playerDetail = $objParser->arrangeNHLineUp($playerDetail);
					} else {
						ksort($playerDetail);
					}
					$userLineups[$key]['player_details'] = $playerDetail;
				}                
            }
            $this->view->lineupdetails = $userLineups;
        }else{
            $this->view->error = "Sadly you do not have any Lineups";
        }

        //for sports list
        $objSports = Application_Model_Sports::getInstance();
        $date = date('Y-m-d');
        $gameslist = $objSports->getSportsAndContest($date);
		//echo "<pre>"; print_r($gameslist); die;
        $contestData = array();
        $finalContestData = array();
        $i = 0;
        if ($gameslist) {
            foreach ($gameslist as $sport) {
                $data = json_decode($sport['game_stat'], true);
                $contestData[$i]['sports_id'] = $sport['sports_id'];
                $contestData[$i]['display_name'] = $sport['display_name'];
                $time = array();
                if (isset($data['match'])) {
                    foreach ($data['match'] as $val) {
                        $time[] = date('M d Y', strtotime($val['formatted_date'])) . ' ' . (string) $val['time'] . ' ' . $data['timezone'];
                    }
                }
                $contestData[$i]['time'] = array_count_values($time);
                $i++;
                $finalContestData = $this->arrangeContestSportsTimeBySports($contestData);
            }
            foreach ($finalContestData as $fkey => $fval) { //to make sport timing in order
                $array = $fval['time'];
                $value = array();
                foreach ($array as $key => $row) {
                    $value[$key] = strtotime($key);
                }
                array_multisort($value, SORT_ASC, $array);
                $finalContestData[$fkey]['time'] = $array;
            }
            if ($finalContestData) {
                $this->view->sportlist = $finalContestData;
            }
			
        }
        //end sport list 
    }
    public function lineup() {

        if ($li = $this->getRequest()->getParam('li')) {
            $this->view->createlineup = "1";
        }

        $objLineupModel = Application_Model_Lineup::getInstance();
        $objGamePlayerModel = Application_Model_GamePlayers::getInstance();
        $objUserLineupModel = Application_Model_UserLineup::getInstance();
        $objContestModel = Application_Model_Contests::getInstance();
        $objPlayerStatsModel = Application_Model_PlayerStats::getInstance();
        $userId = $this->view->session->storage->user_id;
//        $lineUpDetails = $objLineupModel->getMyLineupDetails($userId);
//        $result = $objLineupModel->getUserLineups($userId);
        
        $currentTime = date('Y-m-d H:i:s',time());
        $diffTime = date('Y-m-d H:i:s',strtotime($currentTime."-1 day"));
        $userLineups = $objLineupModel->getLineupByUid($userId,$diffTime);
//        echo "<pre>"; print_r($userLineups); die;
        if(isset($userLineups) && !empty($userLineups)){
            foreach($userLineups as $key=>$lineup){
                $lineupId = $lineup['lineup_id'];
                $userLineups[$key]['l_status'] = 0;
                $lineupEntry = $objUserLineupModel->getLineupUseCount($lineupId);
                if($lineupEntry!=0){//entry exist
                   $conData = $objUserLineupModel->getAllContestByLineupId($lineupId);
//                   echo "<pre>"; print_r($conData); die;
                   if(!empty($conData)){
                       $userLineups[$key]['l_status'] = $conData[0]['con_status'];
                   }
                }
                $userLineups[$key]['entries'] = $lineupEntry;
                $playerIds = json_decode($lineup['player_ids'], true);
                $position = json_decode($lineup['pos_details'], true);
                $playerDetails = $objGamePlayerModel->getPlayerList($playerIds);
                $playerDetail = array();
                 foreach ($playerDetails as $pvalue) {
                    $payerDetailsArray = json_decode($pvalue['plr_details'], true);
                    $teamname = $payerDetailsArray['team_name'];
                    $team = $objPlayerStatsModel->getTeamDetailsTeamName($teamname);
                    $pkey = array_search($payerDetailsArray['id'], $playerIds);
                    if (!empty($team)) {
                        $playerDetail[$pkey]['team_id'] = $team['team_id'];
                    }
                    $playerDetail[$pkey]['id'] = $payerDetailsArray['id'];
                    $playerDetail[$pkey]['name'] = $payerDetailsArray['name'];
                    $playerDetail[$pkey]['position'] = $position[$payerDetailsArray['id']]; //$payerDetailsArray['pos_code'];
                    $playerDetail[$pkey]['fppg'] = $pvalue['fppg'];
                }
                        $objParser = Engine_Utilities_GameXmlParser::getInstance();
                         if ($lineup['sports_id'] == '1') {
                            $playerDetail = $objParser->arrangeNFLineUp($playerDetail);
                         }else if ($lineup['sports_id'] == '2') {
                            $playerDetail = $objParser->arrangeMLBLineUp($playerDetail);
                        } else if ($lineup['sports_id'] == '3') {
                            $playerDetail = $objParser->arrangeNBALineUp($playerDetail);
                        } else if ($lineup['sports_id'] == '4') {
                            $playerDetail = $objParser->arrangeNHLineUp($playerDetail);
                        } else {
                            ksort($playerDetail);
                        }
                        $userLineups[$key]['player_details'] = $playerDetail;
                
            }
            $this->view->lineupdetails = $userLineups;
        }else{
            $this->view->error = "Sadly you do not have any Lineups";
        }
//       echo "<pre>"; print_r($userLineups); die;  
        //for sports list
        $objSports = Application_Model_Sports::getInstance();
        $date = date('Y-m-d');
        $gameslist = $objSports->getSportsAndContest($date);
        $contestData = array();
        $finalContestData = array();
        $i = 0;
        if ($gameslist) {
            foreach ($gameslist as $sport) {
                $data = json_decode($sport['game_stat'], true);
                $contestData[$i]['sports_id'] = $sport['sports_id'];
                $contestData[$i]['display_name'] = $sport['display_name'];
                $time = array();
                if (isset($data['match'])) {
                    foreach ($data['match'] as $val) {
                        $time[] = date('M d Y', strtotime($val['formatted_date'])) . ' ' . (string) $val['time'] . ' ' . $data['timezone'];
                    }
                }
                $contestData[$i]['time'] = array_count_values($time);
                $i++;
                $finalContestData = $this->arrangeContestSportsTimeBySports($contestData);
            }
            foreach ($finalContestData as $fkey => $fval) { //to make sport timing in order
                $array = $fval['time'];
                $value = array();
                foreach ($array as $key => $row) {
                    $value[$key] = strtotime($key);
                }
                array_multisort($value, SORT_ASC, $array);
                $finalContestData[$fkey]['time'] = $array;
            }
            if ($finalContestData) {
                $this->view->sportlist = $finalContestData;
            }
        }
        //end sport list 
    }

    public function lineuphandlerAction() {
        $ok = 0;
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->getRequest()->isPost()) {
            $method = $this->getRequest()->getParam('method');

            switch ($method) {
                case 'sportslist':
                    $objSports = Application_Model_Sports::getInstance();
                    $date = date('Y-m-d');
                    $sports = $objSports->getSportsAndContest($date);
					
                    $contestData = array();
                    $finalContestData = array();
                    $i = 0;
                    if ($sports) {
                        foreach ($sports as $sport) {
                            $data = json_decode($sport['game_stat'], true);
                            $contestData[$i]['sports_id'] = $sport['sports_id'];
                            $contestData[$i]['display_name'] = $sport['display_name'];
                            $time = array();

                            if (isset($data['match'])) {
                                foreach ($data['match'] as $val) {
                                    $time[] = date('M d Y', strtotime($val['formatted_date'])) . ' ' . (string) $val['time'] . ' ' . $data['timezone'];
                                }
                            }
                            $contestData[$i]['time'] = array_count_values($time);
                            $i++;

                            $finalContestData = $this->arrangeContestSportsTimeBySports($contestData);
                        }
                        foreach ($finalContestData as $fkey => $fval) { //to make sport timing in order
                            $array = $fval['time'];
                            $value = array();
                            foreach ($array as $key => $row) {
                                $value[$key] = strtotime($key);
                            }
                            array_multisort($value, SORT_ASC, $array);
                            $finalContestData[$fkey]['time'] = $array;
                        }
                        if ($finalContestData) {
                            echo json_encode($finalContestData);
                        }
                    }
                    break;
                case 'getgameslist':
                    $gametype = $this->getRequest()->getParam('gametype');
                    $objParser = Engine_Utilities_GameXmlParser::getInstance();
                    $response = $objParser->getGameLists($gametype);
                    $data = array();
                    $i = 0;
                    foreach ($response as $key => $value) {
                        if (strtotime($value['match_on']) >= strtotime(date("M.d.Y"))) {
                            @$data[$i]['match_date'] = $value['match_on'];
                            @$data[$i]['match_time'] = $value['match'][0]['time'];
                            @$data[$i]['match_count'] = count($value['match']);
                            $i++;
                        }
                    }
                    if (!empty($data)) {
                        echo json_encode($data);
                    }
                    break;

                    //vivek chaudhari
                case 'savelineup':

                    $objLineup = Application_Model_Lineup::getInstance();
                    $objContest = Application_Model_Contests::getInstance();
                    $objUserAccount = Application_Model_UserAccount::getInstance();
                    $objUsersModel = Application_Model_Users::getInstance();
                    $objUserLineupModel = Application_Model_UserLineup::getInstance();
                    $objTicketModel = Application_Model_TicketSystem::getInstance();
                    $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
                    $objFacebookModel = Engine_Facebook_Facebookclass::getInstance();
                    $objCore = Engine_Core_Core::getInstance();
                    $this->_appSetting = $objCore->getAppSetting();

                    
					$ticketId = $this->getRequest()->getParam('ticketid');
                    $ticketStatus = $this->getRequest()->getParam('tstatus');
					
                    $lineup = $this->getRequest()->getParam('lineup');
                    $sports_id = $this->getRequest()->getParam('sports_id');
                    $conid = $this->getRequest()->getParam('conid');
                    $start_time = $this->getRequest()->getParam('start_time');
                    $remSalary = $this->getRequest()->getParam('rem_salary');
                    $current_date = date('Y-m-d H:i:s');
                    $end_time = $this->getRequest()->getParam('end_time'); // Manoj (29th Oct 14)
                    $pos_details = $this->getRequest()->getParam('pos_details');
					
                    $contestDetails = $objContest->getContestsDetailsById($conid);

                    $objSettingsModel = Application_Model_Settings::getInstance();
                   
                    $plrPosDet = array();
					
					if(!empty($pos_details)){
						array_walk_recursive($pos_details, function($key, $val) use (&$plrPosDet) {
                                $plrPosDet[$val] = $key;
                            });
					}
                    

                    $userId = $this->view->session->storage->user_id;
                    
                    $userLineup = array('sports_id' => $sports_id,
                        'start_time' => $start_time,
                        'player_ids' => json_encode($lineup),
                        'pos_details' => json_encode($plrPosDet),
                        'rem_salary' => $remSalary,
                        'created_by' => $userId
                    ); 
                     
                   $userData = $objUsersModel->getUserDetailsByUserId($userId);
                   
                   $response = new stdClass();
                   if(!empty($userData)){
                       $allowEntry = true; $allowStatus = 1; //default entry
                       
                        // check user having the valid ticket to enter tne contest
                        $objTicketUserModel = Application_Model_TicketUsers::getInstance();
                        if (isset($ticketStatus) && $ticketStatus != 0 && isset($ticketId) && $ticketId != 0) { // check user is using ticket
                            $ticketUser['user_id'] = $userId;
                            $ticketUser['ticket_id'] = $ticketId;
                            $ticketUser['contest_id'] = $conid;
                            $ticketUser['use_date'] = date('Y-m-d');
                            $objTicketUserModel->insertTicketUser($ticketUser);
                            $allowStatus = 3; // entry by ticket
                        }
                      
                       
                        if($allowStatus ==1){
                            if (isset($contestDetails['offers_to']) && $contestDetails['offers_to'] != null) { // check user is offered for this contest entry
								$offeredUsers = json_decode($contestDetails['offers_to'], true);
								$checkoffer = in_array($userId, $offeredUsers);
								if ($checkoffer != false) {
									$allowEntry = true;
									$allowStatus = 2; // entry by offer
								}
                            }
                        }
                       
						if($allowStatus ==1){
							if($contestDetails['entry_fee']!=0){ //check for user balance
								if($contestDetails['entry_fee'] > $userData['balance_amt']){
									$allowEntry = false;
									$response->code = 198;
									$response->message = "You do not have sufficient amount to Make an Entry";
								}
							}	
						}
						
						if($allowEntry){ // check of contest entry limit
                           if($contestDetails['play_limit']!=0){
                               if ($contestDetails['total_entry'] == $contestDetails['play_limit']) {
                                    $allowEntry = false;
                                    $response->code = 198;
                                    $response->message = "ContestEntry is Full";
                               }
                           }
                        }
                       
						if($allowEntry){
							$entryCheck = $objUserLineupModel->getUserContestEntry($userId,$conid);
							if(isset($entryCheck) && !empty($entryCheck)){
								$entryCount = count($entryCheck);
								if($contestDetails['challenge_limit']!=0){
									if($entryCount == $contestDetails['challenge_limit']){
										$allowEntry = false;
										$response->code = 198;
										$response->message = "You already entered into this contest";
									}
								}else{
									$allowEntry = false;
									$response->code = 198;
									$response->message = "You already entered into this contest";
								}
							}
						}
						if($allowEntry){
                         //check user entry exist
                         
							$lineupId = $objLineup->insertLineup($userLineup); 
							if (isset($lineupId)) {
								
								 //facebook post======================
								$settings = $objSettingsModel->getSettings();
								$link = $this->_appSetting->hostLink;
								$message = "Join a contest in " . $this->_appSetting->appName . "! " . $contestDetails['contest_name'];
								$name = $this->_appSetting->title;
								$description = $settings['fb_desc'];
								$fbID = $this->view->session->storage->fb_id;
								$fbToken = $this->view->session->storage->fb_token;
								if (isset($fbID) && !empty($fbID) && $fbID != null && isset($fbToken) && !empty($fbToken) && $fbToken != null) {
									$objFacebookModel->autopost($fbID, $fbToken, $link, $message, $name, $description);
								}
								//facebook post end------------------
								$user_lineup = array('lineup_id' => $lineupId,
									'contest_id' => $conid,
									'status' => '1',
									'created_date' => $current_date);
								$objUserLineupModel->inserUserLineup($user_lineup);
								
								if($allowStatus == 1){ // entry by balance amount
									$objUserAccount->updateUserBalanceWithdrawn($userId, $contestDetails['entry_fee']);
									$this->view->session->storage->userBalance = $this->view->session->storage->userBalance - $contestDetails['entry_fee'];

									$transactions['user_id'] = $userId;
									$transactions['transaction_type'] = 'From Balance';
									$transactions['transaction_amount'] = $contestDetails['entry_fee'];
									$transactions['confirmation_code'] = 'N/A';
									$transactions['description'] = 'Entry Fee';
									$transactions['status'] = '1';
									$transactions['request_type'] = '6';
									$transactions['transaction_date'] = date('Y-m-d');

//                                    $objUserTransactionsModel->insertUseTransactions($transactions);
									$transactionId = $objUserTransactionsModel->insertUseTransactions($transactions);
									if(isset($transactionId)){
										//changes for contest details in transaction history (vivek 3rdOct15)
										$contestTxData['transaction_id'] = $transactionId;
										$contestTxData['contest_id'] = $contestDetails['contest_id'];
										$contestTxData['user_id'] = $userId;

										$objContestTransactiuonModel = Application_Model_ContestTransactions::getInstance();
										$objContestTransactiuonModel ->insertConTransaction($contestTxData);
										//end
									}
								} elseif($allowStatus == 3) {
									
									$transactions['user_id'] = $userId;
									$transactions['transaction_type'] = 'Ticket Code';
									$transactions['transaction_amount'] = 0;
									$transactions['confirmation_code'] = 'N/A';
									$transactions['description'] = 'Ticket Code Applied';
									$transactions['status'] = '1';
									$transactions['request_type'] = '3';
									$transactions['transaction_date'] = date('Y-m-d');

//                                    $objUserTransactionsModel->insertUseTransactions($transactions);
									$transactionId = $objUserTransactionsModel->insertUseTransactions($transactions);
									if(isset($transactionId)){
										//changes for contest details in transaction history
										$contestTxData['transaction_id'] = $transactionId;
										$contestTxData['contest_id'] = $contestDetails['contest_id'];
										$contestTxData['user_id'] = $userId;

										$objContestTransactiuonModel = Application_Model_ContestTransactions::getInstance();
										$objContestTransactiuonModel->insertConTransaction($contestTxData);
										//end
									}
									
								}
								
								$objContest->updateTotalEntry($conid);
								$response->code = 200;
								$response->data= $lineupId;
							}else{
								$response->code = 198;
								$response->message = "Error Occured, Please try again";
							}
						}
					} else {
                       $response->code = 198;
                       $response->message = "Error Occured, Please try again";
                    }
                   
                    echo json_encode($response,true);

                    break;

                case 'savelineup_old':

                    $objLineup = Application_Model_Lineup::getInstance();
                    $objContest = Application_Model_Contests::getInstance();
                    $objUserAccount = Application_Model_UserAccount::getInstance();
                    $objUserLineupModel = Application_Model_UserLineup::getInstance();
                    $objTicketModel = Application_Model_TicketSystem::getInstance();
                    $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
                    $objFacebookModel = Engine_Facebook_Facebookclass::getInstance();
                    $objCore = Engine_Core_Core::getInstance();
                    $this->_appSetting = $objCore->getAppSetting();

                    $lineup = $this->getRequest()->getParam('lineup');
                    $sports_id = $this->getRequest()->getParam('sports_id');
                    $conid = $this->getRequest()->getParam('conid');
                    $start_time = $this->getRequest()->getParam('start_time');
                    $remSalary = $this->getRequest()->getParam('rem_salary');
                    $current_date = date('Y-m-d H:i:s');
                    $end_time = $this->getRequest()->getParam('end_time'); // Manoj (29th Oct 14)
                    $pos_details = $this->getRequest()->getParam('pos_details');
//                    echo $conid; die;
                    $contestDetails = $objContest->getContestFeeById($conid);

                    $objSettingsModel = Application_Model_Settings::getInstance();
                    //facebook post======================
                    $settings = $objSettingsModel->getSettings();
                    $link = $this->_appSetting->hostLink;
                    $message = "Join a contest in " . $this->_appSetting->appName . "! " . $contestDetails['contest_name'];
                    $name = $this->_appSetting->title;
                    $description = $settings['fb_desc'];
                    $fbID = $this->view->session->storage->fb_id;
                    $fbToken = $this->view->session->storage->fb_token;
                    if (isset($fbID) && !empty($fbID) && $fbID != null && isset($fbToken) && !empty($fbToken) && $fbToken != null) {
                        $objFacebookModel->autopost($fbID, $fbToken, $link, $message, $name, $description);
                    }
                    //facebook post end------------------
                    $plrPosDet = array();

                    array_walk_recursive($pos_details, function($key, $val) use (&$plrPosDet) {
                                $plrPosDet[$val] = $key;
                            });

//echo "<pre>"; print_r($this->view->session->storage); die;
                    $userId = $this->view->session->storage->user_id;
                    $accData = $objUserAccount->getAccountByUserId($userId);
                    $userBonusAmount = $accData['bonus_amt'];
//echo "<pre>"; print_r($accData); die;

                    $conData = $objContest->getContestsDetailsById($conid);
                    $offered = 0;
                    if (isset($conData['offers_to']) && $conData['offers_to'] != null) {
                        $offeredUsers = json_decode($conData['offers_to'], true);
                        $checkoffer = in_array($userId, $offeredUsers);
                        if ($checkoffer != false) {
                            $offered = 1;
                        }
                    }
                    $userLineup = array('sports_id' => $sports_id,
                        'start_time' => $start_time,
                        'player_ids' => json_encode($lineup, true),
                        'pos_details' => json_encode($plrPosDet, true),
                        'rem_salary' => $remSalary,
                        'created_by' => $userId
//                        'end_time' => $end_time,
                    ); // Manoj (29th Oct 14)
//              echo "<pre>";  print_r($userLineup); echo $offered; die;
                    if ($contestDetails['play_limit'] != 0) { // check unlimited entry
                        //check total entry should be under play limit
                        if ($contestDetails['total_entry'] < $contestDetails['play_limit']) {

                            $lineupId = $objLineup->insertLineup($userLineup);

                            if ($lineupId) {
                                if (isset($offered) && $offered != 1) { //for offered users
                                    if ($contestDetails['ticket_id']) {
//                                        $accData = $objUserAccount->getAccountByUserId($userId);
                                        $userTickets = json_decode($accData['available_tickets'], true);
                                        $key = array_search($contestDetails['ticket_id'], $userTickets);
                                        unset($userTickets[$key]);
                                        $updateAccData['available_tickets'] = json_encode($userTickets);
                                        $objUserAccount->updateUserAccount($userId, $updateAccData);

//                                    $ticketData = $objTicketModel->getTicketDetailsById($contestDetails['ticket_id']);    
//                                    //echo "<pre>"; print_r($ticketData); echo "</pre>"; die;
//                                    $tdata = json_decode($ticketData['ticket_for'],true);                                
//                                    $key   = array_search($userId,$tdata);                                    
//                                    unset($tdata[$key]);        bvb                            
//                                    $filterdata = json_encode($tdata);
//                                    $objTicketModel->updateTicketUsers($filterdata,$contestDetails['ticket_id']);
                                    } else if ($contestDetails && $contestDetails['entry_fee'] > 0) {

                                        if ($userBonusAmount != 0) {

                                            if ($userBonusAmount >= $contestDetails['entry_fee']) {

                                                //$objUserAccount->updateUserFppAdded($userId,$contestDetails['fpp']);

                                                $transactions['user_id'] = $userId;
                                                $transactions['transaction_type'] = 'From Bonus';
                                                $transactions['transaction_amount'] = $contestDetails['entry_fee'];
                                                $transactions['confirmation_code'] = 'N/A';
                                                $transactions['description'] = 'Entry Fee(Bonus)';
                                                $transactions['status'] = '1';
                                                $transactions['request_type'] = '6';
                                                $transactions['transaction_date'] = date('Y-m-d');

                                                $objUserTransactionsModel->insertUseTransactions($transactions);

                                                $lineupUpdateData = array('bonus' => $contestDetails['entry_fee']);
                                                $objLineup->updateLineup($lineupUpdateData, $lineupId);
                                                $objUserAccount->updateUserBonusAmount($userId, $contestDetails['entry_fee']);
                                                $this->view->session->storage->userBonus = $userBonusAmount - $contestDetails['entry_fee'];
                                            } else {
                                                $remAmount = $contestDetails['entry_fee'] - $userBonusAmount;
                                                $objUserAccount->updateUserBalanceWithdrawn($userId, $remAmount);
                                                //$objUserAccount->updateUserFppAdded($userId,$contestDetails['fpp']);

                                                $transactions['user_id'] = $userId;
                                                $transactions['transaction_type'] = 'From Balance';
                                                $transactions['transaction_amount'] = $remAmount;
                                                $transactions['confirmation_code'] = 'N/A';
                                                $transactions['description'] = 'Entry Fee';
                                                $transactions['status'] = '1';
                                                $transactions['request_type'] = '6';
                                                $transactions['transaction_date'] = date('Y-m-d');

                                                $objUserTransactionsModel->insertUseTransactions($transactions);


                                                $transactions['user_id'] = $userId;
                                                $transactions['transaction_type'] = 'From Bonus';
                                                $transactions['transaction_amount'] = $userBonusAmount;
                                                $transactions['confirmation_code'] = 'N/A';
                                                $transactions['description'] = 'Entry Fee(Bonus)';
                                                $transactions['status'] = '1';
                                                $transactions['request_type'] = '6';
                                                $transactions['transaction_date'] = date('Y-m-d');

                                                $objUserTransactionsModel->insertUseTransactions($transactions);

                                                $this->view->session->storage->userBalance = $this->view->session->storage->userBalance - $remAmount;
                                                $this->view->session->storage->userBonus = 0;

                                                $lineupUpdateData = array('bonus' => $userBonusAmount);
                                                $objLineup->updateLineup($lineupUpdateData, $lineupId);
                                                $objUserAccount->updateUserBonusAmount($userId, $userBonusAmount);
                                            }
                                        } else {

                                            $objUserAccount->updateUserBalanceWithdrawn($userId, $contestDetails['entry_fee']);
//                                    $objUserAccount->updateUserFppAdded($userId,$contestDetails['fpp']);

                                            $this->view->session->storage->userBalance = $this->view->session->storage->userBalance - $contestDetails['entry_fee'];

                                            $transactions['user_id'] = $userId;
                                            $transactions['transaction_type'] = 'From Balance';
                                            $transactions['transaction_amount'] = $contestDetails['entry_fee'];
                                            $transactions['confirmation_code'] = 'N/A';
                                            $transactions['description'] = 'Entry Fee';
                                            $transactions['status'] = '1';
                                            $transactions['request_type'] = '6';
                                            $transactions['transaction_date'] = date('Y-m-d');

                                            $objUserTransactionsModel->insertUseTransactions($transactions);
                                        }
                                    }
                                }
                                $objContest->updateTotalEntry($conid);
                                echo $lineupId;
                            }
                        } else {
                            echo 0;
                        }
                    } else {

                        $lineupId = $objLineup->inserLineup($userLineup);

                        if ($lineupId) {
                            if (isset($offered) && $offered != 1) { //for offered users
                                if ($contestDetails['ticket_id']) {
                                    $accData = $objUserAccount->getAccountByUserId($userId);
                                    $userTickets = json_decode($accData['available_tickets'], true);
                                    $key = array_search($contestDetails['ticket_id'], $userTickets);
                                    unset($userTickets[$key]);
                                    $updateAccData['available_tickets'] = json_encode($userTickets);
                                    $objUserAccount->updateUserAccount($userId, $updateAccData);
//                                $ticketData = $objTicketModel->getTicketDetailsById($contestDetails['ticket_id']);    
//                                $tdata      = json_decode($ticketData['ticket_for'],true);                                
//                                $key        = array_search($userId,$tdata);
//                                
//                                unset($tdata[$key]);
//                                
//                                $filterdata = json_encode($tdata);
//                                
//                                $objTicketModel->updateTicketUsers($filterdata,$contestDetails['ticket_id']);   
                                } else if ($contestDetails && $contestDetails['entry_fee'] > 0) {

                                    if ($userBonusAmount != 0) {

                                        if ($userBonusAmount >= $contestDetails['entry_fee']) {

                                            $objUserAccount->updateUserFppAdded($userId, $contestDetails['fpp']);

                                            $transactions['user_id'] = $userId;
                                            $transactions['transaction_type'] = 'From Bonus';
                                            $transactions['transaction_amount'] = $contestDetails['entry_fee'];
                                            $transactions['confirmation_code'] = 'N/A';
                                            $transactions['description'] = 'Entry Fee(Bonus)';
                                            $transactions['status'] = '1';
                                            $transactions['request_type'] = '6';
                                            $transactions['transaction_date'] = date('Y-m-d');

                                            $objUserTransactionsModel->insertUseTransactions($transactions);
                                            //$lineupUpdateData = 
                                            $lineupUpdateData = array('bonus' => $contestDetails['entry_fee']);

                                            $objLineup->updateLineup($lineupUpdateData, $lineupId);

                                            $objUserAccount->updateUserBonusAmount($userId, $contestDetails['entry_fee']);

                                            $this->view->session->storage->userBonus = $userBonusAmount - $contestDetails['entry_fee'];
                                        } else {

                                            $remAmount = $contestDetails['entry_fee'] - $userBonusAmount;

                                            $objUserAccount->updateUserBalanceWithdrawn($userId, $remAmount);
                                            $objUserAccount->updateUserFppAdded($userId, $contestDetails['fpp']);

                                            $transactions['user_id'] = $userId;
                                            $transactions['transaction_type'] = 'From Balance';
                                            $transactions['transaction_amount'] = $remAmount;
                                            $transactions['confirmation_code'] = 'N/A';
                                            $transactions['description'] = 'Entry Fee';
                                            $transactions['status'] = '1';
                                            $transactions['request_type'] = '6';
                                            $transactions['transaction_date'] = date('Y-m-d');

                                            $objUserTransactionsModel->insertUseTransactions($transactions);

                                            $transactions['user_id'] = $userId;
                                            $transactions['transaction_type'] = 'From Bonus';
                                            $transactions['transaction_amount'] = $userBonusAmount;
                                            $transactions['confirmation_code'] = 'N/A';
                                            $transactions['description'] = 'Entry Fee(Bonus)';
                                            $transactions['status'] = '1';
                                            $transactions['request_type'] = '6';
                                            $transactions['transaction_date'] = date('Y-m-d');

                                            $objUserTransactionsModel->insertUseTransactions($transactions);

                                            $this->view->session->storage->userBalance = $this->view->session->storage->userBalance - $remAmount;
                                            $this->view->session->storage->userBonus = 0;

                                            $lineupUpdateData = array('bonus' => $userBonusAmount);

                                            $objLineup->updateLineup($lineupUpdateData, $lineupId);
                                            $objUserAccount->updateUserBonusAmount($userId, $userBonusAmount);
                                        }
                                    } else {

                                        $objUserAccount->updateUserBalanceWithdrawn($userId, $contestDetails['entry_fee']);
                                        $objUserAccount->updateUserFppAdded($userId, $contestDetails['fpp']);

                                        $this->view->session->storage->userBalance = $this->view->session->storage->userBalance - $contestDetails['entry_fee'];

                                        $transactions['user_id'] = $userId;
                                        $transactions['transaction_type'] = 'From Balance';
                                        $transactions['transaction_amount'] = $contestDetails['entry_fee'];
                                        $transactions['confirmation_code'] = 'N/A';
                                        $transactions['description'] = 'Entry Fee';
                                        $transactions['status'] = '1';
                                        $transactions['request_type'] = '6';
                                        $transactions['transaction_date'] = date('Y-m-d');

                                        $objUserTransactionsModel->insertUseTransactions($transactions);
                                    }
                                }
                            }
                            $objContest->updateTotalEntry($conid);

                            echo $lineupId;
                        }
                    }

                    if (isset($lineupId)) {

                        $user_lineup = array('lineup_id' => $lineupId,
                            'contest_id' => $conid,
                            'status' => '1',
                            'created_date' => $current_date);

                        $objUserLineupModel->inserUserLineup($user_lineup);
                    }

                    break;
                /**
                 * Developer     : Vivek Chaudhari   
                 * Date          : 13/07/2014
                 * Description   : save lineup and update lineup and samelineupcontests functions 
                 * ------------------------------------------------------------------------------------------------- 
                 */
                case 'saveuserlineup':

                    $objLineup = Application_Model_Lineup::getInstance();
                    $objUserLineup = Application_Model_UserLineup::getInstance();
                    $objUserAccount = Application_Model_UserAccount::getInstance();

                    $lineup = $this->getRequest()->getParam('lineup');
                    $sports_id = $this->getRequest()->getParam('sports_id');
                    //print_r($sports_id);die;
                    $time = $this->getRequest()->getParam('time');
                    $remSalary = $this->getRequest()->getParam('rem_salary');
                    $pos_details = $this->getRequest()->getParam('pos_details');
                    $plrPosDet = array();
                    array_walk_recursive($pos_details, function($key, $val) use (&$plrPosDet) {
                                $plrPosDet[$val] = $key;
                            });

                    $userId = $this->view->session->storage->user_id;
                    $start_time = Date('Y-m-d H:i:s', strtotime($time));
                    $lineupDetails = array('sports_id' => $sports_id,
                        'player_ids' => json_encode($lineup),
                        'pos_details' => json_encode($plrPosDet),
                        'start_time' => $start_time,
                        'created_by' => $userId,
                        'rem_salary' => $remSalary);

                    $lineupId = $objLineup->insertLineup($lineupDetails);

//                    $current_date = date('Y-m-d H:i:s');
//                    $lineupDetails = array('lineup_id' => $lineupId,
//                        'contest_id' => '0',
//                        'created_date' => $current_date);
//
//                    $objUserLineup->inserUserLineup($lineupDetails);
                    if ($lineupId) {
                        echo $lineupId;
                    }
                    break;

                case 'updateuserlineup':

                    $objLineup = Application_Model_Lineup::getInstance();
                    $objUserLineupModel = Application_Model_UserLineup::getInstance();
                    $objUserAccount = Application_Model_UserAccount::getInstance();

                    $lineup = $this->getRequest()->getParam('lineup');
                    $lineup_id = $this->getRequest()->getParam('lineup_id');
                    $sports_id = $this->getRequest()->getParam('sports_id');
                    $time = $this->getRequest()->getParam('time');
                    $remSalary = $this->getRequest()->getParam('rem_salary');
                    $pos_details = $this->getRequest()->getParam('pos_details');
                    $plrPosDet = array();
                    array_walk_recursive($pos_details, function($key, $val) use (&$plrPosDet) {
                                $plrPosDet[$val] = $key;
                            });
                    // echo "<pre>"; print_r($remSalary); echo "</pre>";die;
                    $userId = $this->view->session->storage->user_id;
//echo "<pre>"; print_r($lineup_id); echo "</pre>";
                    $userLineup = array('player_ids' => json_encode($lineup), 'pos_details' => json_encode($plrPosDet), 'rem_salary' => $remSalary);
//                echo "<pre>"; print_r($userLineup); echo "</pre>";die;
                    $updateres = $objLineup->updateLineupDetails($userLineup, $lineup_id);

                    if (isset($updateres)) {
                        $this->view->upsalary = $updateres;
                    }
                    $objUserLineupModel->updateLineupEdit($lineup_id);
                    echo $lineup_id;

                    break;
                    
                case 'samelineupcontests':
                    $userId = $this->view->session->storage->user_id;
                    $cid = $this->getRequest()->getParam('cid');
                    $time = $this->getRequest()->getParam('time');
                    $cost = $this->getRequest()->getParam('cost');
                    $lid = $this->getRequest()->getParam('lid');

                    $objLineupModel = Application_Model_Lineup::getInstance();
                    $objUserAccount = Application_Model_UserAccount::getInstance();
                    $objUserLineupModel = Application_Model_UserLineup::getInstance();
                    $objContest = Application_Model_Contests::getInstance();
                    $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
                    $objContestTransactiuonModel = Application_Model_ContestTransactions::getInstance();
                    
                    if(isset($lid) && !empty($lid)){
                    $lineup = $objLineupModel->getPlayerIdsByLineupId($lid);
                    $inLineup = json_decode($lineup['player_ids']);
                    $sports_id = $lineup['sports_id'];
                    foreach ($cid as $key => $value) {
                        $contestDetails = $objContest->getContestFeeById($value);
                        $current_date = date('Y-m-d H:i:s');
                        $user_lineup = array('lineup_id' => $lid,
                            'contest_id' => $value,
                            'status' => '1',
                            'created_date' => $current_date);
                        $check = false;
                        if ($contestDetails['play_limit'] != 0) {
                            if ($contestDetails['total_entry'] < $contestDetails['play_limit']) {
                                $ok = $objUserLineupModel->inserUserLineup($user_lineup);
                                if ($ok) {
                                    $check = true;
                                }
                            }
                        } else {
                            $ok = $objUserLineupModel->inserUserLineup($user_lineup);
                            if($ok){
                                $check = true;
                            }
                        }
                        if($check){
                            $objContest->updateTotalEntry($value);
                            $transactions['user_id'] = $userId;
                            $transactions['transaction_type'] = 'From Balance';
                            $transactions['transaction_amount'] = $contestDetails['entry_fee'];
                            $transactions['confirmation_code'] = 'N/A';
                            $transactions['description'] = 'Entry Fee';
                            $transactions['status'] = '1';
                            $transactions['request_type'] = '6';
                            $transactions['transaction_date'] = date('Y-m-d');

                            $transactionId = $objUserTransactionsModel->insertUseTransactions($transactions);

                            if(isset($transactionId)){
                                //changes for contest details in transaction history (vivek 3rdOct15)
                                $contestTxData['transaction_id'] = $transactionId;
                                $contestTxData['contest_id'] = $value;
                                $contestTxData['user_id'] = $userId;

                               
                                $objContestTransactiuonModel ->insertConTransaction($contestTxData);
                                //end
                            }
                        }
                    }

                    $cids = implode(",", $cid);
                    $conname = $objContest->getcontestnamebyuid($cids);
                    $objUserAccount->updateUserBalanceWithdrawn($userId, $cost);
                    $this->view->session->storage->userBalance = $this->view->session->storage->userBalance - $cost;
                    }
                    if (isset($ok) && ($ok != 0)) {
                        echo json_encode($conname);
                    } else {
                        echo 0;
                    }

                    break;    
                case 'samelineupcontests_old':
                    $userId = $this->view->session->storage->user_id;
                    $cid = $this->getRequest()->getParam('cid');
                    $time = $this->getRequest()->getParam('time');
                    $cost = $this->getRequest()->getParam('cost');
                    $lid = $this->getRequest()->getParam('lid');

                    $objLineupModel = Application_Model_Lineup::getInstance();
                    $objUserAccount = Application_Model_UserAccount::getInstance();
                    $objUserLineupModel = Application_Model_UserLineup::getInstance();
                    $objContest = Application_Model_Contests::getInstance();
                    $lineup = $objLineupModel->getPlayerIdsByLineupId($lid);
                    $inLineup = json_decode($lineup['player_ids']);
                    $sports_id = $lineup['sports_id'];
                    foreach ($cid as $key => $value) {
                        $contestDetails = $objContest->getContestFeeById($value);
                        $current_date = date('Y-m-d H:i:s');

                        $user_lineup = array('lineup_id' => $lid,
                            'contest_id' => $value,
                            'status' => '1',
                            'created_date' => $current_date);

                        if ($contestDetails['play_limit'] != 0) {

                            if ($contestDetails['total_entry'] < $contestDetails['play_limit']) {
                                $ok = $objUserLineupModel->inserUserLineup($user_lineup);
                                if ($ok) {
                                    $objContest->updateTotalEntry($value);
                                }
                            }
                        } else {
                            $ok = $objUserLineupModel->inserUserLineup($user_lineup);
                            $objContest->updateTotalEntry($value);
                        }
                    }

                    $cids = implode(",", $cid);
                    $conname = $objContest->getcontestnamebyuid($cids);
                    $objUserAccount->updateUserBalanceWithdrawn($userId, $cost);
                    if ($ok != 0) {
                        echo json_encode($conname);
                    } else {
                        echo 0;
                    }

                    break;
//==============================================================================================================

                       case 'exportlineup':
                        $lineupId = $this->getRequest()->getPost('lid');
                       
                        $objLineupModel = Application_Model_Lineup::getInstance();
                        $objGamePlayerModel = Application_Model_GamePlayers::getInstance();
                        $objPlayerStatsModel = Application_Model_PlayerStats::getInstance();
                        $response = new stdClass();
                        if(isset($lineupId)){
                            $lineupData = $objLineupModel->getPlayerIdsByLineupId($lineupId);
                            
                            $playerIds = json_decode($lineupData['player_ids'], true);
                            $position = json_decode($lineupData['pos_details'], true);
                            $playerDetails = $objGamePlayerModel->getPlayerList($playerIds);

                            $playerDetail = array();
                            foreach ($playerDetails as $pvalue) {
                               $payerDetailsArray = json_decode($pvalue['plr_details'], true);
                               $teamname = $payerDetailsArray['team_name'];
                               $team = $objPlayerStatsModel->getTeamDetailsTeamName($teamname);
                               $pkey = array_search($payerDetailsArray['id'], $playerIds);
                               if (!empty($team)) {
                                   $playerDetail[$pkey]['team_id'] = $team['team_id'];
                               }
                              
                               $playerDetail[$pkey]['id'] = $payerDetailsArray['id'];
                               $playerDetail[$pkey]['name'] = $payerDetailsArray['name'];
                               $playerDetail[$pkey]['position'] = $position[$payerDetailsArray['id']]; //$payerDetailsArray['pos_code'];
                               $playerDetail[$pkey]['team'] = $teamname;
                               $playerDetail[$pkey]['fppg'] = $pvalue['fppg'];
                               $playerDetail[$pkey]['salary'] = $pvalue['plr_value'];
                           }
                        $objParser = Engine_Utilities_GameXmlParser::getInstance();
                        if ($lineupData['sports_id'] == '1') {
                            $playerDetail = $objParser->arrangeNFLineUp($playerDetail);
                        } else if ($lineupData['sports_id'] == '2') {
                            $playerDetail = $objParser->arrangeMLBLineUp($playerDetail);
                        } else if ($lineupData['sports_id'] == '3') {
                            $playerDetail = $objParser->arrangeNBALineUp($playerDetail);
                        } else if ($lineupData['sports_id'] == '4') {
                            $playerDetail = $objParser->arrangeNHLineUp($playerDetail);
                        } else {
                            ksort($playerDetail);
                        }
                        $csvPath = 'assets/csv/exportlineup.csv';
                        $fp = fopen($csvPath, 'w');
                        $header = array('position', 'name', 'team', 'fpp', 'salary');
                        fputcsv($fp, $header);

                        foreach ($playerDetail as $fkey => $fieldsVal) {
                            if (isset($fieldsVal['position'])) {
                                $fieldData['position'] = $fieldsVal['position'];
                            }
                            if (isset($fieldsVal['name'])) {
                                $fieldData['name'] = $fieldsVal['name'];
                            }
                            if (isset($fieldsVal['team'])) {
                                $fieldData['team'] = $fieldsVal['team'];
                            }
                            if (isset($fieldsVal['fppg'])) {
                                $fieldData['fppg'] = $fieldsVal['fppg'];
                            }
                            if (isset($fieldsVal['salary'])) {
                                $fieldData['salary'] = $fieldsVal['salary'];
                            }
                            fputcsv($fp, $fieldData);
                        }
                        fclose($fp);
                        
                        $response->code = 200;
                    }else{
                        $response->code = 198;
                    }
					
                    echo json_encode($response,true);
                        break;
                default:
                    break;
            }
        }
    }

    /**
     * Developer    : Vivek chaudhari
     * Date         : 18/09/2014
     * Description  : import lineup according to contest sport type
     */
    public function importLineupAction() {

        $this->_helper->layout()->disableLayout();
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objGamePlayerModel = Application_Model_GamePlayers::getInstance();
        $objContest = Application_Model_Contests::getInstance();
        $objGamePlayers = Application_Model_GamePlayers::getInstance();
        $objAbbreviation = Engine_Utilities_Abbreviations::getInstance();
        $objGameStats = Application_Model_GameStats::getInstance();
        $userId = $this->view->session->storage->user_id;

        $conId = $this->getRequest()->getParam('conid');
        $lineupId = $this->getRequest()->getParam('lineupid');
        if ($conId == '' && $lineupId != '') {
            $response = $objLineupModel->getLineupDetailsbyLid($lineupId);
        } else {
            $response = $objContest->getContestsById($conId);
        }
        if (!empty($response)) {
            $startTime = $response['start_time'];
            $sportId = $response['sports_id'];
            $lineupData = $objLineupModel->getlineupdatailsbystartid($startTime, $userId, $sportId);

            $data = array();
            if (!empty($lineupData)) {
                foreach ($lineupData as $lkey => $lvalue) {
                    $data[$lkey]['lineup_id'] = $lvalue['lineup_id'];
                    $data[$lkey]['sports_id'] = $lvalue['sports_id'];
                    $data[$lkey]['start_time'] = $lvalue['start_time'];
                    $data[$lkey]['player_ids'] = $lvalue['player_ids'];
                    $data[$lkey]['rem_salary'] = $lvalue['rem_salary'];
                    $game_date = date('Y-m-d', strtotime($lvalue['start_time']));
                    switch ($lvalue['sports_id']) {
                        case 1:

                            $response = $objGameStats->getGameStats($lvalue['sports_id'], $game_date);
                            $abbreviation = $objAbbreviation->getNFLAbbreviations(); // get team Abbreviations
                            $sports = 'NFL';
                            $searchValue = "QB";
                            $searchKey = "pos_code";
                            break;
                        case 2:

                            $response = $objGameStats->getGameStats($lvalue['sports_id'], $game_date);

                            $abbreviation = $objAbbreviation->getMLBAbbreviations(); // get team Abbreviations

                            $sports = 'MLB';
                            $searchValue = "P";
                            $searchKey = "pos_code";
                            break;
                        case 3:

                            $response = $objGameStats->getGameStats($lvalue['sports_id'], $game_date);
                            $abbreviation = $objAbbreviation->getNBAAbbreviations(); // get team Abbreviations
                            $sports = 'NBA';
                            $searchValue = "PG";
                            $searchKey = "pos_code";
                            break;
                        case 4:

                            $response = $objGameStats->getGameStats($lvalue['sports_id'], $game_date);
                            $abbreviation = $objAbbreviation->getNHLAbbreviations(); // get team Abbreviations
                            $sports = 'NHL';
                            $searchValue = "C";
                            $searchKey = "pos_code";
                            break;

                        default:
                            break;
                    }
                    if (isset($response)) {
                        $contest_res = json_decode($response['game_stat'], true);

                        if (isset($abbreviation)) {

                            $abbreviation = (array) json_decode($abbreviation);
                            $teamCode = array();
                            $team = array();

                            $i = 0;

                            //create array to get team code for hometeam and away team
                            foreach ($contest_res['match'] as $matchDetails) {
                                $hometeamName = array_search($matchDetails['hometeam']['name'], $abbreviation);
                                $awayteamName = array_search($matchDetails['awayteam']['name'], $abbreviation);
                                $teamCode[$i]['hometeam']['name'] = $hometeamName;
                                $teamCode[$i]['hometeam']['id'] = $matchDetails['hometeam']['id'];
                                $teamCode[$i]['awayteam']['name'] = $awayteamName;
                                $teamCode[$i]['awayteam']['id'] = $matchDetails['awayteam']['id'];
                                $team[$hometeamName] = $awayteamName;
                                $team[$awayteamName] = $hometeamName;
                                $i++;
                            }


                            if (!empty($teamCode)) {

                                $teamIds = array();

                                foreach ($teamCode as $key => $value) {
                                    $teamIds[$value['hometeam']['name']] = $value['hometeam']['id'];
                                    $teamIds[$value['awayteam']['name']] = $value['awayteam']['id'];
                                }

                                $hometeam = array_map(function($item) {
                                            return strtolower($item['hometeam']['name']);
                                        }, $teamCode);

                                $awayteam = array_map(function($item) {
                                            return strtolower($item['awayteam']['name']);
                                        }, $teamCode);


                                // merge hometeam and away team to get players
                                $mergeTeamName = array_merge($hometeam, $awayteam);

                                $teamString = implode("','", $mergeTeamName);

                                $playerLists = $objGamePlayers->getPlayersByGameTeam($lvalue['sports_id'], $teamString);
//echo "<pre>"; print_r($playerLists); echo "</pre>";die;
                                $playerListArray = array();

                                $userLineupDetails = json_decode($lvalue['player_ids']);
                                $playerListArray = array();
                                $mylineupPlayerDetails = array();
                                foreach ($playerLists as $key => $value) {

                                    $dencode = json_decode($value['plr_details'], true);
                                    $position = json_decode($lvalue['pos_details'], true);
                                    $check = array_search($dencode['id'], $userLineupDetails);

                                    if ($check !== false) {
                                        $dencode['id'] = intval($dencode['id']);                            //  var_dump($dencode['id'])."</br>";
                                        $mylineupPlayerDetails[$dencode['id']] = $dencode;
                                        $mylineupPlayerDetails[$dencode['id']]['position'] = $position[$dencode['id']];
//                              $mylineupPlayerDetails[$dencode['id']]['pos_code'] = $position[$dencode['id']];
                                        $mylineupPlayerDetails[$dencode['id']]['team_vs'] = $team[$dencode['team_code']];
                                        $mylineupPlayerDetails[$dencode['id']]['team_id'] = $teamIds[$dencode['team_code']];
                                        $mylineupPlayerDetails[$dencode['id']]['plr_value'] = $value['plr_value'];
                                    }
                                }
                                foreach ($userLineupDetails as $upkey => $upVal) {

                                    $find = array_key_exists($upVal, $mylineupPlayerDetails);
                                    if ($find == false) {
                                        $eachplayer = $objGamePlayers->getByIdAndSport($upVal, $lvalue['sports_id']);
                                        $eachplayerDecode = json_decode($eachplayer['plr_details'], true);
                                        $teamName = $eachplayerDecode['team_name'];
                                        $teamData = $objGamePlayers->getTeamIdByTeam($teamName);
                                        $mylineupPlayerDetails[$upVal] = $eachplayer;
                                        $mylineupPlayerDetails[$upVal]['team_name'] = $teamName;
                                        $mylineupPlayerDetails[$upVal]['pos_code'] = $position[$upVal];
                                        $mylineupPlayerDetails[$upVal]['team_code'] = $eachplayer['plr_team_code'];
                                        $mylineupPlayerDetails[$upVal]['team_vs'] = $mergeTeamName[1];
                                        $mylineupPlayerDetails[$upVal]['team_id'] = $teamData['team_id'];
                                        $mylineupPlayerDetails[$upVal]['position'] = $position[$upVal];
                                        $mylineupPlayerDetails[$upVal]['plr_value'] = $eachplayer['plr_value'];
                                    }
                                }
                                if (isset($lvalue['sports_id'])) {
                                    $objParser = Engine_Utilities_GameXmlParser::getInstance();
                                    switch ($lvalue['sports_id']) {
                                        case 1:
                                            $mylineupPlayerDetails = $objParser->arrangeNFLineUp($mylineupPlayerDetails);
                                            break;
                                        case 2:
                                            $mylineupPlayerDetails = $objParser->arrangeMLBLineUp($mylineupPlayerDetails);
                                            break;
                                        case 3:
                                            $mylineupPlayerDetails = $objParser->arrangeNBALineUp($mylineupPlayerDetails);
                                            break;
                                        case 4:
                                            $mylineupPlayerDetails = $objParser->arrangeNHLineUp($mylineupPlayerDetails);
                                            break;
                                        default:
                                            break;
                                    }
                                }
                                $data[$lkey]['player_details'] = $mylineupPlayerDetails;
                            }
                        }
                    }
                }
            }
        }

        if ($data) {
            $this->view->data = $data;
        }
    }

    /**
     * Developer    : Vinay
     * Date         : 26/09/2014
     * Description  : Arrange contest times by sports id  
     * @param       : array
     * @return      : array
     */
    function arrangeContestSportsTimeBySports($contestData) {
        $mlbSports = array();
        $nflSports = array();
        $nbaSports = array();
        $nhlSports = array();
        $finalContestArray = array();
        $MLBSportsTime = array();
        $NFLSportsTime = array();
        $NBASportsTime = array();
        $NHLSportsTime = array();
        foreach ($contestData as $contest) {

            switch ($contest['sports_id']) {
                case '1': {
                        $nflSports['sports_id'] = $contest['sports_id'];
                        $nflSports['display_name'] = $contest['display_name'];
                        $NFLSportsTime = array_merge($contest['time'], $NFLSportsTime);
                        krsort($NFLSportsTime);
                        $nflSports['time'] = $NFLSportsTime;
                        break;
                    }
                case '2': {
                        $mlbSports['sports_id'] = $contest['sports_id'];
                        $mlbSports['display_name'] = $contest['display_name'];
                        $MLBSportsTime = array_merge($contest['time'], $MLBSportsTime);
                        krsort($MLBSportsTime);
                        $mlbSports['time'] = $MLBSportsTime;

                        break;
                    }
                case '3': {
                        $nbaSports['sports_id'] = $contest['sports_id'];
                        $nbaSports['display_name'] = $contest['display_name'];
                        $NBASportsTime = array_merge($contest['time'], $NBASportsTime);
                        krsort($NBASportsTime);
                        $nbaSports['time'] = $NBASportsTime;
                        break;
                    }
                case '4': {
                        $nhlSports['sports_id'] = $contest['sports_id'];
                        $nhlSports['display_name'] = $contest['display_name'];
                        $NHLSportsTime = array_merge($contest['time'], $NHLSportsTime);
                        krsort($NHLSportsTime);
                        $nhlSports['time'] = $NHLSportsTime;

                        break;
                    }
            }
        }

        if (!empty($nflSports)) {
            $finalContestArray[] = $nflSports;
        }
        if (!empty($mlbSports)) {
            $finalContestArray[] = $mlbSports;
        }
        if (!empty($nbaSports)) {
            $finalContestArray[] = $nbaSports;
        }
        if (!empty($nhlSports)) {
            $finalContestArray[] = $nhlSports;
        }

        return $finalContestArray;
    }

}
