<?php

/**
 * HomeController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class Home_HomeController extends Zend_Controller_Action {

   
    public function init(){
		$res_data = $this->dfs_curl();
		if ($this->view->auth->hasIdentity()) {    
			$this->view->session->storage->dfsBalance = $res_data;
        }else{
			$this->view->dfsBalance = $res_data;
		}	
	}
	
	public function indexAction(){
		//$this->_helper->layout->disableLayout();
		$objPromotions = Admin_Model_Promotions::getInstance();
		$objUserAccount = Application_Model_UserAccount::getInstance();
		$activepromos = $objPromotions->getActivePromotions();
		//echo "<pre>"; print_r($activepromos); die;
        if($activepromos) {
			$this->view->session->activePromos = $activepromos;		  
        }
		if(!empty($this->view->session->storage->user_id)){
			$userId = $this->view->session->storage->user_id;
			$userAmount = $objUserAccount->getUserBalance($userId);
			$this->view->session->storage->userBalance = $userAmount['balance_amt'];
			$this->view->session->storage->userBonus = $userAmount['bonus_amt'];
		}	
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
	
	public function homeAction() {         
        $objSportsModel = Application_Model_Sports::getInstance();
        $objContestsModel = Application_Model_Contests::getInstance();
        $objFunctions = Engine_Utilities_Functions::getInstance();
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objUserLineupModel = Application_Model_UserLineup::getInstance();
				
        $sportsDetails = $objSportsModel->getSports();
        $this->view->sport = $sportsDetails;
        
		$objPromotions = Admin_Model_Promotions::getInstance();
			
        if(empty($this->view->session->activePromos)) {
			$activepromos = $objPromotions->getActivePromotions();
			$this->view->session->activePromos = $activepromos;		  
        }
		
        $contests = $objContestsModel->getActiveContests();
        $objContestTypeModel = Application_Model_ContestsType::getInstance();
        $contestTypeDetails = $objContestTypeModel->getContestTypeDetails();
        $this->view->contype = $contestTypeDetails;
        if(!empty($contests)){
			foreach ($contests as $conKey => $conVal) {
				$contests[$conKey]['entry_type'] = 1; //user not entered into contest
				$entryFull = false;
				if($conVal['play_limit'] == $conVal['total_entry']){
					$entryFull = true;
					$contests[$conKey]['entry_type'] = 5; 
				}
			}
        }
		
		// offer details
		
		$objOffersModel = Application_Model_Offers::getInstance();
		$offers = $objOffersModel->getActiveOffer();
        if (!empty($offers)) {
            $this->view->activeOffers = $offers;
        }
		//echo "<pre>"; print_r($offers); die;
        //add the condition when user joined event
		
        if (isset($this->view->session->storage->user_id) ){
            $userId = $this->view->session->storage->user_id;
            $contestUserLineup = $objLineupModel->getMyLineupDetails($userId);
            if (!empty($contests)) {
                foreach ($contests as $conKey => $conVal) {
                    $contests[$conKey]['entry_count'] = 0;
                    $contests[$conKey]['entry_type'] = 1; //user not entered into contest
                    $entryFull = false;
                    if($conVal['play_limit'] == $conVal['total_entry']){
                        $entryFull = true;
                        $contests[$conKey]['entry_type'] = 5; 
                    }
                    $entryCount = 0;
                    foreach ($contestUserLineup as $ukey => $uval) {
                        if ($conVal['contest_id'] === $uval['contest_id']) {
                            $contests[$conKey]['lineup_id'] = $uval['lineup_id'];
                            $entryCount = $contests[$conKey]['entry_count'] + 1;
                            $contests[$conKey]['entry_count'] = $entryCount;
                            $contests[$conKey]['entry_type'] = 2; //user entered into contest,ask for edit
                        }
                    }
                    if(!$entryFull){
                        if($conVal['challenge_limit'] !=0){
                            $contests[$conKey]['entry_type'] = 3; //allow user to enter the contest, bcoz its multientry contest
                            if($conVal['challenge_limit'] == $entryCount){
                                $contests[$conKey]['entry_type'] = 4; //multientry for this user is done, ont allow to enter, send to mylineup page
                            }
                        }
                    }
                    
                }
            }
        }
        
        if (isset($contests) && !empty($contests)) {
            $sortedData = array();
            $i = 0;
            if ($contests) {
                foreach ($contests as $okey => $contestDetails) {
                    if (date('Y-m-d') <= date('Y-m-d', strtotime($contestDetails['start_time']))) {

                        if ($i == 0) {
                            $sortedData[$i] = $contestDetails['start_time'];
                            $i++;
                        } else if ($sortedData[$i - 1] != $contestDetails['start_time']) {
                            $sortedData[$i] = $contestDetails['start_time'];
                            $i++;
                        }
                    }
                }
            }
            if ($i > 0) {
                sort($sortedData);
                if ($contests) {
                    $this->view->mainLiveData = strtotime($sortedData[0]);
                }
            }

            $currDay = time();
            $nxtDay = strtotime('+1 day');
			if(!empty($sortedData[0])){
				$nxtStartTime = strtotime($sortedData[0]);
			} else{
				$nxtStartTime = '';
			}
            
            foreach ($contests as $key => $contest) {
                $evtDay = strtotime($contest['start_time']);
                if ($nxtStartTime == $evtDay ) {
                    //next day start
                    $contests[$key]['todayStart'] = 3;
                } else if($evtDay<=$nxtDay) {
                    $contests[$key]['todayStart'] = 2;
                }else{
                    $contests[$key]['todayStart'] = 1;
                }

                if ($contest['play_limit'] != 0) {
                    if ($contest['total_entry'] == $contest['play_limit']) {
                        $contests[$key]['is_vacant'] = 0;
                    } else if ($contest['total_entry'] <= $contest['play_limit']) {
                        $contests[$key]['is_vacant'] = 1;
                    }
                }
            }
            $this->view->allcontest = $contests;

            $gauranteed = $objFunctions->filterArray(1, $contests, 'con_type_id');
            $qualifiers = $objFunctions->filterArray(2, $contests, 'con_type_id');
            $headTohead = $objFunctions->filterArray(3, $contests, 'con_type_id');
            $fiftyfifty = $objFunctions->filterArray(4, $contests, 'con_type_id');
            $leagues = $objFunctions->filterArray(5, $contests, 'con_type_id');
            $multipliers = $objFunctions->filterArray(6, $contests, 'con_type_id');
            $beginers = $objFunctions->filterArray(7, $contests, 'con_type_id');
            $this->view->grtd = $gauranteed;
            $this->view->qual = $qualifiers;
            $this->view->h2h = $headTohead;
            $this->view->fft = $fiftyfifty;
            $this->view->leag = $leagues;
            $this->view->mult = $multipliers;
            $this->view->begi = $beginers;
        }
        
        
        /* $url = 'http://mlb.mlb.com/partnerxml/gen/news/rss/mlb.xml';
        $client = new Zend_Http_Client($url);
        $response = $client->request();
        $data = simplexml_load_string($response->getBody());
        $parseData = array();
        $eachData = array();
        foreach ($data->channel->item as $value):
            $eachData['title'] = (string) $value->title;
            $eachData['link'] = (string) $value->link;
            $eachData['pubDate'] = (string) $value->pubDate;
            $eachData['guid'] = (string) $value->guid;
            $eachData['description'] = (string) $value->description;
            array_push($parseData, $eachData);
        endforeach;
        if ($parseData):
            $layout = Zend_Layout::getMvcInstance();
            $view = $layout->getView();
            $view->newsdata = $parseData;
        endif; */
        $leaderData = $this->leaderboard();
        if ($leaderData) {
            $this->view->leadedata = $leaderData;
        }
        if(isset($this->view->session->storage->user_id)){
            $objModelNotify = Application_Model_Notification::getInstance();
            $objUserAccount = Application_Model_UserAccount::getInstance();
            $userId = $this->view->session->storage->user_id;
            $notification = $objModelNotify->getNotification($userId);
            if (!empty($notification)) {
                $notecount = count($notification);
                $this->view->session->storage->notecount = $notecount;
                $notecount = $this->view->session->storage->notecount;
            }
            
            $userAmount = $objUserAccount->getUserBalance($userId);
            $this->view->session->storage->userBalance = $userAmount['balance_amt'];
            $this->view->session->storage->userBonus = $userAmount['bonus_amt'];
            
        }
    }

    

    /**
     * Developer     : Vivek Chaudhari   
     * Date          : 20/06/2014
     * Description   : give contest details and show prize payouts in correct format in contest details popup
     * @param       : <int> contest id
     */
    public function contestDetailsAction() {

        $this->_helper->layout()->disableLayout();
        $cid = $this->getRequest()->getparam('cid');
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
        $prizeDetails = array();
        if (isset($details['prizes']) && $details['prizes'] != 6) {
            if ($details['prizes'] == 1) {
                $prizeDetails[0]['from'] = "1st";
                $prizeDetails[0]['prize'] = "$" . $details['prize_pool'];
            }
            if ($details['prizes'] == 2) {
                $prizeDetails[0]['from'] = "1st";
                $prizeDetails[0]['to'] = "2nd";
                $prizeDetails[0]['prize'] = "$" . $details['prize_pool'];
            }
            if ($details['prizes'] == 3) {
                $prizeDetails[0]['from'] = "1st";
                $prizeDetails[0]['to'] = "3rd";
                $prizeDetails[0]['prize'] = "$" . $details['prize_pool'];
            }
            if ($details['prizes'] == 4) {
                $prizeDetails[0]['from'] = "1st";
                $prizeDetails[0]['to'] = "5th";
                $prizeDetails[0]['prize'] = "$" . $details['prize_pool'];
            }
            if ($details['prizes'] == 5) {
                $prizeDetails[0]['from'] = "1st";
                $upto = round($details['play_limit'] / 2, 1);
                if ($upto > 0) {
                    $to = substr(($upto), -1);
                    if ($to == 1) {
                        $prizeDetails[0]['to'] = ($upto) . "st";
                    } else if ($to == 2) {
                        $prizeDetails[0]['to'] = ($upto) . "nd";
                    } else if ($to == 3) {
                        $prizeDetails[0]['to'] = ($upto) . "rd";
                    } else if ($to >= 4) {
                        $prizeDetails[0]['to'] = ($upto) . "th";
                    } else if ($to == 0) {
                        $prizeDetails[0]['to'] = ($upto) . "th";
                    }
                }
                $prizeDetails[0]['prize'] = "$" . $details['prize_pool'];
            }
        } 
        if (isset($details['prize_payouts']) && !empty($details['prize_payouts'])) {
            $payouts = json_decode($details['prize_payouts']);

            if (!empty($payouts) && is_array($payouts)) {
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
                        $prizeDetails[$key]['prize'] = "$" . $value->amount;
                    } else if (($value->type) == 1) {
                        $ticketId = $value->ticket_id;
                        $data = $objTicketModel->getTicketDetailsById($ticketId);
                        $prizeDetails[$key]['prize'] = $data['description'] . "Ticket. ($" . $data['bonus_amt'] . ")";
                    }
                }
            }
        }
        if (!empty($prizeDetails)) {
            $this->view->prize_details = $prizeDetails;
           
        }
        if ($details):
            $this->view->details = $details;
       
        endif;

        if ($username):
            $this->view->username = $username;
        endif;
    }

    /*
     * Name        :   Abhinish Kumar Singh
     * Date        :   04/07/2014
     * Description :   This action is used to fetch a response containing data 
     *                 about total_entries and contest_id.
     */

    public function filterSportsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        if ($this->getRequest()->isPost()) {
            $method = $this->getRequest()->getParam('method');

            switch ($method) {
                case 'getentrY':
                    $objContestsModelEntry = Application_Model_Contests::getInstance();
                    $response = $objContestsModelEntry->getEntries();
                    $data = array();
                    $i = 0;
                    foreach ($response as $key => $value) {
                        @$data[$i]['contest_id'] = $value['contest_id'];
                        @$data[$i]['total_entry'] = $value['total_entry'];
                        $i++;
                    }
                    if (!empty($data)) {
                        echo json_encode($data);
                    }
                    break;
                case 'validateUname':
                    $userName = $this->getRequest()->getParam('uName');
                    $objUsers = Application_Model_Users::getInstance();
                    $objUsers->validateUserName($userName);

                    break;
                default:
                    break;
            }
        }
    }

    /*
     * Name        :   Abhinish Kumar Singh
     * Date        :   16/07/2014
     * Description :   This action is used to display contest promotions
     */

    public function promoAction() {
        $promoid = $this->getRequest()->getparam('promoid');
        if ($promoid) {
            $this->view->promoid = $promoid;
        }
    }


    //vivek
    public function contestDetailsAjaxAction(){
        $this->_helper->layout()->disableLayout();
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objContestModel = Application_Model_Contests::getInstance();
        $objUserLineupModel = Application_Model_UserLineup::getInstance();
        $objGamaPlayerModel = Application_Model_GamePlayers::getInstance();
         if ($this->getRequest()->isPost()) {
            $lineupID = $this->getRequest()->getPost('lid');
            $this->view->lineupId = $lineupID;
            $userId = $this->view->session->storage->user_id;
            $lineupDetails = $objLineupModel->getLineupByID($lineupID);
            $joinContests  = $objUserLineupModel->getAllContestByLineupId($lineupID);
            $playerIds = json_decode($lineupDetails['player_ids'],true);
            $playerDetails = $objGamaPlayerModel->getPlayerList($playerIds);
             $i = 0;
            foreach ($playerDetails as $plrdetails) {
                $playerDetails = (array) json_decode($plrdetails['plr_details']);
                $plrData[$i]['name'] = $playerDetails['name'];
                $plrData[$i]['position'] = $playerDetails['pos_code'];
                $i++;
            }
            $this->view->playerIds = $plrData;
            if(isset($joinContests) && !empty($joinContests)){
                $this->view->joincontest = $joinContests;
            }
            $this->view->remTime = strtotime($lineupDetails['start_time']);
            $contestData = $objContestModel->getActiveSportContestByDate($lineupDetails['sports_id'], date('Y-m-d', strtotime($lineupDetails['start_time'])));
            
            if(!empty($contestData)){
                $contestList = array();
                foreach($contestData as $contest){
                    $contestId = $contest['contest_id'];
                   
                   if($contest['total_entry'] <= $contest['play_limit']){ // check entries available
                       $entries = $objUserLineupModel->getUserContestEntry($userId,$contestId);
                       if(!empty($entries)){
                           $entryCount = count($entries);
                       }else{
                           array_push($contestList, $contest);
                       }
                       if($contest['challenge_limit'] != 0){
                           if(isset($entryCount)){
                               if($contest['challenge_limit'] < $entryCount){
                                   array_push($contestList, $contest);
                               }
                           }else{
                               array_push($contestList, $contest);
                           }
                       }
                   }
                   
                }
            }
            if(isset($contestList) && !empty($contestList)){
                $this->view->contest = $contestList;
            }
         }
    }
    
    public function contestDetailsUndraftAction() {

        $this->_helper->layout()->disableLayout();
        $cid = $this->getRequest()->getparam('cid');
        $this->view->contest_id = $cid;
        $userId = $this->view->session->storage->user_id;
        if (!$userId) {
            $this->_helper->redirector('login');
        }
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
        $prizeDetails = array();
        if (isset($details['prizes']) && $details['prizes'] != 6) {
            if ($details['prizes'] == 1) {
                $prizeDetails[0]['from'] = "1st";
                $prizeDetails[0]['prize'] = "$" . $details['prize_pool'];
            }
            if ($details['prizes'] == 2) {
                $prizeDetails[0]['from'] = "1st";
                $prizeDetails[0]['to'] = "2nd";
                $prizeDetails[0]['prize'] = "$" . $details['prize_pool'];
            }
            if ($details['prizes'] == 3) {
                $prizeDetails[0]['from'] = "1st";
                $prizeDetails[0]['to'] = "3rd";
                $prizeDetails[0]['prize'] = "$" . $details['prize_pool'];
            }
            if ($details['prizes'] == 4) {
                $prizeDetails[0]['from'] = "1st";
                $prizeDetails[0]['to'] = "5th";
                $prizeDetails[0]['prize'] = "$" . $details['prize_pool'];
            }
            if ($details['prizes'] == 5) {
                $prizeDetails[0]['from'] = "1st";
                $upto = round($details['play_limit'] / 2, 1);
                if ($upto > 0) {
                    $to = substr(($upto), -1);
                    if ($to == 1) {
                        $prizeDetails[0]['to'] = ($upto) . "st";
                    } else if ($to == 2) {
                        $prizeDetails[0]['to'] = ($upto) . "nd";
                    } else if ($to == 3) {
                        $prizeDetails[0]['to'] = ($upto) . "rd";
                    } else if ($to >= 4) {
                        $prizeDetails[0]['to'] = ($upto) . "th";
                    } else if ($to == 0) {
                        $prizeDetails[0]['to'] = ($upto) . "th";
                    }
                }
                $prizeDetails[0]['prize'] = "$" . $details['prize_pool'];
            }
        } 
        if (isset($details['prize_payouts']) && !empty($details['prize_payouts'])) {
            $payouts = json_decode($details['prize_payouts']);

            if (!empty($payouts) && is_array($payouts)) {
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
                        $prizeDetails[$key]['prize'] = "$" . $value->amount;
                    } else if (($value->type) == 1) {
                        $ticketId = $value->ticket_id;
                        $data = $objTicketModel->getTicketDetailsById($ticketId);
                        $prizeDetails[$key]['prize'] = $data['description'] . "Ticket. ($" . $data['bonus_amt'] . ")";
                    }
                }
            }
        }
        if (!empty($prizeDetails)) {
            $this->view->prize_details = $prizeDetails;
        }
        if ($details):
            $this->view->details = $details;
        endif;

        if ($username):
            $this->view->username = $username;
        endif;
    }

    function rearrangePlayerPics($playersDetails) {
        foreach ($playersDetails as $key => $player) {
            if (!file_exists($player['player_img_link'])) {
                //echo     $player['player_img_link'];die;

                $playersDetails[$key]['player_img_link'] = $this->getDefaultPlayerImage($player['sports_id']);
            }
        }

        return $playersDetails;
    }

    function getDefaultPlayerImage($sportsId) {
        $imageName = "";

        switch ($sportsId) {
            case 1: {
                    $imageName = "images/nfl_default.png";
                    break;
                }
            case 2: {
                    $imageName = "images/mlb_default.png";
                    break;
                }
            case 3: {
                    $imageName = "images/nba_default.png";
                    break;
                }
            case 4: {
                    $imageName = "images/nhl_default.png";
                    break;
                }
        }
        return $imageName;
    }

    public function leaderboard() {
        $objTransactionModel = Application_Model_UserTransactions::getInstance();
        $objUserModel = Application_Model_Users::getInstance();
        $transactionData = $objTransactionModel->winningTransactions();
        $userTransaction = array();
        $userData = array();
        if (!empty($transactionData)) {
            foreach ($transactionData as $tval) {
                if (isset($userTransaction[$tval['user_id']])) {
                    $userTransaction[$tval['user_id']]['amount'] = $userTransaction[$tval['user_id']]['amount'] + $tval['transaction_amount'];
                } else {
                    $userTransaction[$tval['user_id']]['amount'] = $tval['transaction_amount'];
                    $userTransaction[$tval['user_id']]['user_id'] = $tval['user_id'];
                }
            }

            $value = array();
            foreach ($userTransaction as $key => $row) {
                $value[$key] = $row['amount'];
            }
            array_multisort($value, SORT_DESC, $userTransaction); //sort according to amount
            $userTransaction = array_slice($userTransaction, 0, 10); //limit top ten users
            $index = 0;
            foreach ($userTransaction as $utVal) {
                $response = $objUserModel->getUserDetailsByUserId($utVal['user_id']);
                $userData[$index]['name'] = $response['user_name'];
                $userData[$index]['amount'] = $utVal['amount'];
                $userData[$index]['user_id'] = $utVal['user_id'];
                $index++;
            }
        }
        if ($userData) {
            return $userData;
        }
    }

}
