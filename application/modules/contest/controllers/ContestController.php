<?php

/**
 * AdminController
 *
 * @author
 * @version
 */
 
require_once 'Zend/Controller/Action.php';

require_once '../vendor/autoload.php';

class Contest_ContestController extends Zend_Controller_Action {

    protected $session;

    public function preDispatch() {
        $this->session = new Zend_Session_Namespace('Default');
    }

    public function init() {
        
    }

    public function directChallengeAction() {
        
    } 

    public function headToHeadAction() {
        
    }

    public function leagueAction() {
        
    }
    
        //vivek chaudhari
    public function inviteAction() {
        $contestID = $this->getRequest()->getParam('cid');
        if(isset($contestID) && !empty($contestID)){            
        $objContestModel = Application_Model_Contests::getInstance();
        $mailer = Engine_Mailer_Mailer::getInstance();
        $contestData = $objContestModel->getContestsForInviteId($contestID);
        $objEmaillog = Application_Model_Emaillog::getInstance();
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
        if ($this->getRequest()->isPost()){
            $contestname = $contestData['contest_name'];
            $dateTime = date('F j, Y, g:i a' ,strtotime($contestData['start_time']));
            $referLink = $this->_appSetting->hostLink . "/affiliate/" . $this->view->session->storage->user_name;
            $loginLink = $this->_appSetting->hostLink."/signup";                        
            $useremail = $this->getRequest()->getPost('emails');
            $message = $this->getRequest()->getPost('message');
            $username = $this->view->session->storage->user_name;
            $subject = "Friends Invite from ".$this->_appSetting->title;
            $template_name = 'invite-friend-new';
            /* $mergers = array(
                array(
                    'name' => 'username',
                    'content' => $username
                ),
                array(
                    'name' => 'message',
                    'content' => $message
                ),
                array(
                    'name' => 'loginlink',
                    'content' => $loginLink
                ),
                array(
                    'name'=>'referlink',
                    'content'=>$referLink
                ),
                array(
                    'name'=>'contest',
                    'content'=>$contestname
                ),
                array(
                    'name'=>'datetime',
                    'content'=>$dateTime
                )
            ); */
			
            $emailArr = explode(",", $useremail);
            if(!empty($emailArr)){
				$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
				$postmark_config = $config->getOption('postmark');
				
                $userId  = $this->view->session->storage->user_id;
                $objUserReferals = Application_Model_ReferFriends::getInstance();
                $client = new Postmark\PostmarkClient($postmark_config['key']);
				foreach($emailArr as $email){
					
					try{
						$result = $client->sendEmailWithTemplate(
							$postmark_config['email'],
							$email,
							$postmark_config['refer_a_friend'], 
							[
								"username" => $username,
								"message" => $message,
								"login_url" => $loginLink,
								"refer_link" => $referLink,
								"subject" => $subject,
							] 
						);
						if($result){ // add referal entry
							$referData['email'] = $email;
							$referData['ref_by'] = $userId;
							$referData['ref_date'] = date('Y-m-d');
							$referData['req_count'] = 1;
							$objUserReferals->insertNewReferal($referData);
						}
					} catch (Exception $e){
					   print_r($e);
					}  					
                }	
            }
            $this->_redirect('/draftteam/'.$contestID);
        }

        if ($contestData) {
            $this->view->contestDetails = $contestData;
        }
        }
    }

        //vivek chaudhari
    public function invite() {
        $contestID = $this->getRequest()->getParam('cid');
        $objContestModel = Application_Model_Contests::getInstance();
        $mailer = Engine_Mailer_Mailer::getInstance();
        $contestData = $objContestModel->getContestsForInviteId($contestID);
        $objEmaillog = Application_Model_Emaillog::getInstance();
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
        if ($this->getRequest()->isPost()):
            $contestname = $contestData['contest_name'];
            $dateTime = date('F j, Y, g:i a' ,strtotime($contestData['start_time']));
            $referLink = $this->_appSetting->hostLink . "/affiliate/" . $this->view->session->storage->user_name;
            $loginLink = $this->_appSetting->hostLink."/signup";                        
            $useremail = $this->getRequest()->getPost('emails');
            $message = $this->getRequest()->getPost('message');
            $username = $this->view->session->storage->user_name;
            $subject = "Friends Invite from ".$this->_appSetting->title;
            $template_name = 'invite-friend';
            $mergers = array(
                array(
                    'name' => 'username',
                    'content' => $username
                ),
                array(
                    'name' => 'message',
                    'content' => $message
                ),
                array(
                    'name' => 'loginlink',
                    'content' => $loginLink
                ),
                array(
                    'name'=>'referlink',
                    'content'=>$referLink
                ),
                array(
                    'name'=>'contest',
                    'content'=>$contestname
                ),
                array(
                    'name'=>'datetime',
                    'content'=>$dateTime
                )
            );
            $emailArr = explode(",", $useremail);
            if(!empty($emailArr)){
                foreach($emailArr as $email){
                    $result = $mailer->sendtemplate($template_name, $email, $username, $subject, $mergers);
                }
            }
            $this->_redirect('/draftteam/'.$contestID);
        endif;

        if ($contestData) {
            $this->view->contestDetails = $contestData;
        }
    }

    public function joinContestAction() {
        
    }

    public function gameCenterAction() {
        
    }

    /**
     * Desc : Filter Array by searchkey and searchvalue
     * @param <String> $searchValue
     * @param <Array> $array
     * @param <String> $searchKey
     * @return <Array> $filtered
     */
    public function filterArray($searchValue, $playerListArray, $searchKey, $team = null) {

        if ($searchValue != "" && !empty($playerListArray) && $searchKey != "" && $team == null) {
            $objParser = Engine_Utilities_GameXmlParser::getInstance();
            if ($searchValue != 'All Games') {
                $filterPlayerList = $objParser->filterArray($searchValue, $playerListArray, $searchKey);
                return $filterPlayerList;
            } else {
                return $playerListArray;
            }
        }

        if ($searchValue != "" && !empty($playerListArray) && $searchKey != "" && $team != null) {

            $objParser = Engine_Utilities_GameXmlParser::getInstance();
            $filterPlayerList = array();
            foreach ($team as $code) {
                if ($code != "") {

                    $searchTeamValue = $code;
                    $searchTeamKey = 'team_code';
                    $filterPlayerList[] = $objParser->filterArray($searchTeamValue, $playerListArray, $searchTeamKey);
                }
            }

            if (!empty($filterPlayerList)) {

                if (count($filterPlayerList) > 1) {

                    $playerList = array();
                    foreach ($filterPlayerList as $data) {
                        $playerList = array_merge($playerList, $data);
                    }

                    if (!empty($playerList)) {
                        if ($searchValue == "ALL") {
                            return $playerList;
                        } else {
                            $newPlayerList = $objParser->filterArray($searchValue, $playerList, $searchKey);
                            return $newPlayerList;
                        }
                    }
                } else {
                    $playerList = array();
                    foreach ($filterPlayerList as $data) {
                        $playerList = array_merge($playerList, $data);
                    }
                    $newPlayerList = $objParser->filterArray($searchValue, $playerList, $searchKey);
                    return $newPlayerList;
                }
            }
        }
    }
    
    // this is used for array search added by alok kr saxena
    public function preg_grep_keys_values($pattern, $input, $flags = 0) {
		return array_merge(array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags))),
		preg_grep($pattern, $input, $flags));
    }

    /**
     * Desc : Action to get filter player list
     */
    public function filterPlayerByTeamAction() {

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->getRequest()->isPost()) {
            $method = $this->getRequest()->getParam('method');

            //echo "Method: ".$method;die();
            switch ($method) {
                case 'teamfilter':
                    $teamfilter = $this->getRequest()->getPost('team');
                    $searchValue = $this->getRequest()->getPost('searchValue');
                    $playerListArray = $this->getRequest()->getPost('playerList');
                    $searchKey = $this->getRequest()->getPost('searchKey');

                    // with team filter
                    $filterPlayerList = $this->filterArray($searchValue, $playerListArray, $searchKey, $teamfilter);
                   // echo "<pre>"; print_r( $filterPlayerList); echo "</pre>"; die;
                    if ($filterPlayerList) {
                        echo json_encode($filterPlayerList);
                    }

                    break;

                case 'playerfilter':
					
                    $searchValue = $this->getRequest()->getPost('searchValue');
                    $filterValue = $this->getRequest()->getPost('filterValue');
                    $playerListArray = $this->view->session->playerListArray;
					//echo print_r($this->getRequest()->getPost()); die;
				    //added code by alok on 21/07/2017 used for search player with listing and search carry in another tab
				    if(isset($filterValue) && !empty($filterValue) && $filterValue != null && $searchValue != 'ALL'){
				       $objGamePlayers = Application_Model_GamePlayers::getInstance();
				        $playerListArray = $objGamePlayers->getPlayersByGameTeamWithSearch($this->getRequest()->getPost('sportId'), $filterValue);
				    }
				    
                    $searchKey = $this->getRequest()->getPost('searchKey');
                    $teamfilter = $this->getRequest()->getPost('selectedTeam');
                    $sportId = $this->getRequest()->getPost('sportId');
                    if (($searchValue == "FLEX" && $sportId == 1 ) || ($searchValue == "F" && $sportId == 3 ) || ($searchValue == "G" && $sportId == 3 ) || $searchValue == "UTIL") {
                        if ($teamfilter[0] != "All Games") { 
					 
                            //$playerListArray = $this->specialPlayer($sportId, $searchValue, $searchKey, $teamfilter);
                            $playerListArray = $this->specialPlayer($sportId, $searchValue, $searchKey, $teamfilter,$filterValue);
							
                        } else {
						    //$playerListArray = $this->specialPlayer($sportId, $searchValue, $searchKey);
                            $playerListArray = $this->specialPlayer($sportId, $searchValue, $searchKey,$filterValue);
                        }
                        $value = array();
                        foreach ($playerListArray as $key => $row) {
                            $value[$key] = $row['plr_value'];
                        }
                        array_multisort($value, SORT_DESC, $playerListArray);
						
                        echo json_encode($playerListArray);
                    } else {

                        if ($searchValue == "ALL" && empty($teamfilter)) {
                            $value = array();
							$nfl_pos = array('QB','RB','WR','K','DST','TE');
							$mlb_pos = array('1B','2B','3B','C','SS','SP');
							$nba_pos = array('PG','SG','SF','PF','C');
							$nhl_pos = array('C','RW','LW','D','G','W');
							$newPlayerArray = array();
                            foreach ($playerListArray as $key => $row) {
								if($sportId==1){
									if(in_array($row['pos_code'],$nfl_pos)){
										$newPlayerArray[] = $row;
										$value[$key] = $row['plr_value'];
									}
								}elseif($sportId==2){
									if(in_array($row['pos_code'],$mlb_pos)){
										$newPlayerArray[] = $row;
										$value[$key] = $row['plr_value'];
									}
								}elseif($sportId==3){
									if(in_array($row['pos_code'],$nba_pos)){
										$newPlayerArray[] = $row;
										$value[$key] = $row['plr_value'];
									}
								}elseif($sportId==4){
									if(in_array($row['pos_code'],$nhl_pos)){
										$newPlayerArray[] = $row;
										$value[$key] = $row['plr_value'];
									}
								}else{
									$newPlayerArray = $playerListArray;
								}                              
                            }
							
                            array_multisort($value, SORT_DESC, $newPlayerArray);
							
                            echo json_encode($newPlayerArray);
                        } else {
                            if ($teamfilter[0] != "All Games") { 
							 
                                $filterPlayerList = $this->filterArray($searchValue, $playerListArray, $searchKey, $teamfilter);
								
                            } else {
                                $filterPlayerList = $this->filterArray($searchValue, $playerListArray, $searchKey);
                            }

                            $value = array();
                            foreach ($filterPlayerList as $key => $row) {
                                $value[$key] = $row['plr_value'];
                            }
                            array_multisort($value, SORT_DESC, $filterPlayerList);
                            if ($filterPlayerList) {
                                echo json_encode($filterPlayerList);
                            }
                        }
                    }

                    break;

                case 'playerByTeam':

                    $searchPos = $this->getRequest()->getPost('searchPos');
                    $searchTeam = $this->getRequest()->getPost('searchTeam');
                    $playerListArray = $this->view->session->playerListArray;
                    $searchKey = $this->getRequest()->getPost('searchKey');

                    $sportId = $this->getRequest()->getPost('sportId');
                    if (($searchPos == "FLEX" && $sportId == 1 ) || ($searchPos == "F" && $sportId == 3 ) || ($searchPos == "G" && $sportId == 3 ) || $searchPos == "UTIL") {
                        if ($searchTeam[0] != "All Games") {
                            $playerListArray = $this->specialPlayer($sportId, $searchPos, $searchKey, $searchTeam);
                        } else {
                            $playerListArray = $this->specialPlayer($sportId, $searchPos, $searchKey);
                        }

                        echo json_encode($playerListArray);
                    } else {

                        if ($searchPos == "ALL") {
                            echo json_encode($playerListArray);
                        } else {

                            if ($searchTeam[0] != "All Games") {
                                $filterPlayerList = $this->filterTeamArray($searchPos, $playerListArray, $searchKey, $searchTeam);
                            } else {
                                $filterPlayerList = $this->filterTeamArray($searchPos, $playerListArray, $searchKey);
                            }

                            if ($filterPlayerList) {
                                echo json_encode($filterPlayerList);
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 05/06/2014
     * Description : Create New Contest By User.
     */
    public function newContestAction() {
        $objGamestatusModel = Application_Model_GameStats::getInstance();
        $objContestModel = Application_Model_Contests::getInstance();
        $objGameModel = Application_Model_Sports::getInstance();
        $objContestTypeModel = Application_Model_ContestsType::getInstance();
        $objNotification = Application_Model_Notification::getInstance();

        $contestType = $this->getRequest()->getParam('ctype');

        $userId = $this->view->session->storage->user_id;
        $this->view->ctype = $contestType;
        $sportsDeatils = $objGameModel->getSports();
        $this->view->sportsDeatils = $sportsDeatils;
		//echo "<pre>"; print_r($sportsDeatils); die;
 
        if ($this->getRequest()->isPost()) {
            if ($contestType == '1') {
                $contestName = "Head-To-Head";
                $contestDetails = $objContestTypeModel->getContestIdByName($contestName);
                $data['con_type_id'] = $contestDetails['con_type_id'];
				
            } else {
                $contestName = "Leagues";
                $contestDetails = $objContestTypeModel->getContestIdByName($contestName);
                $data['con_type_id'] = $contestDetails['con_type_id'];
            }

            $data['sports_id'] = $this->getRequest()->getPost('sport');

            $sportsName = $objGameModel->getSportsDetailsByID($data['sports_id']);

            $date = $this->getRequest()->getPost('time');

            $matchdate = date('Y-m-d', strtotime($date));

            $response = $objGamestatusModel->getGameStats($data['sports_id'], $matchdate);
            
            if (isset($response)) {
                $contest_res = json_decode($response['game_stat'], true);
                $matchCount = count($contest_res['match']);

                if ($matchCount == 1) {
                    $response = $objGamestatusModel->getFutureGameStats($data['sports_id'], $matchdate, 5);

                    if ($response) {
                        $contest_res = array();
                        foreach ($response as $res) {
                            $game_stat = json_decode($res['game_stat'], true);
                            $contest_res[] = $game_stat;
                            $matcharraypop = array_pop($game_stat['match']);
                            $gameLastDate = date('Y-m-d H:i:s', strtotime($game_stat['formatted_date'] . ' ' . $matcharraypop['time']));
                        }
                    }
                } else {
                    $lastMatch = array_pop($contest_res['match']);
                    $gameLastDate = date('Y-m-d H:i:s', strtotime($lastMatch['formatted_date'] . ' ' . $lastMatch['time']));
                    
                }
            }
            if (isset($data['sports_id'])) {
                switch ($data['sports_id']) {
                    case 1 :
                        $gameLastDate = date('Y-m-d H:i:s', strtotime($gameLastDate .'+4hours'));
                        break;
                    case 2 :
                        $gameLastDate = date('Y-m-d H:i:s', strtotime($gameLastDate .'+4hours'));
                        break;
                    case 3 :
                        $gameLastDate = date('Y-m-d H:i:s', strtotime($gameLastDate .'+3hours'));
                        break;
                    case 4 :
                        $gameLastDate = date('Y-m-d H:i:s', strtotime($gameLastDate .'+3hours'));
                        break;
                }
            }
            $data['end_time'] = $gameLastDate;

            $data['start_time'] = date('Y-m-d H:i:s', strtotime($date));

            $data['entry_fee'] = $this->getRequest()->getPost('entryfee');
            $data['play_limit'] = $this->getRequest()->getPost('teamsize');

            if ($contestType == 2) {
                $data['prizes'] = $this->getRequest()->getPost('prize');

                $data['prize_pool'] = 0.95 * ($data['entry_fee'] * $data['play_limit']);
                $data['fpp'] = $data['entry_fee'] * 4;

               // print_r($data['prizes']);die();
                if ($data['prizes'] == '0') {
                    $prizename = 'No Prize';
                } else if ($data['prizes'] == '1') {

                    $prizename = 'Winner Takes All';
                } else if ($data['prizes'] == '2') {
                    $prizename = 'Top 2 Win';
                } else if ($data['prizes'] == '3') {
                    $prizename = 'Top 3 Win';
                } else if ($data['prizes'] == '4') {
                    $prizename = 'Top 5 Win';
                } else if ($data['prizes'] == '5') {
                    $prizename = '50/50';
                }else if ($data['prizes'] == '2"') {
                    $prizename = 'Top 2 Win';
                }
                
                /* description:calculating prizes for the contestants for displaying in the prize payout section in the contest detail modal */ 
                
                $payout = Array();
                $amount = (($data['entry_fee']) * ($data['play_limit']));
                $percent = ($amount / 100) * 5;
                $actamount = ($amount - $percent);
                $per = $actamount / 100;
                
                if ($data['prizes'] == '2') { //die('bdf');
                    $top1 = ($per * 70);
                    $top2 = ($per * 30);

                    $payout[0]['from'] = 1;
                    $payout[0]['to'] = 0;
                    $payout[0]['type'] = 0;
                    $payout[0]['amount'] = $top1;
                    $payout[0]['ticket_id'] = NULL;

                    $payout[1]['from'] = 2;
                    $payout[1]['to'] = 0;
                    $payout[1]['type'] = 0;
                    $payout[1]['amount'] = $top2;
                    $payout[1]['ticket_id'] = NULL;
                } else if ($data['prizes'] == '3') {
                    $win1 = ($per * 50);
                    $win2 = ($per * 30);
                    $win3 = ($per * 20);

                    $payout[0]['from'] = 1;
                    $payout[0]['to'] = 0;
                    $payout[0]['type'] = 0;
                    $payout[0]['amount'] = $win1;
                    $payout[0]['ticket_id'] = NULL;

                    $payout[1]['from'] = 2;
                    $payout[1]['to'] = 0;
                    $payout[1]['type'] = 0;
                    $payout[1]['amount'] = $win2;
                    $payout[1]['ticket_id'] = NULL;

                    $payout[2]['from'] = 3;
                    $payout[2]['to'] = 0;
                    $payout[2]['type'] = 0;
                    $payout[2]['amount'] = $win3;
                    $payout[2]['ticket_id'] = NULL;
                } else if ($data['prizes'] == '4') {
                    $win1 = ($per * 30);
                    $win2 = ($per * 25);
                    $win3 = ($per * 20);
                    $win4 = ($per * 15);
                    $win5 = ($per * 10);

                    $payout[0]['from'] = 1;
                    $payout[0]['to'] = 0;
                    $payout[0]['type'] = 0;
                    $payout[0]['amount'] = $win1;
                    $payout[0]['ticket_id'] = NULL;

                    $payout[1]['from'] = 2;
                    $payout[1]['to'] = 0;
                    $payout[1]['type'] = 0;
                    $payout[1]['amount'] = $win2;
                    $payout[1]['ticket_id'] = NULL;

                    $payout[2]['from'] = 3;
                    $payout[2]['to'] = 0;
                    $payout[2]['type'] = 0;
                    $payout[2]['amount'] = $win3;
                    $payout[2]['ticket_id'] = NULL;

                    $payout[3]['from'] = 4;
                    $payout[3]['to'] = 0;
                    $payout[3]['type'] = 0;
                    $payout[3]['amount'] = $win4;
                    $payout[3]['ticket_id'] = NULL;

                    $payout[4]['from'] = 5;
                    $payout[4]['to'] = 0;
                    $payout[4]['type'] = 0;
                    $payout[4]['amount'] = $win5;
                    $payout[4]['ticket_id'] = NULL;
                } else if ($data['prizes'] == '5') {
//                                $win = $per * 50;
                    $payout = Array();
                    $amount = (($data['entry_fee']) * ($data['play_limit']));
                    $percent = $amount * 0.05;
                    $actamount = ($amount - $percent);
                    $players = round($data['play_limit'] / 2, 0);
                    $payAmt = $actamount / $players;
//                            echo $players; die;
                    $payout[0]['from'] = 1;
                    $payout[0]['to'] = $players;
                    $payout[0]['type'] = 0;
                    $payout[0]['amount'] = $payAmt;
                    $payout[0]['ticket_id'] = NULL;
                }
                //echo "<pre>payout--->"; print_r( $payout);echo "</pre>";die;
                if (!empty($payout)) {
                    $payArr = json_encode($payout);
                    $data['prize_payouts'] = $payArr;
                }
                $data['prize_pool'] = $actamount;
            }
            if ($contestType == 1) {
//                $data['play_limit'] = $this->getRequest()->getPost('challengerCount');
//                Player limit should be 2 in case of Head-to-Head contest.

                $data['play_limit'] = 2;

                $data['prizes'] = 1;

                $data['prize_pool'] = 0.95 * ($data['entry_fee'] * $data['play_limit']);
                $data['fpp'] = $data['entry_fee'] * 4;
				$data['challenge_limit'] = 0;
				
				$payout[0]['from'] = 1;
                $payout[0]['to'] = 0;
                $payout[0]['type'] = 0;
                $payout[0]['amount'] = $data['prize_pool'];
                $payout[0]['ticket_id'] = NULL;
				if (!empty($payout)) {
                    $payArr = json_encode($payout);
                    $data['prize_payouts'] = $payArr;
                }	
				 $data['contest_name'] = $sportsName['display_name'] .' '. $data['entry_fee'].'  DFS';
            }

           

            $data['play_type'] = $this->getRequest()->getPost('playwith');

            if ($contestType == 2) {
                $data['play_limit'] = $this->getRequest()->getPost('teamsize');
            }

            if ($contestType == 2) {
                if ($data['play_type'] == 1) {
                    $data['contest_name'] = $sportsName['display_name'] .' '.$data['entry_fee'] . ' DFS ' . $contestName . ' BY ' . $this->view->session->storage->user_name;
                }
            }

            if ($contestType == 2) {

                $data['contest_name'] = $this->getRequest()->getPost('name');
                if (empty($data['contest_name'])) {
                    //echo $prizename; die;
                    $data['contest_name'] = $sportsName['display_name'] .' '. $data['entry_fee'] . ' DFS ' . $data['play_limit'] . '-Players (' . $prizename . ') BY ' . $this->view->session->storage->user_name;
                }
            }
			
            $data['description'] = "This is " . $sportsName['display_name'] .' '. $data['entry_fee'] . ' DFS ' . $contestName . ' BY ' . $this->view->session->storage->user_name;
            $data['created_by'] = $userId;
            $challanges = $this->getRequest()->getPost('challengerCount');
//echo "<pre>"; print_r($data);// die;
            $contestID = $objContestModel->insertContestsDetails($data);

            if ($data['play_type'] == 1) {
                $this->_redirect('/draftteam/' . $contestID);
            } else {
                $this->_redirect('/invite/' . $contestID);
            }
        }
    }

    public function draftTeamAction() {
		
        if ($this->getRequest()->getParam('conid')) {

            $objSettingModel = Application_Model_Settings::getInstance();
            $objBlockedModel = Application_Model_BlockUsers::getInstance();
            //$objUserAccModel = Application_Model_UserAccount::getInstance();
            $objTicketModel = Application_Model_TicketSystem::getInstance();

            $username = $this->view->session->storage->user_name;
            $userId = $this->view->session->storage->user_id;
            $settings = $objSettingModel->getSettings();
           //echo "<pre>"; print_r($settings); die;
            if ($settings) {
                $this->view->settings = $settings;
            }

            $conid = $this->getRequest()->getParam('conid');

            if ($this->getRequest()->getParam('act')) {
                $act = $this->getRequest()->getParam('act');
            }
            if ($this->getRequest()->getParam('act')) {
                $actTicket = $this->getRequest()->getParam('act');
            }

            $objContestsModel = Application_Model_Contests::getInstance();
            $conResult = $objContestsModel->getContestsById($conid); // get Active upcomming contest

			$conStamp = strtotime($conResult['start_time']."-5 mins");
			$currentTime = time();
			if($currentTime < $conStamp){
            
            if (isset($conResult['offers_to']) && ($conResult['offers_to'] != null)) {
                $offeredUsers = json_decode($conResult['offers_to'], true);
                $offered = in_array($userId, $offeredUsers);

                if ($offered) {
                    $this->view->offer = 1;
                }
            }

            if ((isset($conResult['created_by'])) && ($conResult['created_by'] != '')) {
                $blockdata = $objBlockedModel->getBlockedUserDetailsByName($username, $conResult['created_by']);
            }
//           echo "<pre>"; print_r($conResult); echo "</pre>"; die;
            if (($conResult)) {

                $objAbbreviation = Engine_Utilities_Abbreviations::getInstance();
                $objGameStats = Application_Model_GameStats::getInstance();

                $game_date = date('Y-m-d', strtotime($conResult['start_time']));

                if ($blockdata) {

                    $this->view->blocked = 1; // blocked User
                }

                if (isset($act) && $act == 'res') {
                    $this->view->reserver = 0;
                } else {

                    if ($this->view->ticketContest != 1) {

                        if ($game_date > date('Y-m-d')) {

                            $this->view->reserver = 1; // reserve entries    
                        } else {

                            $this->view->reserver = 0;
                        }
                    } else {
                        
                    }
                }

                $objUserAcount = Application_Model_UserAccount::getInstance();
                $userBalance = $objUserAcount->getUserBalance($this->view->session->storage->user_id);

                $this->view->userBalance = $userBalance['balance_amt'];
          
                if ($userBalance['balance_amt'] >= $conResult['entry_fee']) {
                    $this->view->balance = $userBalance['balance_amt'];
                } else {
                    $this->view->lowbalance = $userBalance['balance_amt'];
                }
                $sports = null;
                $searchValue = "";
                $searchKey = "";

                $this->view->sports_id = $conResult['sports_id'];
                $this->view->conid = $conid;
                $this->view->start_time = $conResult['start_time'];

                switch ($conResult['sports_id']) {
                    case 1:

                        $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                        $abbreviation = $objAbbreviation->getNFLAbbreviations(); // get team Abbreviations
                        $sports = 'NFL';
                        $searchValue = "QB";
                        $searchKey = "pos_code";
                        break;
                    case 2:


                        $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);

                        $abbreviation = $objAbbreviation->getMLBAbbreviations(); // get team Abbreviations

                        $sports = 'MLB';
                        $searchValue = "P";
                        $searchKey = "pos_code";
                        break;
                    case 3:

                        $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                        $abbreviation = $objAbbreviation->getNBAAbbreviations1(); // get team Abbreviations
                        $sports = 'NBA';
                        $searchValue = "PG";
                        $searchKey = "pos_code";
                        break;
                    case 4:

                        $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                        $abbreviation = $objAbbreviation->getNHLAbbreviations(); // get team Abbreviations
                        $sports = 'NHL';
                        $searchValue = "C";
                        $searchKey = "pos_code";
                        break;

                    default:
                        break;
                }
				//echo "<pre>"; print_r($conResult); echo "</pre>";
                if (isset($response)) {

                    $contest_res = json_decode($response['game_stat'], true);
                    
                    $contestDate = strtotime(date('Y-m-d', strtotime($conResult['start_time'])));
                    $contestTimeStamp = strtotime($conResult['start_time']);
                    $this->view->contesttime = $contestTimeStamp;
                    foreach ($contest_res['match'] as $crkey => $crData) {
                        $matchTimeStamp = strtotime($crData['formatted_date'] . $crData['time']);
                        if ($matchTimeStamp < $contestTimeStamp) { 
                            unset($contest_res['match'][$crkey]);
                        }
                    }
                    // Get Match count                     
                    $matchCount = count($contest_res['match']);

                    if ($matchCount == 1) {
                        $response = $objGameStats->getFutureGameStats($conResult['sports_id'], $game_date, 5);

                        if ($response) {
                            $contest_res = array();
                            foreach ($response as $res) {
                                $game_stat = json_decode($res['game_stat'], true);
                                foreach ($game_stat['match'] as $gkey => $gVal) {
                                    $matchTimeStamp = strtotime($gVal['formatted_date'] . $gVal['time']);
                                    if ($matchTimeStamp < $contestTimeStamp) {
                                        unset($game_stat['match'][$gkey]);
                                    }
                                }
                                $contest_res[] = $game_stat;
                                $matcharraypop = array_pop($game_stat['match']);
                                
                            }
                        }
                    } else {
                       
                    }


                    if (isset($abbreviation)) {

                        $abbreviation = (array) json_decode($abbreviation);

                        $this->view->abbreviation = $abbreviation;                       
                        $teamCode = array();
                        $team = array();
						$homeTeam_array = array();
                        $i = 0;

                        if ($matchCount != 1) {
                            //create array to get team code for hometeam and away team
                            foreach ($contest_res['match'] as $matchDetails) {
                                $hometeamName = array_search($matchDetails['hometeam']['name'], $abbreviation);
								//echo "<pre>"; print_r($matchDetails); echo "</pre>";
                                $awayteamName = array_search($matchDetails['awayteam']['name'], $abbreviation);
                                $teamCode[$i]['time'] = $matchDetails['formatted_date'] .' '. $matchDetails['time'];
                                $teamCode[$i]['hometeam']['name'] = $hometeamName;
                                $teamCode[$i]['hometeam']['id'] = $matchDetails['hometeam']['id'];
                                $teamCode[$i]['awayteam']['name'] = $awayteamName;
                                $teamCode[$i]['awayteam']['id'] = $matchDetails['awayteam']['id'];
                                $team[$hometeamName] = $awayteamName;
                                $team[$awayteamName] = $hometeamName;
								$homeTeam_array[$hometeamName] = $awayteamName."@".$hometeamName;
								$homeTeam_array[$awayteamName] = $awayteamName."@".$hometeamName;
                                $i++;
                            }
                        } else {

                            foreach ($contest_res as $conres) {

                                foreach ($conres['match'] as $matchDetails) {
                                    $hometeamName = array_search($matchDetails['hometeam']['name'], $abbreviation); 
									//echo "<pre>"; print_r($matchDetails); echo "</pre>";
                                    $awayteamName = array_search($matchDetails['awayteam']['name'], $abbreviation);
									
                                    $teamCode[$i]['time'] = $matchDetails['formatted_date'] .' '. $matchDetails['time'];
                                    $teamCode[$i]['hometeam']['name'] = $hometeamName;
                                    $teamCode[$i]['hometeam']['id'] = $matchDetails['hometeam']['id'];
                                    $teamCode[$i]['awayteam']['name'] = $awayteamName;
                                    $teamCode[$i]['awayteam']['id'] = $matchDetails['awayteam']['id'];
                                    $team[$hometeamName] = $awayteamName;
                                    $team[$awayteamName] = $hometeamName;
									$homeTeam_array[$hometeamName] = $awayteamName."@".$hometeamName;
									$homeTeam_array[$awayteamName] = $awayteamName."@".$hometeamName;
                                    $i++;
                                }
                            }
                        }
                        $this->view->homeTeam_array = $homeTeam_array;
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


                            // merge hometeam and away team to get players  "TEST1
                            $mergeTeamName = array_merge($hometeam, $awayteam);
                            $objGamePlayers = Application_Model_GamePlayers::getInstance();

                            $teamString = implode("','", $mergeTeamName);

                            $playerLists = $objGamePlayers->getPlayersByGameTeam($conResult['sports_id'], $teamString);
							
                            $playerListArray = array();
                            if (!empty($playerLists)) {
                                foreach ($playerLists as $plrkey => $plrvalue) {
                                    $dencode = json_decode($plrvalue['plr_details'], true);
                                    if (isset($dencode)) {
                                        $playerListArray[$plrkey] = $dencode;
										$home_arr = explode('@',$homeTeam_array[$dencode['team_code']]);
									
										if($home_arr[0]==$dencode['team_code']){
											$match_code =  "<span class='font3'>".$home_arr[0]."</span> @ ".$home_arr[1];
										}else{
											$match_code = $home_arr[0]." @ <span class='font3'>".$home_arr[1]."</span>";
										}
                                        $playerListArray[$plrkey]['match_code'] = $match_code;
                                        $playerListArray[$plrkey]['team_vs'] = $team[$dencode['team_code']];
                                        $playerListArray[$plrkey]['team_id'] = $teamIds[$dencode['team_code']];
                                        $playerListArray[$plrkey]['fppg'] = $plrvalue['fppg'];
                                        $playerListArray[$plrkey]['plr_value'] = $plrvalue['plr_value'];
                                        $playerListArray[$plrkey]['injury_status'] = $plrvalue['injury_status'];
                                        $playerListArray[$plrkey]['injury_code'] = $plrvalue['injury_code'];
                                        $playerListArray[$plrkey]['injury_reason'] = $plrvalue['injury_reason'];
                                    }
                                }
                            }
							
							
                            if (!empty($playerListArray)) {
                                $fp = fopen('assets/csv/file.csv', 'w');
                                $header = array('id', 'name', 'position', 'age', 'team_name', 'pos_code', 'team_code');

                                $status = true;
                                $fieldData = array();
                                foreach ($playerListArray as $fkey => $fieldsVal) {
                                    if (isset($fieldsVal['id'])) {
                                        $fieldData['id'] = $fieldsVal['id'];
                                    }
                                    if (isset($fieldsVal['name'])) {
                                        $fieldData['name'] = $fieldsVal['name'];
                                    }
                                    if (isset($fieldsVal['position'])) {
                                        $fieldData['position'] = $fieldsVal['position'];
                                    }
                                    if (isset($fieldsVal['age'])) {
                                        $fieldData['age'] = $fieldsVal['age'];
                                    }
                                    if (isset($fieldsVal['team_name'])) {
                                        $fieldData['team_name'] = $fieldsVal['team_name'];
                                    }
                                    if (isset($fieldsVal['pos_code'])) {
                                        $fieldData['pos_code'] = $fieldsVal['pos_code'];
                                    }
                                    if (isset($fieldsVal['team_code'])) {
                                        $fieldData['team_code'] = $fieldsVal['team_code'];
                                    }

                                    if ($status) {
                                        fputcsv($fp, $header);
                                        $status = false;
                                    }
									//echo "<pre>"; print_r($fieldData); die;
                                    fputcsv($fp, $fieldData);
                                }
 
                                $this->view->playerListArray = $playerListArray;
                                $this->view->session->playerListArray = $playerListArray;

                                $this->view->searchValue = $searchValue;
                                $this->view->searchKey = $searchKey;

                                // without team filter
                                $filterPlayerList = $this->filterArray($searchValue, $playerListArray, $searchKey);
                                $value = array();
                                foreach ($filterPlayerList as $key => $row) {
                                    $value[$key] = $row['plr_value'];
                                }
                                array_multisort($value, SORT_DESC, $filterPlayerList);

                                if ($filterPlayerList) {
                                    $this->view->filterPlayerList = $filterPlayerList;
                                    $this->view->session->filterPlayerList = $filterPlayerList;
                                }
                            }

                            $this->view->teamCode = $teamCode;

                            $this->session->teamCode = $teamCode;

                            $this->view->sports = $sports;
                        }
                    }
                    $this->view->contestRes = $conResult;
//                   echo "<pre>"; print_r($teamCode);die;
                } else {
                    $this->_redirect('/home');
                }
            } else {
                $this->_redirect('/home');
            }
           } else {
                $this->_redirect('/home');
            } 
            
        }

        $this->view->conid = $conid;
    }

    

    public function contestAjaxHandlerAction() {


        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $userID = $this->view->session->storage->user_id;
        $objGamestatusModel = Application_Model_GameStats::getInstance();
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objContestModel = Application_Model_Contests::getInstance();
        $objUserAccountModel = Application_Model_UserAccount::getInstance();
        $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
        $objGamePlayersDetails = Application_Model_GamePlayersDetails::getInstance();

        if ($this->getRequest()->isPost()) {
            $method = $this->getRequest()->getParam('method');

            switch ($method) {
                case 'getcontestdata':
                    $sportsID = $this->getRequest()->getParam('sportsid');
                    $currentDate = date('Y-m-d');
                    $statsData = $objGamestatusModel->getGameStats2($sportsID, $currentDate);
					//echo "<pre>"; print_r($statsData); echo "</pre>";die; 

                    $responce = new stdClass();
                    if ($statsData) {
						foreach($statsData as $k=>$v){
							$data[] = json_decode($v['game_stat']);								
						}   
						//print_r($data); die;
                        $time = array();

                        $currentTime = time();
                        if (isset($data)) {
							foreach($data as $ks=>$line){
								
								foreach ($line->match as $val) {
									$mTime = $val->formatted_date." ".$val->time;
									$matchTime = strtotime($mTime);
									$matchTime2 = strtotime('-15 minutes',$matchTime);
									if($matchTime2 > $currentTime){
										
										$time[] = date('M d', strtotime($val->formatted_date)) . ' ' . (string) $val->time . ' ' . $line->timezone;
										//modify by vinay 3-11-2014
										$homeTeam = $objGamePlayersDetails->getTeamCodeByTeamName($sportsID, $val->hometeam->name);
										/* if(empty($homeTeam)) {
											$as[]= $val->hometeam->name;
										} */
										//echo "homeTeam"; print_r($homeTeam); 
										$awayTeam = $objGamePlayersDetails->getTeamCodeByTeamName($sportsID, $val->awayteam->name);
										/* if(empty($homeTeam)) {
											$as2[]= $val->awayteam->name;
										} */
										//echo "awayteam"; print_r($awayTeam);
										$gamesDetails[] = $homeTeam['plr_team_code'] . ' @ ' . $awayTeam['plr_team_code'] . ', ' . date('m/d', strtotime($val->formatted_date)) . ' ' . (string) $val->time;
									}
								}
								
							} 
						}
						///echo print_r($time); die;
						//echo "awayteam"; print_r($as2); 
                        if (isset($time) && isset($gamesDetails) && !empty($time) && !empty($gamesDetails)) {
                            $responce->code = 200;
                            $responce->data = array_count_values($time);
                            $responce->gamesdata = array_count_values($gamesDetails);
                        } else {
                            $responce->code = 198;
                            $responce->data = "error";
                        }
                    } else {
                        $responce->code = 198;
                        $responce->data = "error";
                    }
                    echo json_encode($responce);
                    break;
                case 'cancelcontest':
					
                    $userId = $this->view->session->storage->user_id;
                    $objUserAccount = Application_Model_UserAccount::getInstance();
                    $objUserLineupModel = Application_Model_UserLineup::getInstance();
                    $contestId = $this->getRequest()->getParam('cid');
                    $lineupId = $this->getRequest()->getParam('lid');
                    $lineupData = $objLineupModel->getLineupDetailsbyLid($lineupId);
                    $price = $this->getRequest()->getParam('price');
                    $objUserLineupModel->deleteLineup($lineupId, $contestId);
                    $objContestModel->updateContestEntry($contestId);
					
					
					$objContestTransactiuonModel = Application_Model_ContestTransactions::getInstance();
					$response = $objContestTransactiuonModel->getContestEnteryByUserIdAndContestId($contestId,$userId);
					
					if(!empty($response['transaction_id'])){
						$objUserTransactionModal = Application_Model_UserTransactions::getInstance();
						$txn_data = $objUserTransactionModal->getTransactionDetailById($response['transaction_id']);
					}
					
					if(!empty($txn_data)) {
						if(!empty($txn_data['transaction_type']) && $txn_data['transaction_type']=="Ticket Code"){
							$res = 1;
						} else {
							$res = 0;
						}
					} else {
						$res = 0;
					}
					if($res){
						$result = 1;
					} else {
                        $result = $objUserAccountModel->updateBalance($userID, $price);
                        if ($result) {
                            $transactions['user_id'] = $userID;
                            $transactions['transaction_type'] = 'Support';
                            $transactions['transaction_amount'] = $price;
                            $transactions['confirmation_code'] = 'N/A';
                            $transactions['description'] = 'Credit Amount';
                            $transactions['status'] = '1';
                            $transactions['request_type'] = '4';
                            $transactions['transaction_date'] = date('Y-m-d');

                            $objUserTransactionsModel->insertUseTransactions($transactions);

                            $this->view->session->storage->userBalance +=$price;
                        }
					}
					$responce = new stdClass();
					if ($result) {
						$responce->code = 200;
						$responce->msg = "success";
					} else {
						$responce->code = 198;
						$responce->msg = "error";
					}

                    echo json_encode($responce);
                    break;

                case 'buyticket':

                    $objTicketModel = Application_Model_TicketSystem ::getInstance();
                    $objUserAccountModel = Application_Model_UserAccount::getInstance();
                    $ticketId = $this->getRequest()->getParam('ticketID');
                    $ticketData = $objTicketModel->getTicketDetailsById($ticketId);
                    $this->ticketManager($ticketId, $userID);
                    $result = $objUserAccountModel->updateUserFppBalance($userID, $ticketData['fpp']);
                    $responce = new stdClass();
                    if ($result) {
                        $responce->code = 200;
                        $responce->msg = "success";
                    } else {
                        $responce->code = 198;
                        $responce->msg = "error";
                    }
                    echo json_encode($responce);
                    break;
                case 'useticket': 
                    $code = $this->getRequest()->getParam('code');
                    $contestId = $this->getRequest()->getParam('contestId');
                    
                    
                    $objContestModel = Application_Model_Contests::getInstance();
                    $objTicketSystemModel = Application_Model_TicketSystem::getInstance();
                    $objTicketUserModel = Application_Model_TicketUsers::getInstance();
                    
                    $ticketData = $objTicketSystemModel->getTicketByCode($code);
                    
                    $response = new stdClass();
                    if(isset($ticketData) && !empty($ticketData)){
                        $currDate = time();
                        $userId = $this->view->session->storage->user_id;
                        $ticketId = $ticketData['ticket_id'];
                        $ticketContestId = $ticketData['contest_id'];
                        $ticketLimit = intval($ticketData['ticket_use_limit']);
                        $ticketExpiry = strtotime($ticketData['valid_upto']);
                        
                        if($ticketData['status'] !=0){
                        if($currDate < $ticketExpiry){ // check ticket expiry
                            
                            $allowUse = false;
                            if($ticketLimit != 0){
                                $ticketUsers = $objTicketUserModel->allticketUsers($ticketId,$contestId);
                                if(isset($ticketUsers) && !empty($ticketUsers)){
                                    $ticketUseCount = count($ticketUsers);
                                    if(intval($ticketUseCount) < $ticketLimit){ // check the total ticket users limit
                                      
                                        $userLimit = $objTicketUserModel->getUserById($userId,$ticketId,$contestId);
                                        if(isset($userLimit) && !empty($userLimit)){
                                            $count = count($userLimit);
                                            $userCount = $ticketData['user_limit'];
                                            if(intval($count) < $userCount){ // check current user usage limit
                                                 $allowUse = true;
                                            }else{
                                                $response->code  = 197;
                                                $response->message = "Your already use this ticket";
                                            }
                                        }else{ // user didnt used this ticket
                                             $allowUse = true;
                                        }
                                    }else{
                                        $response->code  = 197;
                                        $response->message = "Ticket uses limit is over";
                                    }
                                }else{
                                    $allowUse = true;
                                }
                            }else{
                                $allowUse = true;
                            }
                            if($allowUse){
                                
//                                $ticketUser['user_id'] = $userId;
//                                $ticketUser['ticket_id'] = $ticketId;
//                                $ticketUser['contest_id'] = $contestId;
//                                $ticketUser['use_date'] = date('Y-m-d');
//                                $check = $objTicketUserModel->insertTicketUser($ticketUser); 
//                                if($check){
                                    $response->code  = 200;
                                    $response->message = "Ticket successfully applied";
                                    $response->data = $ticketId;
//                                }else{
//                                    $response->code  = 197;
//                                    $response->message = "Unable to use this ticket";
//                                }
                            }
                        }else{
                            $response->code  = 197;
                            $response->message = "This Ticket has been expired on ".$ticketData['valid_upto'];
                        }
                        }else{
                            $response->code  = 197;
                            $response->message = "This Ticket is inactive";
                        }
                    }else{
                        $response->code  = 197;
                        $response->message = "Ticket code is invalid";
                    }
                    
                   
                    echo json_encode($response);
                    break;
                    default:
                    break;
            }
        }
    }

    public function filterTeamArray($searchPos, $playerListArray, $searchKey, $searchTeam = null) {

        if ($searchPos != "" && !empty($playerListArray) && $searchKey != "" && $searchTeam == null) {
            $objParser = Engine_Utilities_GameXmlParser::getInstance();
            $filterPlayerList = $objParser->filterArray($searchPos, $playerListArray, 'pos_code');
            return $filterPlayerList;
        }

        if ($searchPos != "" && !empty($playerListArray) && $searchKey != "" && !empty($searchTeam)) {
            $objParser = Engine_Utilities_GameXmlParser::getInstance();
            $filterPlayerList = array();

            foreach ($searchTeam as $code) {
                if ($code != "") { //echo $code; echo $searchKey;
                    $filterPlayerList[] = $objParser->filterArray($code, $playerListArray, 'team_code');
                }
            }


            if (!empty($filterPlayerList)) {

                $playerList = array();
                foreach ($filterPlayerList as $data) {
                    $playerList = array_merge($playerList, $data);
                }

                if (!empty($playerList)) {
                    $newPlayerList = $objParser->filterArray($searchPos, $playerList, 'pos_code');
                    return $newPlayerList;
                }
            }
        }
    }

    /**
     * Developer     : Vivek Chaudhari   
     * Date          : 24/07/2014
     * Description   : contest details popup in draftteam page
     */
    public function contestDetailsAction() {
        $this->_helper->layout()->disableLayout();
        $cid = $this->getRequest()->getparam('cid');
        $drafttype = $this->getRequest()->getParam('drafttype');
        $this->view->drafttype = $drafttype;
        $this->view->contest_id = $cid;
        $objContestsModel = Application_Model_Contests::getInstance();
        $objTicketModel = Application_Model_TicketSystem::getInstance();
        $details = $objContestsModel->getContestsDetailsById($cid);

        $namesdata = $objContestsModel->getUsernameByContestId($cid);
        $allnames = array();
        if ((isset($namesdata)) && ($namesdata != "")):
            foreach ($namesdata as $key => $value):
                array_push($allnames, $value['user_name']);
            endforeach;
        endif;

        $username = array_count_values($allnames);
		
        if (isset($details['prize_payouts'])) {
            $payouts = json_decode($details['prize_payouts']);
			//echo "<pre>"; print_r($payouts); die;
            $prizeDetails = array();
            if (!empty($payouts)) {
                foreach ($payouts as $key => $value) {
                    if ((($value->from) >= 10) && (($value->from) <= 20)) {
                        $prizeDetails[$key]['from'] = ($value->from) . "th";
                    } else {
                        $from = substr(($value->from), -1);
                        if ($from == 1) {
                            $prizeDetails[$key]['from'] = ($value->from) . "st";
                        } else if ($from == 2) {
                            $prizeDetails[$key]['from'] = ($value->from) . "nd";
                        } else if ($from == 3) {
                            $prizeDetails[$key]['from'] = ($value->from) . "rd";
                        } else if ($from >= 4) {
                            $prizeDetails[$key]['from'] = ($value->from) . "th";
                        } else if ($from == 0) {
                            $prizeDetails[$key]['from'] = ($value->from) . "th";
                        }
                    }
                    if (($value->to) != 0) {
                        if ((($value->to) >= 10) && (($value->to) <= 20)) {
                            $prizeDetails[$key]['to'] = ($value->to) . "th";
                        } else {
                            $to = substr(($value->to), -1);
                            if ($to == 1) {
                                $prizeDetails[$key]['to'] = ($value->to) . "st";
                            } else if ($to == 2) {
                                $prizeDetails[$key]['to'] = ($value->to) . "nd";
                            } else if ($to == 3) {
                                $prizeDetails[$key]['to'] = ($value->to) . "rd";
                            } else if ($to >= 4) {
                                $prizeDetails[$key]['to'] = ($value->to) . "th";
                            } else if ($to == 0) {
                                $prizeDetails[$key]['to'] = ($value->to) . "th";
                            }
                        }
                    }
                    if (($value->type) == 0) {
                        $prizeDetails[$key]['prize'] = $value->amount." DFS";
                    } else if (($value->type) == 1) {
                        $ticketId = $value->ticket_id;
                        $data = $objTicketModel->getTicketDetailsById($ticketId);
                        $prizeDetails[$key]['prize'] = "(" . $data['bonus_amt'] . " DFS) Ticket";
                    }
                }
            }
            if (isset($prizeDetails)) {
                $this->view->prize_details = $prizeDetails;
            }
        }

        if ($details) {
            $this->view->details = $details;
        }
        if ($username) {
            $this->view->username = $username;
        }
    }

    public function depositAction() {

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->getRequest()->isPost()) {

            $objUserAccount = Application_Model_UserAccount::getInstance();
            $objUserModel = Application_Model_Users::getInstance();
            $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();

            $userId = $this->view->session->storage->user_id;

            $userAccountDetails = $objUserModel->getUserDetailsByUserId($userId);

            $objSettingsModel = Application_Model_Settings::getInstance();

            $settings = $objSettingsModel->getSettings();

            $bonusradio = $this->getRequest()->getPost('bonus-radio');
            $custom_amt = $this->getRequest()->getPost('custom_amt');

            if ($bonusradio == -1) {

                $custom_bns = $this->getRequest()->getPost('custom_bns');
            } else {

                if ($custom_amt > $settings['bonus_amt_limit']) {

                    $custom_bns = $settings['bonus_amt_limit'];
                } else {

                    $custom_bns = $custom_amt;
                }
            }


            $creditCardType = $this->getRequest()->getPost('CardType');
            $creditCardNumber = $this->getRequest()->getPost('CreditCard');
            $expDate = $this->getRequest()->getPost('Expiration');
            $cvv = $this->getRequest()->getPost('CVV');
            $firstName = "";
            $lastName = "";
            $address1 = "";
            $address2 = "";
            $city = "";
            $zipCode = "";
            $countryId = "";
            $state = "";
            $phone = "";

            $objPaypal = Engine_Payment_Paypal_Paypal::getInstance();

            $returnURL = "http://" . $_SERVER['HTTP_HOST'] . "/success";
            $cancelURL = "http://" . $_SERVER['HTTP_HOST'] . "/cancel-order";



            $response = $objPaypal->DirectPayment($custom_amt, $creditCardType, $creditCardNumber, $expDate, $cvv, $firstName, $lastName, $address1, $city, $state, $zipCode, $countryId, $returnURL, $cancelURL);

            if ($response['ACK'] == "Success") {

                $transactions['user_id'] = $userId;
                $transactions['transaction_type'] = 'Paypal';
                $transactions['transaction_amount'] = $response['AMT'];
                $transactions['confirmation_code'] = $response['TRANSACTIONID'];
                $transactions['description'] = 'description';
                $transactions['status'] = '1';
                $transactions['transaction_date'] = date('Y-m-d');

                $objUserTransactionsModel->insertUseTransactions($transactions);
                $accountData = array('balance_amt' => $custom_amt, 'bonus_amt' => $custom_bns);
                $objUserAccount->updateUserBalance($userId, $accountData);

                echo 1;
                die;
            } else {
                echo json_encode($response['L_LONGMESSAGE0']);
                die;
            }
        }
    }

    public function newLineupAction() {

        $this->_helper->layout()->disableLayout();
        
        if ($this->getRequest()->isPost()) {
            $objContestsModel = Application_Model_Contests::getInstance();
            $objSettingModel = Application_Model_Settings::getInstance();
            $objGameStats = Application_Model_GameStats::getInstance();
            $objAbbreviation = Engine_Utilities_Abbreviations::getInstance();
            $gameID = $this->getRequest()->getParam('gid');
            $time = $this->getRequest()->getParam('time');
            $this->view->time = $time;
            $conResult['sports_id'] = $gameID;
            $this->view->sports_id = $gameID;

            $date = rtrim($time, "EST");
            $conResult['game_stat'] = $date;
            $date = date('Y-m-d', strtotime($date));

            $settings = $objSettingModel->getSettings();
            if ($settings) {
                $this->view->settings = $settings;
            }
			
            switch ($conResult['sports_id']) {
                case 1:

                    $response = $objGameStats->getGameStats($conResult['sports_id'], $date);
                    $abbreviation = $objAbbreviation->getNFLAbbreviations(); // get team Abbreviations
                    $sports = 'NFL';
                    $searchValue = "QB";
                    $searchKey = "pos_code";
                    break;
                case 2:

                    $response = $objGameStats->getGameStats($conResult['sports_id'], $date);

                    $abbreviation = $objAbbreviation->getMLBAbbreviations(); // get team Abbreviations

                    $sports = 'MLB';
                    $searchValue = "P";
                    $searchKey = "pos_code";
                    break;
                case 3:

                    $response = $objGameStats->getGameStats($conResult['sports_id'], $date);
                    $abbreviation = $objAbbreviation->getNBAAbbreviations1(); // get team Abbreviations
                    $sports = 'NBA';
                    $searchValue = "PG";
                    $searchKey = "pos_code";
                    break;
                case 4:

                    $response = $objGameStats->getGameStats($conResult['sports_id'], $date);
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

                $contestDate = strtotime(date('Y-m-d', strtotime($conResult['game_stat'])));
                //   $contestDate = strtotime(date('Y-m-d', strtotime($conResult['start_time'])));

                $matchCount = count($contest_res['match']);
                if ($matchCount == 1) {
                    $response = $objGameStats->getFutureGameStats($conResult['sports_id'], $date, 5);

                    if ($response) {
                        $contest_res = array();
                        foreach ($response as $res) {
                            $game_stat = json_decode($res['game_stat'], true);
                            $gameLastDate = date('Y-m-d', strtotime($game_stat['formatted_date']));
                            $contest_res[] = $game_stat;
                        }
                    }

                } else {
                    $gameLastDate = date('Y-m-d', strtotime($contest_res['formatted_date']));
                }

                $this->view->matchDetails = $contest_res;

                if (isset($abbreviation)) {

                    $abbreviation = (array) json_decode($abbreviation);

                    $this->view->abbreviation = $abbreviation;
                     
                    $teamCode = array();
                    $team = array();
					$homeTeam_array = array();
                    $i = 0;

                    if ($matchCount != 1) {
                        //create array to get team code for hometeam and away team
                        foreach ($contest_res['match'] as $matchDetails) {
                            $hometeamName = array_search($matchDetails['hometeam']['name'], $abbreviation); //echo "<pre>"; print_r($matchDetails); echo "</pre>";
                            $awayteamName = array_search($matchDetails['awayteam']['name'], $abbreviation);
                            $teamCode[$i]['time'] = $matchDetails['formatted_date'] . $matchDetails['time'];
                            $teamCode[$i]['hometeam']['name'] = $hometeamName;
                            $teamCode[$i]['hometeam']['id'] = $matchDetails['hometeam']['id'];
                            $teamCode[$i]['awayteam']['name'] = $awayteamName;
                            $teamCode[$i]['awayteam']['id'] = $matchDetails['awayteam']['id'];
							$homeTeam_array[$hometeamName] = $awayteamName."@".$hometeamName;
							$homeTeam_array[$awayteamName] = $awayteamName."@".$hometeamName;
                            $team[$hometeamName] = $awayteamName;
                            $team[$awayteamName] = $hometeamName;
                            $i++;
                        }
                    } else {
                        //create array to get team code for hometeam and away team
                        foreach ($contest_res as $conres) {

                            foreach ($conres['match'] as $matchDetails) {
                                $hometeamName = array_search($matchDetails['hometeam']['name'], $abbreviation); //echo "<pre>"; print_r($matchDetails); echo "</pre>";
                                $awayteamName = array_search($matchDetails['awayteam']['name'], $abbreviation);
                                $teamCode[$i]['time'] = $matchDetails['formatted_date'] . $matchDetails['time'];
                                $teamCode[$i]['hometeam']['name'] = $hometeamName;
                                $teamCode[$i]['hometeam']['id'] = $matchDetails['hometeam']['id'];
                                $teamCode[$i]['awayteam']['name'] = $awayteamName;
                                $teamCode[$i]['awayteam']['id'] = $matchDetails['awayteam']['id'];
                                $team[$hometeamName] = $awayteamName;
                                $team[$awayteamName] = $hometeamName;
								$homeTeam_array[$hometeamName] = $awayteamName."@".$hometeamName;
								$homeTeam_array[$awayteamName] = $awayteamName."@".$hometeamName;
                                $i++;
                            }
                        }
                    }
					
					$this->view->homeTeam_array = $homeTeam_array;

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

                        $objGamePlayers = Application_Model_GamePlayers::getInstance();

                        $teamString = implode("','", $mergeTeamName);



                        $playerLists = $objGamePlayers->getPlayersByGameTeam($conResult['sports_id'], $teamString);

                        $playerListArray = array();

                        foreach ($playerLists as $key => $value) {
                            $dencode = json_decode($playerLists[$key]['plr_details'], true);
                           
						   $playerListArray[$key] = $dencode;
							$home_arr = explode('@',$homeTeam_array[$dencode['team_code']]);
							if($home_arr[0]==$dencode['team_code']){
								$match_code =  "<span class='font3'>".$home_arr[0]."</span> @ ".$home_arr[1];
							}else{
								$match_code = $home_arr[0]." @ <span class='font3'>".$home_arr[1]."</span>";
							}
							$playerListArray[$key]['match_code'] = $match_code;
                            $playerListArray[$key]['team_vs'] = $team[$dencode['team_code']];
                            $playerListArray[$key]['injury_status'] = $value['injury_status'];
							$playerListArray[$key]['injury_code'] = $value['injury_code'];
							$playerListArray[$key]['injury_reason'] = $value['injury_reason'];
                            $playerListArray[$key]['team_id'] = $teamIds[$dencode['team_code']];
                            $playerListArray[$key]['plr_value'] = $value['plr_value'];
                            $playerListArray[$key]['fppg'] = $value['fppg'];
                        }



                        if (!empty($playerListArray)) {

                            $fp = fopen('assets/csv/file.csv', 'w');
                            $header = array('name', 'position', 'team_name', 'pos_code', 'team_code');
                            $status = true;
                            foreach ($playerListArray as $fields) {
                                $data['name'] = $fields['name'];
                                $data['position'] = $fields['position'];

                                $data['team_name'] = $fields['team_name'];
                                $data['pos_code'] = $fields['pos_code'];
                                $data['team_code'] = $fields['team_code'];

                                if ($status) {
                                    fputcsv($fp, $header);
                                    $status = false;
                                }
                                fputcsv($fp, $data);
                            }

                            $this->view->playerListArray = $playerListArray;
                            $this->view->session->playerListArray = $playerListArray;

                            $this->view->searchValue = $searchValue;
                            $this->view->searchKey = $searchKey;

                            // without team filter
                            $filterPlayerList = $this->filterArray($searchValue, $playerListArray, $searchKey);
                            $value = array();
                            foreach ($filterPlayerList as $key => $row) {
                                $value[$key] = $row['plr_value'];
                            }
                            array_multisort($value, SORT_DESC, $filterPlayerList);
                            if ($filterPlayerList) {
                                $this->view->filterPlayerList = $filterPlayerList;
                                $this->view->session->filterPlayerList = $filterPlayerList;
                            }
                        }

                        $this->view->teamCode = $teamCode;
                        $this->view->sports = $sports;
                    }
                }
                $this->view->contestRes = $conResult;
            }
        }
    }
     
    
    public function editLineupAction() {
        $this->_helper->layout()->disableLayout();
        
        $objUserLineupModel = Application_Model_UserLineup::getInstance();
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objSportsDetails = Application_Model_Sports::getInstance();
        $objGameStats = Application_Model_GameStats::getInstance();
        $objAbbreviation = Engine_Utilities_Abbreviations::getInstance();
        $objContestModel = Application_Model_Contests::getInstance();
        $objSettingModel = Application_Model_Settings::getInstance();
        $lineupID = $this->getRequest()->getParam('lid');
        $this->view->mylineup_id = $lineupID;
        $lineupDetails = $objLineupModel->getLineupByLid($lineupID);
        $this->view->lineupDetails = $lineupDetails;
        $starttime = strtotime($lineupDetails['start_time']);
        $currentTime = time()+300;
        if($currentTime > $starttime){
            $this->view->error = "Time to edit this lineup is Over, You can not edit this lineup";
        }else{
            
            $settings = $objSettingModel->getSettings(); // print_r($settings); die;
            $this->view->rem_salary = $lineupDetails['rem_salary'];
            if ($settings) {
                $this->view->settings = $settings;
            }
            
            $date = date('Y-m-d',$starttime);
            switch ($lineupDetails['sports_id']) {
                
				case 1:
					$response = $objGameStats->getGameStats($lineupDetails['sports_id'], $date);
					$abbreviation = $objAbbreviation->getNFLAbbreviations(); // get team Abbreviations
					$sports = 'NFL';
					$searchValue = "QB";
					$searchKey = "pos_code";
					break;
				case 2:

					$response = $objGameStats->getGameStats($lineupDetails['sports_id'], $date);

					$abbreviation = $objAbbreviation->getMLBAbbreviations(); // get team Abbreviations

					$sports = 'MLB';
					$searchValue = "P";
					$searchKey = "pos_code";
					break;
				case 3:

					$response = $objGameStats->getGameStats($lineupDetails['sports_id'], $date);
					$abbreviation = $objAbbreviation->getNBAAbbreviations1(); // get team Abbreviations
					$sports = 'NBA';
					$searchValue = "PG";
					$searchKey = "pos_code";
					break;
				case 4:

					$response = $objGameStats->getGameStats($lineupDetails['sports_id'], $date);
					$abbreviation = $objAbbreviation->getNHLAbbreviations(); // get team Abbreviations
					$sports = 'NHL';
					$searchValue = "C";
					$searchKey = "pos_code";
					break;

				default:
					break;
			}
        
			if (isset($response)) {
				$gameStat = json_decode($response['game_stat'], true);
				$userLineup = $objLineupModel->getLineupDetailsbyLid($lineupID);
				if(!empty($userLineup)){
					if($userLineup['contest_id'] !=0){
						$conDetails = $objContestModel->getContestsDetailsById($userLineup['contest_id']);
							
						$contestStartTimeStamp = strtotime($conDetails['start_time']);
						$contestEndTimeStamp = strtotime($conDetails['end_time']);
						foreach ($gameStat['match'] as $crkey => $crData) {
							$matchTimeStamp = strtotime($crData['formatted_date'] . $crData['time']);
							if (($matchTimeStamp > $contestStartTimeStamp && $matchTimeStamp > $contestEndTimeStamp)
									|| ($matchTimeStamp < $contestStartTimeStamp)
							) {
								unset($gameStat['match'][$crkey]);
							}
						}
						
					}
				}

				$matchCount = count($gameStat['match']);
				if ($matchCount == 1) {
					$matchResponse = $objGameStats->getFutureGameStats($lineupDetails['sports_id'], $date, 5);
					if ($matchResponse) {
						$contest_res = array();
						$currentTimeStamp = time();
						foreach ($matchResponse as $res) {
							$game_stat = json_decode($res['game_stat'], true);
							foreach ($game_stat['match'] as $extKey => $extMatches) {
								$matchTimeStamp = strtotime($extMatches['formatted_date'] . $extMatches['time']);
								if ($currentTimeStamp > $matchTimeStamp) {
									unset($game_stat['match'][$extKey]);
								}
							}
							$gameStat['match'] = array_merge($gameStat['match'], $game_stat['match']);
						}
					}
				}
					
				if (isset($abbreviation)) {
					$abbreviation = (array) json_decode($abbreviation);
					$teamCode = array();
					$team = array();
					$homeTeam_array = array();
					$i = 0;
					$matchCount = count($gameStat['match']);
					if ($matchCount != 1) {
						//create array to get team code for hometeam and away team
						foreach ($gameStat['match'] as $matchDetails) {
							$hometeamName = array_search($matchDetails['hometeam']['name'], $abbreviation); //echo "<pre>"; print_r($matchDetails); echo "</pre>";
							$awayteamName = array_search($matchDetails['awayteam']['name'], $abbreviation);
							$teamCode[$i]['time'] = $matchDetails['formatted_date'] . $matchDetails['time'];
							$teamCode[$i]['hometeam']['name'] = $hometeamName;
							$teamCode[$i]['hometeam']['id'] = $matchDetails['hometeam']['id'];
							$teamCode[$i]['awayteam']['name'] = $awayteamName;
							$teamCode[$i]['awayteam']['id'] = $matchDetails['awayteam']['id'];
							$homeTeam_array[$hometeamName] = $awayteamName."@".$hometeamName;
							$homeTeam_array[$awayteamName] = $awayteamName."@".$hometeamName;
							$team[$hometeamName] = $awayteamName;
							$team[$awayteamName] = $hometeamName;
							$i++;
						}
					} 
					
					$this->view->homeTeam_array = $homeTeam_array;
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

						$objGamePlayers = Application_Model_GamePlayers::getInstance();

						$teamString = implode("','", $mergeTeamName);
						$playerLists = $objGamePlayers->getPlayersByGameTeam($lineupDetails['sports_id'], $teamString);

						$userLineupDetails = json_decode($userLineup['player_ids'], true);
						$position = json_decode($userLineup['pos_details'], true);
						$playerListArray = array();
						$mylineupPlayerDetails = array();
						foreach ($playerLists as $key => $value) {

							$dencode = json_decode($playerLists[$key]['plr_details'], true);
							
							$check = array_search($dencode['id'], $userLineupDetails);
							
							if ($check !== false) {
								$dencode['position'] = $position[$dencode['id']];
								$dencode['pos_code'] = $position[$dencode['id']];
								
								$mylineupPlayerDetails[$check] = $dencode;
								$mylineupPlayerDetails[$check]['team_vs'] = $team[$dencode['team_code']];
								$mylineupPlayerDetails[$check]['plr_value'] = $value['plr_value'];
								$mylineupPlayerDetails[$check]['fppg'] = $value['fppg'];
								$mylineupPlayerDetails[$check]['team_id'] = $teamIds[$dencode['team_code']];
							}else{
								$playerListArray[$key] = $dencode;
							
								$home_arr = explode('@',$homeTeam_array[$dencode['team_code']]);
											
								if($home_arr[0]==$dencode['team_code']){
									$match_code =  "<span class='font3'>".$home_arr[0]."</span> @ ".$home_arr[1];
								}else{
									$match_code = $home_arr[0]." @ <span class='font3'>".$home_arr[1]."</span>";
								}
								$playerListArray[$key]['match_code'] = $match_code;
								
								$playerListArray[$key]['team_vs'] = $team[$dencode['team_code']];
								$playerListArray[$key]['team_id'] = $teamIds[$dencode['team_code']];
								$playerListArray[$key]['plr_value'] = $value['plr_value'];
								$playerListArray[$key]['fppg'] = $value['fppg'];
								$playerListArray[$key]['injury_status'] = $value['injury_status'];
								$playerListArray[$key]['injury_reason'] = $value['injury_reason'];
								$playerListArray[$key]['injury_code'] = $value['injury_code'];
							}
						}
						
						ksort($mylineupPlayerDetails);
						$objFunctions = Engine_Utilities_GameXmlParser::getInstance();
						switch ($lineupDetails['sports_id']) {
							case 1:
								$mylineup = $objFunctions->arrangeNFLineUp($mylineupPlayerDetails);
								break;
							case 2:
								$mylineup = $objFunctions->arrangeMLBLineUp($mylineupPlayerDetails);
								break;
							case 3:
								$mylineup = $objFunctions->arrangeNBALineUp($mylineupPlayerDetails);
								break;
							case 4:
								$mylineup = $objFunctions->arrangeNHLineUp($mylineupPlayerDetails);
								break;
							default:
								break;
						}
						
						$this->view->mylineupDetails = $mylineup;
						
						if (!empty($playerListArray)) {
							
							$this->view->session->playerListArray = $playerListArray;

							$this->view->searchValue = $searchValue;
							$this->view->searchKey = $searchKey;

							/* without team filter */
							$filterPlayerList = $this->filterArray($searchValue, $playerListArray, $searchKey);
							$value = array();
							foreach ($filterPlayerList as $key => $row) {
								$value[$key] = $row['plr_value'];
							}
							array_multisort($value, SORT_DESC, $filterPlayerList);

							if ($filterPlayerList) {
								$this->view->filterPlayerList = $filterPlayerList;
								$this->view->session->filterPlayerList = $filterPlayerList;
							}
						}
						$this->view->sports = $sports;
						$this->view->sports_id = $lineupDetails['sports_id'];
					}
				}
            
			}else{
				 $this->view->error = "You can not edit this Lineup";
			}
		}
	}
    
	
    /**
     * Developer    : Vivek Chaudhari
     * Date         : 31/07/2014
     * Description  : swap lineup details
     */
    public function swapLineupAction() {
        $user_id = $this->view->session->storage->user_id;
        $this->_helper->layout()->disableLayout();
        //$this->_helper->viewRenderer->setNoRender(true);
        if ($this->getRequest()->isPost()) {
            $objUserLineupModel = Application_Model_UserLineup::getInstance();
            $objLineupModel = Application_Model_Lineup::getInstance();
            $objGamePlayers = Application_Model_GamePlayers::getInstance();
            $objGamePlrModel = Application_Model_GamePlayers::getInstance();
            $objContests = Application_Model_Contests::getInstance();

            $objGamePlayerModel = Application_Model_GamePlayers::getInstance();
            $objLineupModel = Application_Model_Lineup::getInstance();
            $objContestModel = Application_Model_Contests::getInstance();
            $objAbbreviation = Engine_Utilities_Abbreviations::getInstance();
            $objGameStats = Application_Model_GameStats::getInstance();
            if ($this->getRequest()->isPost()) {

                $response = $objLineupModel->getLineupForSwap($user_id);
//        echo"<pre>";print_r($response);echo"</pre>";//die('test');
                if (empty($response)) {
                    $this->view->message = "Global player swap can only be used on Lineups in the Upcoming or Live state. You currently do not have any upcoming or Live lineups";
                } else {
                    $sports = array();
//            $swapLineup
                    $time = array();
                    $sportId = 0;
                    $lineupId = 0;
                    $swapLineups = array();
                    $sindex = 0;
                    $dropValue = array();

                    foreach ($response as $lineup) {
                        if (isset($dropValue[$lineup['sports_id']])) {
                            $dropValue[$lineup['sports_id']]['sport'] = $lineup['sports_id'];
                            $time = strtotime($lineup['start_time']);
                            if (!isset($dropValue[$lineup['sports_id']]['time'][$time])) {
                                $dropValue[$lineup['sports_id']]['time'][$time] = date('H:i D/m a', strtotime($lineup['start_time']));
                            }
                        } else {
                            $time = strtotime($lineup['start_time']);
                            $dropValue[$lineup['sports_id']]['sport'] = $lineup['sports_id'];
                            $dropValue[$lineup['sports_id']]['time'][$time] = date('H:i D/m a', strtotime($lineup['start_time']));
                        }

                        if (isset($lineup['contest_id'])) {
                            $conResult = $objContestModel->getContestsById($lineup['contest_id']); // get Active upcomming contest

                            if ($conResult) {
                                $game_date = date('Y-m-d', strtotime($conResult['start_time']));

                                switch ($conResult['sports_id']) {
                                    case 1:
                                        $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                                        $abbreviation = $objAbbreviation->getNFLAbbreviations(); // get team Abbreviations

                                        break;
                                    case 2:

                                        $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);

                                        $abbreviation = $objAbbreviation->getMLBAbbreviations(); // get team Abbreviations

                                        break;
                                    case 3:

                                        $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                                        $abbreviation = $objAbbreviation->getNBAAbbreviations1(); // get team Abbreviations

                                        break;
                                    case 4:

                                        $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                                        $abbreviation = $objAbbreviation->getNHLAbbreviations(); // get team Abbreviations

                                        break;

                                    default:
                                        break;
                                }
                                if (isset($abbreviation)) {
                                    $contest_res = json_decode($response['game_stat'], true);
                                    $contestDate = strtotime(date('Y-m-d', strtotime($conResult['start_time'])));

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

//                           $response = $objGamePlayerModel->getPlayersByGameTeam($lineup['sports_id'], $teamString);

                                        $playerIds = json_decode($lineup['player_ids'], true);
                                        $playerDetails = $objGamePlrModel->getPlayerListBySport($playerIds, $lineup['sports_id']);
//                            echo"<pre>";print_r($playerDetails);echo"</pre>";die('test');
                                        $playerLineup = array();
                                        $index = 0;
                                        $position = json_decode($lineup['pos_details'], true);
                                        foreach ($playerDetails as $player) {
                                            $decode = json_decode($player['plr_details'], true);
                                            $playerLineup[$index]['name'] = $decode['name'];
                                            $playerLineup[$index]['team_code'] = $decode['team_code'];
                                            if (isset($team[$decode['team_code']])) {
                                                $playerLineup[$index]['team_vs'] = $team[$decode['team_code']];
//                                    $playerLineup[$index]['team_id'] = $teamIds[$decode['team_code']];
                                            } else {
                                                $playerLineup[$index]['team_vs'] = $decode['team_code'];
//                                    $playerLineup[$index]['team_id'] = $decode['team_code'];
                                            }

                                            $playerLineup[$index]['pos_code'] = $decode['pos_code'];
                                            $playerLineup[$index]['position'] = $position[$player['plr_id']];
                                            $playerLineup[$index]['plr_id'] = $player['plr_id'];
                                            $playerLineup[$index]['plr_value'] = $player['plr_value'];
                                            $playerLineup[$index]['fppg'] = $player['fppg'];
                                            $index++;
                                        }
//                          echo"<pre>";print_r($lineup);echo"</pre>";die('test');  
                                        if (isset($lineup['sports_id'])) {
                                            $objParser = Engine_Utilities_GameXmlParser::getInstance();
                                            switch ($lineup['sports_id']) {
                                                case 1:
                                                    $playerLineup = $objParser->arrangeNFLineUp($playerLineup);
                                                    break;
                                                case 2:
                                                    $playerLineup = $objParser->arrangeMLBLineUp($playerLineup);
                                                    break;
                                                case 3:
                                                    $playerLineup = $objParser->arrangeNBALineUp($playerLineup);
                                                    break;
                                                case 4:
                                                    $playerLineup = $objParser->arrangeNHLineUp($playerLineup);
                                                    break;
                                                default:
                                                    break;
                                            }
                                        }
//                            echo"<pre>";print_r($playerLineup);echo"</pre>";
                                        $swapLineups[$sindex]['time'] = strtotime($lineup['start_time']);
                                        $swapLineups[$sindex]['formattedTime'] = date('H:i D,M a', strtotime($lineup['start_time']));
                                        $swapLineups[$sindex]['sportId'] = $lineup['sports_id'];
                                        $swapLineups[$sindex]['lineupId'] = $lineup['lineup_id'];

                                        $swapLineups[$sindex]['contestId'] = $lineup['contest_id'];
                                        $swapLineups[$sindex]['players'] = $playerLineup;
                                        $sindex++;
                                    }
                                }
                            }
                        }
                    }
//            echo"<pre>";print_r($sports);echo"</pre>";
//            echo"<pre>";print_r($dropValue);echo"</pre>";
//             echo"<pre>";print_r($swapLineups);echo"</pre>";//die('test');
                    if (isset($swapLineups)) {
                        $this->view->session->storage->swap_lineups = $swapLineups;
                        $this->view->session->storage->drop_value = $dropValue;
                        $this->view->mylineup = $swapLineups[0];
                        $this->view->drop_value = $dropValue;
                    }
                }
            }
        }
    }
   public function createChallengeAjaxAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $objGamestatusModel = Application_Model_GameStats::getInstance();
        $objContestModel = Application_Model_Contests::getInstance();
        $objGameModel = Application_Model_Sports::getInstance();
        $objAddressModel = Application_Model_BillingAddress::getInstance();
        $objContestTypeModel = Application_Model_ContestsType::getInstance();
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objUserAccount = Application_Model_UserAccount::getInstance();
        $objGamePlayersDetails = Application_Model_GamePlayersDetails::getInstance();

        $method = $this->getRequest()->getPost('method');
        $response = new stdClass();
        if ($method) {
            switch ($method) {
                case "getConType":
                    $lineupId = $this->getRequest()->getPost('lid');
                    $this->view->session->ch_lineup_id = $lineupId;
                    $lineupData = $objLineupModel->getLineupDetailsbyLid($lineupId);
                        switch ($lineupData['sports_id']) {
                            case 1:
                                $lineupData['sportname'] = "NFL";
                                break;
                            case 2 :
                                $lineupData['sportname'] = "MLB";
                                break;
                            case 3 :
                                $lineupData['sportname'] = "NBA";
                                break;
                            case 4:
                                $lineupData['sportname'] = "NHL";
                                break;
                        }
                        $lineupData['sporttime'] = date('M-d H:i A, T', strtotime($lineupData['start_time']));
                        $response->code = 200;
                        $response->data = $lineupData;
                        

                    break;
                case "createChallenge":

                    $contestType = $this->getRequest()->getPost('contestType');
                    $fee = $this->getRequest()->getPost('fee');
                    $time = $this->getRequest()->getPost('time');
                    $sportid = $this->getRequest()->getPost('sportid');
                    $playwith = $this->getRequest()->getPost('playwith');
                    $lineupId = $this->getRequest()->getPost('lineup_id');
                    $user_id = $this->view->session->storage->user_id;
                    $matchTime = strtotime($time);
                    $currentTime = time();
                    if ($currentTime < $matchTime) {

                        $contestData = array();
                        switch ($sportid) {
                            case 1: $sportname = "NFL";
                                break;
                            case 2: $sportname = "MLB";
                                break;
                            case 3 : $sportname = "NBA";
                                break;
                            case 4: $sportname = "NHL";
                                break;
                        }
                        if ($contestType == 1) {
                            $contestData['contest_name'] = $sportname . ' '.  $fee . ' DFS Head-To-Head Challenge By ' . $this->view->session->storage->user_name;
                            $contestData['con_type_id'] = 1;
                            $contestData['play_limit'] = 2;
                            $actamount = ($fee * 2) - (($fee * 2) * (0.1));
                            $contestData['prize_pool'] = $actamount;
                            $prizename = 'Winner Takes All';
                            $payout[0]['from'] = 1;
                            $payout[0]['to'] = 0;
                            $payout[0]['type'] = 0;
                            $payout[0]['amount'] = $actamount;
                            $payout[0]['ticket_id'] = NULL;
                        } else if ($contestType == 2) {

                            $contestData['contest_name'] = $sportname . ' ' . $fee . 'DFS League Challenge By ' . $this->view->session->storage->user_name;
                            $contestData['con_type_id'] = 2;

                            $prizetype = $this->getRequest()->getPost('prizetype');
                            $playlimit = $this->getRequest()->getPost('playlimit');

                            $payout = Array();
                            $amount = (($fee) * ($playlimit));
                            $percent = ($amount / 100) * 10;
                            $actamount = ($amount - $percent);
//                        $per = $actamount / 100;
                            $contestData['prize_pool'] = $actamount;
                            $contestData['play_limit'] = $playlimit;

                            if ($prizetype == '0') {
                                $prizename = 'No Prize';
                            } else if ($prizetype == '1') {
                                $prizename = 'Winner Takes All';
                                $payout[0]['from'] = 1;
                                $payout[0]['to'] = 0;
                                $payout[0]['type'] = 0;
                                $payout[0]['amount'] = $actamount;
                                $payout[0]['ticket_id'] = NULL;
                            } else if ($prizetype == '2') {
                                $prizename = 'Top 2 Win';
                                $top1 = ($actamount * 0.7);
                                $top2 = ($actamount * 0.3);

                                $payout[0]['from'] = 1;
                                $payout[0]['to'] = 0;
                                $payout[0]['type'] = 0;
                                $payout[0]['amount'] = $top1;
                                $payout[0]['ticket_id'] = NULL;

                                $payout[1]['from'] = 2;
                                $payout[1]['to'] = 0;
                                $payout[1]['type'] = 0;
                                $payout[1]['amount'] = $top2;
                                $payout[1]['ticket_id'] = NULL;
                            } else if ($prizetype == '3') {
                                $prizename = 'Top 3 Win';

                                $win1 = ($actamount * 0.5);
                                $win2 = ($actamount * 0.3);
                                $win3 = ($actamount * 0.2);

                                $payout[0]['from'] = 1;
                                $payout[0]['to'] = 0;
                                $payout[0]['type'] = 0;
                                $payout[0]['amount'] = $win1;
                                $payout[0]['ticket_id'] = NULL;

                                $payout[1]['from'] = 2;
                                $payout[1]['to'] = 0;
                                $payout[1]['type'] = 0;
                                $payout[1]['amount'] = $win2;
                                $payout[1]['ticket_id'] = NULL;

                                $payout[2]['from'] = 3;
                                $payout[2]['to'] = 0;
                                $payout[2]['type'] = 0;
                                $payout[2]['amount'] = $win3;
                                $payout[2]['ticket_id'] = NULL;
                            } else if ($prizetype == '4') {
                                $prizename = 'Top 5 Win';

                                $win1 = ($actamount * 0.3);
                                $win2 = ($actamount * 0.25);
                                $win3 = ($actamount * 0.2);
                                $win4 = ($actamount * 0.15);
                                $win5 = ($actamount * 0.10);

                                $payout[0]['from'] = 1;
                                $payout[0]['to'] = 0;
                                $payout[0]['type'] = 0;
                                $payout[0]['amount'] = $win1;
                                $payout[0]['ticket_id'] = NULL;

                                $payout[1]['from'] = 2;
                                $payout[1]['to'] = 0;
                                $payout[1]['type'] = 0;
                                $payout[1]['amount'] = $win2;
                                $payout[1]['ticket_id'] = NULL;

                                $payout[2]['from'] = 3;
                                $payout[2]['to'] = 0;
                                $payout[2]['type'] = 0;
                                $payout[2]['amount'] = $win3;
                                $payout[2]['ticket_id'] = NULL;

                                $payout[3]['from'] = 4;
                                $payout[3]['to'] = 0;
                                $payout[3]['type'] = 0;
                                $payout[3]['amount'] = $win4;
                                $payout[3]['ticket_id'] = NULL;

                                $payout[4]['from'] = 5;
                                $payout[4]['to'] = 0;
                                $payout[4]['type'] = 0;
                                $payout[4]['amount'] = $win5;
                                $payout[4]['ticket_id'] = NULL;
                            } else if ($prizetype == '5') {
                                $prizename = '50/50';

                                $players = round($playlimit / 2, 0);
                                $payAmt = $actamount / $players;
                                $payout[0]['from'] = 1;
                                $payout[0]['to'] = $players;
                                $payout[0]['type'] = 0;
                                $payout[0]['amount'] = $payAmt;
                                $payout[0]['ticket_id'] = NULL;
                            }
                        }
                        if (!empty($payout)) {
                            $payArr = json_encode($payout);
                            $contestData['prize_payouts'] = $payArr;
                        }
                        $contestData['sports_id'] = $sportid;
                        $contestData['start_time'] = $time;
                        $contestData['entry_fee'] = $fee;

                        $contestData['fpp'] = $fee * 4;
                        $contestData['created_by'] = $user_id;

                        $matchdate = date('Y-m-d', strtotime($time));
                        $matchResponse = $objGamestatusModel->getGameStats($sportid, $matchdate);

                        if (isset($matchResponse)) {
                            $contest_res = json_decode($matchResponse['game_stat'], true);
                            $matchCount = count($contest_res['match']);

                            if ($matchCount == 1) {
                                $matchResponse = $objGamestatusModel->getFutureGameStats($sportid, $matchdate, 5);

                                if ($matchResponse) {
                                    $contest_res = array();
                                    foreach ($matchResponse as $res) {
                                        $game_stat = json_decode($res['game_stat'], true);
                                        $contest_res[] = $game_stat;
                                        $matcharraypop = array_pop($game_stat['match']);
                                        $gameLastDate = date('Y-m-d H:i:s', strtotime($game_stat['formatted_date'] . ' ' . $matcharraypop['time']));
                                    }
                                }
                            } else {
                                $lastMatch = array_pop($contest_res['match']);
                                $gameLastDate = date('Y-m-d H:i:s', strtotime($lastMatch['formatted_date'] . ' ' . $lastMatch['time']));
                            }

                            $contestData['end_time'] = $gameLastDate;

                            $startTimeStamp = strtotime($contestData['start_time']);
                            $endTimeStamp = strtotime($contestData['end_time']);

                            if ($startTimeStamp == $endTimeStamp) {
                                $contestData['end_time'] = date('Y-m-d H:i:s', strtotime("+5 hours", strtotime($data['end_time'])));
                            }

                            $contestID = $objContestModel->insertContestsDetails($contestData);
                            if ($contestID) {
                                $lineupId = $this->view->session->ch_lineup_id;
                                if(isset($lineupId)){
                                    $objUserLineupModel = Application_Model_UserLineup::getInstance();
                                    $uLineupData['lineup_id'] = $lineupId;
                                    $uLineupData['contest_id'] = $contestID;
                                    $uLineupData['status'] = 1;
                                    $uLineupData['created_date'] = date('Y-m-d H:i:s');
                                    $objUserLineupModel->inserUserLineup($uLineupData);
                                    $objContestModel->updateTotalEntry($contestID);
                                }
                                $response->code = 200;
                                $response->data = $contestID;
                            } else {
                                $response->code = 198;
                                $response->message = "You can not create challenge.";
                            }
                        } else {
                            $response->code = 198;
                            $response->message = "You can not create challenge, please try later";
                        }
                    } else {
                        $response->code = 198;
                        $response->message = "You can not create challenge, please try later";
                    }



                    break;
                default:
                    break;
            }
        }
        echo json_encode($response, true);
    }

    /**
     * Developer     : Vivek Chaudhari   
     * Date          : 16/07/2014
     * Description   : edit lineup for selected contest
     */
    public function editContestLineupAction() {
		
        $lineupID = $this->getRequest()->getParam('clid');
        $objUserLineupModel = Application_Model_UserLineup::getInstance();
        $objSettingModel = Application_Model_Settings::getInstance();
        $objContestsModel = Application_Model_Contests::getInstance();
        $objAbbreviation = Engine_Utilities_Abbreviations::getInstance();
        $objGameStats = Application_Model_GameStats::getInstance();
        $objUserAcount = Application_Model_UserAccount::getInstance();
        $objGamePlayers = Application_Model_GamePlayers::getInstance();

        $data = $objUserLineupModel->getContestDetailsByLineup($lineupID);
		//echo "<pre>"; print_r($data); die;
        if (isset($data['contest_id'])) {


            $settings = $objSettingModel->getSettings(); // print_r($settings); die;
            $this->view->rem_salary = $data['rem_salary'];
            if ($settings) {
                $this->view->settings = $settings;
            }

            $conid = $data['contest_id'];

            $conResult = $objContestsModel->getContestsById($conid, '1', '0'); // get Active upcomming contest

            if ($conResult) {
                $game_date = date('Y-m-d', strtotime($conResult['start_time']));
                $end_date = $conResult['end_time'];

                $userBalance = $objUserAcount->getUserBalance($this->view->session->storage->user_id);
                $this->view->userBalance = $userBalance['balance_amt'];
                if ($userBalance['balance_amt'] >= $conResult['entry_fee']) {
                    $this->view->balance = $userBalance['balance_amt'];
                } else {
                    $this->view->lowbalance = $userBalance['balance_amt'];
                }

                $sports = null;
                $searchValue = "";
                $searchKey = "";

                $this->view->sports_id = $conResult['sports_id'];
                $this->view->conid = $conid;
                $this->view->start_time = $conResult['start_time'];
                $this->view->contesttime = strtotime($conResult['start_time']);
                switch ($conResult['sports_id']) {
                    case 1:

                        //$response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                        $response = $objGameStats->getGameStatsNew($conResult['sports_id'], $game_date, $end_date);
                        $abbreviation = $objAbbreviation->getNFLAbbreviations(); // get team Abbreviations
                        $sports = 'NFL';
                        $searchValue = "QB";
                        $searchKey = "pos_code";
                        break;
                    case 2:

                        //$response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                        $response = $objGameStats->getGameStatsNew($conResult['sports_id'], $game_date, $end_date);
                        $abbreviation = $objAbbreviation->getMLBAbbreviations(); // get team Abbreviations

                        $sports = 'MLB';
                        $searchValue = "P";
                        $searchKey = "pos_code";
                        break;
                    case 3:

                        //$response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                        $response = $objGameStats->getGameStatsNew($conResult['sports_id'], $game_date, $end_date);
                        $abbreviation = $objAbbreviation->getNBAAbbreviations1(); // get team Abbreviations
                        $sports = 'NBA';
                        $searchValue = "PG";
                        $searchKey = "pos_code";
                        break;
                    case 4:

                        //$response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                        $response = $objGameStats->getGameStatsNew($conResult['sports_id'], $game_date, $end_date);
                        $abbreviation = $objAbbreviation->getNHLAbbreviations(); // get team Abbreviations
                        $sports = 'NHL';
                        $searchValue = "C";
                        $searchKey = "pos_code";
                        break;

                    default:
                        break;
                }
                if (isset($response)) {
					//print"<pre>";print_r($response);print"</pre>";die;

                    $contest_res = array();

                    foreach ($response as $res) {
                        $game_stat = json_decode($res['game_stat'], true);
                        $contest_res[] = $game_stat;
                    }

                     $contestStartTimeStamp = strtotime($conResult['start_time']);
                     $contestEndTimeStamp = strtotime($conResult['end_time']);
                     foreach($contest_res as $rKey=>$resultVal){
                        foreach($resultVal['match'] as $crkey=>$crData){
                            $matchTimeStamp  = strtotime($crData['formatted_date'].$crData['time']);
                            if(($matchTimeStamp >=$contestStartTimeStamp && $matchTimeStamp > $contestEndTimeStamp)
                                    || ($matchTimeStamp < $contestStartTimeStamp)
                                    )
                                {
                              unset($contest_res[$rKey]['match'][$crkey]);
                            }
                        }
                     }

                    $contestDate = strtotime(date('Y-m-d', strtotime($conResult['start_time'])));

                    $this->view->matchDetails = $contest_res;

                    if (isset($abbreviation)) {

                        $abbreviation = (array) json_decode($abbreviation);

                        $this->view->abbreviation = $abbreviation;
                         
                        $teamCode = array();
                        $team = array();
						$homeTeam_array = array();
                        $i = 0;

                        //create array to get team code for hometeam and away team

                        foreach ($contest_res as $conres) {

                            foreach ($conres['match'] as $matchDetails) {
                                $hometeamName = array_search($matchDetails['hometeam']['name'], $abbreviation);
                                $awayteamName = array_search($matchDetails['awayteam']['name'], $abbreviation);
                                $teamCode[$i]['time'] = $matchDetails['formatted_date'].$matchDetails['time'];
                                $teamCode[$i]['hometeam']['name'] = $hometeamName;
                                $teamCode[$i]['hometeam']['id'] = $matchDetails['hometeam']['id'];
                                $teamCode[$i]['awayteam']['name'] = $awayteamName;
                                $teamCode[$i]['awayteam']['id'] = $matchDetails['awayteam']['id'];
								$homeTeam_array[$hometeamName] = $awayteamName."@".$hometeamName;
								$homeTeam_array[$awayteamName] = $awayteamName."@".$hometeamName;
                                $team[$hometeamName] = $awayteamName;
                                $team[$awayteamName] = $hometeamName;
                                $i++;
                            }
                        }
						
						$this->view->homeTeam_array = $homeTeam_array;

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

                            $playerLists = $objGamePlayers->getPlayersByGameTeam($conResult['sports_id'], $teamString);
							//echo "<pre>"; print_r($data); echo "</pre>"; die;
                            $playerListArray = array();

                            $userLineupDetails = json_decode($data['player_ids']);
                            $position = json_decode($data['pos_details'], true);
                            $playerListArray = array();
                            $mylineupPlayerDetails = array();
                            foreach ($playerLists as $key => $value) {

                                $dencode = json_decode($playerLists[$key]['plr_details'], true);
                                $playerListArray[$key] = $dencode;
								$home_arr = explode('@',$homeTeam_array[$dencode['team_code']]);
									
								if($home_arr[0]==$dencode['team_code']){
									$match_code =  "<span class='font3'>".$home_arr[0]."</span> @ ".$home_arr[1];
								}else{
									$match_code = $home_arr[0]." @ <span class='font3'>".$home_arr[1]."</span>";
								}
								$playerListArray[$key]['match_code'] = $match_code;
								
                                $playerListArray[$key]['team_vs'] = $team[$dencode['team_code']];
                                $playerListArray[$key]['team_id'] = $teamIds[$dencode['team_code']];
                                $playerListArray[$key]['fppg'] = $value['fppg'];
                                $playerListArray[$key]['injury_status'] = $value['injury_status'];
                                $playerListArray[$key]['injury_code'] = $value['injury_code']; 
                                $playerListArray[$key]['injury_reason'] = $value['injury_reason'];
                                $playerListArray[$key]['plr_value'] = $value['plr_value'];
                                $check = array_search($dencode['id'], $userLineupDetails);

                                if ($check !== false) {
                                    $dencode['pos_code'] = $position[$dencode['id']];
                                    $dencode['position'] = $position[$dencode['id']];

                                    $mylineupPlayerDetails[$check] = $dencode;
                                    $mylineupPlayerDetails[$check]['team_vs'] = $team[$dencode['team_code']];
                                    $mylineupPlayerDetails[$check]['team_id'] = $teamIds[$dencode['team_code']];
                                    $mylineupPlayerDetails[$check]['fppg'] = $value['fppg'];
                                    $mylineupPlayerDetails[$check]['plr_value'] = $value['plr_value'];
                                }
                            }       
							
                            if (isset($conResult)) {
                                if (isset($conResult['sports_id'])) {
                                    $objParser = Engine_Utilities_GameXmlParser::getInstance();
                                    switch ($conResult['sports_id']) {
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
                            }
                            if (!empty($playerListArray)) {
								//echo "<pre>"; print_r($mylineupPlayerDetails);die;
                                $this->view->playerListArray = $playerListArray;
                                $this->view->session->playerListArray = $playerListArray;
                                $this->view->mylineupDetails = $mylineupPlayerDetails;
                                $this->view->searchValue = $searchValue;
                                $this->view->searchKey = $searchKey;
                                $this->view->mylineup_id = $lineupID;
                                // without team filter
                                $filterPlayerList = $this->filterArray($searchValue, $playerListArray, $searchKey);
								
                                $userLineupDetails = json_decode($data['player_ids']);

                                $lineupIDs = "";
                                foreach ($userLineupDetails as $linup) {
                                    $lineupIDs .= $linup . ',';
                                }
                                $lineupIDs = rtrim($lineupIDs, ',');
                                $this->view->mylineup = $lineupIDs;

                                if ($filterPlayerList) {
                                    $this->view->filterPlayerList = $filterPlayerList;
                                    $this->view->session->filterPlayerList = $filterPlayerList;
                                }
                            }
							//echo "<pre>"; print_r($teamCode); die;
                            $this->view->teamCode = $teamCode;
                            $this->view->sports = $sports;
                        }
                    }
                    $this->view->contestRes = $conResult;
                } else {
                    $this->_redirect('/home');
                }
            } else {
                $this->_redirect('/home');
            }
        }

        $this->view->conid = $data['contest_id'];
        if ($this->getRequest()->getParam('download') == true) {
            $this->_helper->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            header('Content-Disposition: attachment; filename="file.csv"');
            header('Content-type: application/excel');
            readfile('assets/csv/file.csv');
        }
    }

    /**
     * Developer     : Vivek Chaudhari   
     * Date          : 31/07/2014
     * Description   : ajax handler action for getting swapping details in gobal player swap section
     */
    public function swapAjaxHandlerAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $objGamePlayerModel = Application_Model_GamePlayers::getInstance();
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objContestModel = Application_Model_Contests::getInstance();
        $objAbbreviation = Engine_Utilities_Abbreviations::getInstance();
        $objGameStats = Application_Model_GameStats::getInstance();
        $method = $this->getRequest()->getParam('method');

        switch ($method):
            case 'getSwapPlayer' :
                $salary = $this->getRequest()->getParam('salary');
                $posCode = $this->getRequest()->getParam('pcode'); //print_r($posCode);die('test');
                $conid = $this->getRequest()->getParam('cid');  //print_r($conid);die('test');
                $conResult = $objContestModel->getContestsById($conid); // get Active upcomming contest
// echo $conid;  print_r($conResult);die('test');
                if ($conResult) {
                    $game_date = date('Y-m-d', strtotime($conResult['start_time']));

                    switch ($conResult['sports_id']) {
                        case 1:
                            $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                            $abbreviation = $objAbbreviation->getNFLAbbreviations(); // get team Abbreviations

                            break;
                        case 2:

                            $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);

                            $abbreviation = $objAbbreviation->getMLBAbbreviations(); // get team Abbreviations

                            break;
                        case 3:

                            $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                            $abbreviation = $objAbbreviation->getNBAAbbreviations1(); // get team Abbreviations

                            break;
                        case 4:

                            $response = $objGameStats->getGameStats($conResult['sports_id'], $game_date);
                            $abbreviation = $objAbbreviation->getNHLAbbreviations(); // get team Abbreviations

                            break;

                        default:
                            break;
                    }
                    if (isset($abbreviation)) {
                        $contest_res = json_decode($response['game_stat'], true);
                        $contestDate = strtotime(date('Y-m-d', strtotime($conResult['start_time'])));

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
                            if ($posCode == "W") {
                                $pos = array("LW", "RW");
                                $response = array();
                                foreach ($pos as $pval) {
                                    $resVal = $objGamePlayerModel->getDetailsBySalary($pval, $salary, $teamString);
                                    $response = array_merge($resVal, $response);
                                }
                            } else {
                                $response = $objGamePlayerModel->getDetailsBySalary($posCode, $salary, $teamString);
                            }

                            $index = 0;
                            $swapdetails = array();
                            if ($response != '') {
                                foreach ($response as $value) {
                                    $swapdetails[$index]['details'] = json_decode($value['plr_details']);
                                    $swapdetails[$index]['salary'] = $value['plr_value'];
                                    $swapdetails[$index]['fppg'] = $value['fppg'];
                                    $swapdetails[$index]['id'] = $value['plr_id'];
                                    $index++;
                                }
                            } else {
                                $swapdetails = 0;
                            }

                            echo json_encode($swapdetails);
                        }
                    }
                }

                break;
            case 'updateLineup':
                $contestIds = $this->getRequest()->getParam('cid');
                $lineupIds = $this->getRequest()->getParam('lid');
                $playerIn = $this->getRequest()->getParam('playerIn');
                $playerOut = $this->getRequest()->getParam('playerOut');
                $eachLineupId = explode(',', $lineupIds);
                foreach ($eachLineupId as $lineupId):
                    $playerIds = $objLineupModel->getPlayerIdsByLineupId($lineupId);
                    $decode = json_decode($playerIds['player_ids']);
                    $position = json_decode($playerIds['pos_details'], true);
                    $updatedPosition = array();
                    if (isset($playerOut) && isset($playerIn)) {
                        array_walk($position, function($index, $value) use(&$updatedPosition, &$playerOut, &$playerIn) {
                                    if ($playerOut == $value) {
                                        $updatedPosition[$playerIn] = $index;
                                    } else {
                                        $updatedPosition[$value] = $index;
                                    }
                                });
                    }
                    $key = array_search($playerOut, $decode);
                    if ($key !== NULL):
                        $replace = array($key => $playerIn);
                        $updatedLineup = array_replace($decode, $replace);
                        $data = array('player_ids' => json_encode($updatedLineup), 'pos_details' => json_encode($updatedPosition));
                        $ok = $objLineupModel->updateLineupDetails($data, $lineupId);
                    endif;
                endforeach;

                $contest = explode(",", $contestIds);
                foreach ($contest as $value):
                    $contestDetails = $objContestModel->getContestsDetailsById($value);
                    $contestName[] = $contestDetails['contest_name'];
                endforeach;
                echo json_encode($contestName);
                break;
        endswitch;
    }

    /**
     * Developer     : Vivek Chaudhari   
     * Date          : 04/09/2014
     * Description   : show depth chart of teams in draft team page(ajax call)
     */
    public function depthChartAction() {
        $objGamePlayersModel = Application_Model_GamePlayers::getInstance();
        $this->_helper->layout()->disableLayout();
        $teamArray = $this->getRequest()->getPost('searchTeam');
		//print_r($teamArray); die;
        $sportId = $this->getRequest()->getPost('sportId');
        $detail = array();
        $posCode = array();
        if (!empty($teamArray)) {
            foreach ($teamArray as $key => $team) {
                $response = $objGamePlayersModel->getPlayersByGameTeam($sportId, $team);
                if (!empty($response)) {
                    foreach ($response as $tkey => $tvalue) {
                        $decode = json_decode($tvalue['plr_details']);
                        array_push($posCode, $decode->position);
                    } $posCode = array_unique($posCode);
                    foreach ($posCode as $pkey => $pvalue) {
                        $index = 0;
                        foreach ($response as $dkey => $dvalue) {
                            $decode = json_decode($dvalue['plr_details'], TRUE);
                            if ($pvalue == $decode['position']) {
                                $detail[$key][$pkey][$index]['pos'] = $pvalue;
                                $detail[$key][$pkey][$index]['details'] = $decode;
                                $detail[$key][$pkey][$index]['plr_value'] = $dvalue['plr_value'];
                                $index++;
                            }
                        }
                    }
                }
            }
        }
        if (!empty($detail)) {
            $this->view->detail = $detail;
            $this->view->teams = $teamArray;
        }
    }

    /**
     * Developer    : Vivek chaudhari
     * Date         : 25/08/2014
     * Description  : update ticket user for provided ticket id
     * @param       :  <int>ticket Id.
     * @return      : 
     */
    function ticketManager($ticketId, $userId) {
//           $userId = $this->view->session->storage->user_id; 
        $objTicketModel = Application_Model_TicketSystem::getInstance();
        $objUserAccModel = Application_Model_UserAccount::getInstance();

        $accData = $objUserAccModel->getAccountByUserId($userId);
        $data = $objTicketModel->getTicketDetailsById($ticketId);

        if (isset($accData['available_tickets'])) {
            $usrTickets = array();
            if ($accData['available_tickets'] != "") {
                $usrTickets = json_decode($accData['available_tickets'], true);
                $usrTickets[] = $ticketId;
            } else {
                $usrTickets[] = $ticketId;
            }
            $ticketData['available_tickets'] = json_encode($usrTickets, true);
            $objUserAccModel->updateUserAccount($userId, $ticketData);
        }
        $ticketUsers = array();
        if (!empty($data['ticket_for'])) {

            $ticketUsers = json_decode($data['ticket_for'], true);
        } else {

            $user[] = $userId;
            $edit = json_encode($user, JSON_FORCE_OBJECT);
            $ok = $objTicketModel->updateTicketUsers($edit, $ticketId);
        }

        if ((is_array($ticketUsers)) && (!empty($ticketUsers))) {
            array_push($ticketUsers, $userId);
            $edit = json_encode($ticketUsers, JSON_FORCE_OBJECT);
            $ok = $objTicketModel->updateTicketUsers($edit, $ticketId);
        }
    }

    /**
     * Developer    : Vinay
     * Date         : 26/09/2014
     * Description  : Arrange 
     * @param       : array
     * @return      : array
     */
    function arrangeMLBLineUp($MLBLineup) {
        $posP = array();
        $posC = array();
        $pos1B = array();
        $pos2B = array();
        $pos3B = array();
        $posSS = array();
        $posOF = array();

        if (empty($posP)) {
            foreach ($MLBLineup as $lineup) {
                if ($lineup['pos_code'] == 'P') {
                    $posP[] = $lineup;
                }
            }
        }

        if (empty($posC)) {
            foreach ($MLBLineup as $lineup) {
                if ($lineup['pos_code'] == 'C') {
                    $posC[] = $lineup;
                }
            }
        }
        if (empty($pos1B)) {
            foreach ($MLBLineup as $lineup) {
                if ($lineup['pos_code'] == '1B') {
                    $pos1B[] = $lineup;
                }
            }
        }
        if (empty($pos2B)) {
            foreach ($MLBLineup as $lineup) {
                if ($lineup['pos_code'] == '2B') {
                    $pos2B[] = $lineup;
                }
            }
        }
        if (empty($pos3B)) {
            foreach ($MLBLineup as $lineup) {
                if ($lineup['pos_code'] == '3B') {
                    $pos3B[] = $lineup;
                }
            }
        }
        if (empty($posSS)) {
            foreach ($MLBLineup as $lineup) {
                if ($lineup['pos_code'] == 'SS') {
                    $posSS[] = $lineup;
                }
            }
        }
        if (empty($posOF)) {
            foreach ($MLBLineup as $lineup) {
                if ($lineup['pos_code'] == 'OF') {
                    $posOF[] = $lineup;
                }
            }
        }


        return array_merge($posP, $posC, $pos1B, $pos2B, $pos3B, $posSS, $posOF);
    }

    function specialPlayer($sportId, $searchValue, $searchKey, $teamfilter = null,$filterValue=null) {
        $playerListArray = $this->view->session->playerListArray;
		
        switch ($sportId):
            case 1 :
                if ($searchValue === "FLEX") {
                    $filterPlayerList = array();
                    $searchValueArray = array("WR", "TE","RB","FLEX");
					
                    foreach ($searchValueArray as $searchValue) {
                        if ($teamfilter != null || $teamfilter[0] != "All Games") { 
                            $searchdata = $this->filterTeamArray($searchValue, $playerListArray, $searchKey, $teamfilter);
                        } 
						else {
                            $searchdata = $this->filterArray($searchValue, $playerListArray, $searchKey);
                        }

                        $filterPlayerList = array_merge($filterPlayerList, $searchdata);
                    }
                    if ($filterPlayerList) {
                        return $filterPlayerList;
                    }
                }
                break;
            case 2 :
                // no such condition for MLB sport players
                break;
            case 3 :
                if ($searchValue === "G") {
                    $filterPlayerList = array();
                    $searchValueArray = array("SG", "PG");
                    foreach ($searchValueArray as $searchValue) {
                        if ($teamfilter != null || $teamfilter[0] != "All Games") {
                            $searchdata = $this->filterTeamArray($searchValue, $playerListArray, $searchKey, $teamfilter);
                        } else {
                            $searchdata = $this->filterArray($searchValue, $playerListArray, $searchKey);
                        }
                        $filterPlayerList = array_merge($filterPlayerList, $searchdata);
                    }
                    if ($filterPlayerList) {
                        return $filterPlayerList;
                    }
                } else if ($searchValue === "F") {
                    $filterPlayerList = array();
                    $searchValueArray = array("SF", "PF");
                    foreach ($searchValueArray as $searchValue) {
                        if ($teamfilter != null || $teamfilter[0] != "All Games") {
                            $searchdata = $this->filterTeamArray($searchValue, $playerListArray, $searchKey, $teamfilter);
                        } else {
                            $searchdata = $this->filterArray($searchValue, $playerListArray, $searchKey);
                        }
                        $filterPlayerList = array_merge($filterPlayerList, $searchdata);
                    }
                    if ($filterPlayerList) {
                        return $filterPlayerList;
                    }
                } else if ($searchValue === "UTIL") {
                    $filterPlayerList = array();
                    $searchValueArray = array("C","SF", "PF", "SG", "PG");
                    foreach ($searchValueArray as $searchValue) {
                        if ($teamfilter != null || $teamfilter[0] != "All Games") {
                            $searchdata = $this->filterTeamArray($searchValue, $playerListArray, $searchKey, $teamfilter);
                        } else {
                            $searchdata = $this->filterArray($searchValue, $playerListArray, $searchKey);
                        }
                        $filterPlayerList = array_merge($filterPlayerList, $searchdata);
                    }
                    if ($filterPlayerList) {
                        return $filterPlayerList;
                    }
                }

                break;
            case 4 :
                if ($searchValue === "UTIL") {
                    $filterPlayerList = array();
                    $searchValueArray = array("C", "W", "D");
                    foreach ($searchValueArray as $searchValue) {
                        if ($teamfilter != null || $teamfilter[0] != "All Games") {
                            $searchdata = $this->filterTeamArray($searchValue, $playerListArray, $searchKey, $teamfilter);
                        } else {
                            $searchdata = $this->filterArray($searchValue, $playerListArray, $searchKey);
                        }
                        $filterPlayerList = array_merge($filterPlayerList, $searchdata);
                    }
                    if ($filterPlayerList) {
                        return $filterPlayerList;
                    }
                }
                break;
        endswitch;
    }

}

