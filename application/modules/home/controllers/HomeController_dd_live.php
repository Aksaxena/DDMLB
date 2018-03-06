<?php

/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';
//require_once '../vendor/autoload.php';

class Home_HomeController extends Zend_Controller_Action {

    public function init() {
        
    }
    /* public function emailAction() {
		 $config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$postmark_config = $config->getOption('postmark');	
		//print_r($postmark_config); die;
			try{
				$client = new Postmark\PostmarkClient($postmark_config['key']);
				$result = $client->sendEmailWithTemplate(
					$postmark_config['email'],
					"pkdss007@gmail.com",
					$postmark_config['Welcome_template'], 
					[
						"site_name" => $postmark_config['site_name'],
						"username" => $username,
					] 
				);
			} catch (Exception $e){
			  echo "<pre>"; print_r($e); die;
			}
			echo "<pre>"; print_r($result); die;
    } */
    public function homeAction() {         
        $objSportsModel = Application_Model_Sports::getInstance();
        $objContestsModel = Application_Model_Contests::getInstance();
        $objFunctions = Engine_Utilities_Functions::getInstance();
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objUserLineupModel = Application_Model_UserLineup::getInstance();
        $objPromotions = Admin_Model_Promotions::getInstance();
        $sportsDetails = $objSportsModel->getSports();
        $this->view->sport = $sportsDetails;
        $activepromos = $objPromotions->getActivePromotions();
        if($activepromos) {
		  $this->view->session->activePromos = $activepromos;
		  $this->view->activePromos  = $activepromos;
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
            $nxtStartTime = strtotime($sortedData[0]);
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
        
        
        $url = 'http://mlb.mlb.com/partnerxml/gen/news/rss/mlb.xml';
        $client = new Zend_Http_Client($url);
        $response = $client->request();
        $data = simplexml_load_string($response->getBody()); //echo "<pre>"; print_r($data); echo "</pre>"; die;
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
        endif;
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

    public function home() {

//$this->leaderboard();die('test');
        $objEmaillog = Application_Model_Emaillog::getInstance();
        $objParser = Engine_Utilities_GameXmlParser::getInstance();
        $mailer = Engine_Mailer_Mailer::getInstance();
        //$response = $objParser->getGameLists('NFL');
        //$response = $objParser->getGameLists('MLB');
        $objModelNotify = Application_Model_Notification::getInstance();
        $objContestTypeModel = Application_Model_ContestsType::getInstance();
        $objContestsModel = Application_Model_Contests::getInstance();
        $objSportsModel = Application_Model_Sports::getInstance();
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objUserAccount = Application_Model_UserAccount::getInstance();
        $objUsers = Application_Model_Users::getInstance();
        $objOfferModel = Application_Model_Offers::getInstance();
        $objPromotions = Admin_Model_Promotions::getInstance();
        $objTicketModel = Application_Model_TicketSystem::getInstance();
        $objGamePlayers = Application_Model_GamePlayers::getInstance();
        $xid = 0;
        $contests = $objContestsModel->getSportsById($xid);
        $contestTypeDetails = $objContestTypeModel->getContestTypeDetails();
        $sportsDetails = $objSportsModel->getSports();
        $activeOffers = $objOfferModel->getActiveOffer();
        $activepromos = $objPromotions->getActivePromotions();
        $i = 0;


        $sortedData = array();
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
                $this->view->mainLiveData = $sortedData[0];
            }
        }

        $leaderData = $this->leaderboard();
        if ($leaderData) {
            $this->view->leadedata = $leaderData;
        }
        /**
         * Developer    :- Vivek Chaudhari
         * Date         :- 20/06/2014
         * Description  :- get balance amount for session user and store in session as userBalance and Ticket
         */
        if (isset($this->view->session->storage->user_id)) {
            $userId = $this->view->session->storage->user_id;
            $userAmount = $objUserAccount->getUserBalance($userId);
            $userTicket = $objUserAccount->getUserTickets($userId);
            $totalTicket = "";
            $contestTicket = array();
//            $userTicket = $objTicketModel->getUserTickets($userId);
            if (isset($userTicket)) {
                $decode = json_decode($userTicket['available_tickets'], true);
                $totalTicket = count($decode);
                $contestTicket = $decode;
//                foreach ($decode as $ticket) {
//                    $tickets = json_decode($ticket['ticket_for'], true);
//                    $valCount = array_count_values($tickets);
//                    if ($valCount[$userId]) {
//                        $totalTicket += $valCount[$userId];
//                        $contestTicket[$cout]['id'] = $ticket['ticket_id'];
//                        $contestTicket[$cout]['total'] = $valCount[$userId];
//                        $cout++;
//                    }
//                }
            }

            if ($contestTicket) {
                $this->view->session->storage->userTickets = $totalTicket;
                $this->view->session->storage->userContestTickets = $contestTicket;
            } else {
                $this->view->session->storage->userTickets = 0;
                $this->view->session->storage->userContestTickets = "";
            }

            $notification = $objModelNotify->getNotification($userId);
            if (!empty($notification)) {
                $notecount = count($notification);
                $this->view->session->storage->notecount = $notecount;
                $notecount = $this->view->session->storage->notecount;
            }


            $userDetails = $objUsers->getUserdetailsByUserId($userId);
            $this->view->session->storage->userBalance = $userAmount['balance_amt'];
            $this->view->session->storage->userBonus = $userAmount['bonus_amt'];
            $this->view->session->storage->userName = $userDetails['user_name'];
            $contestUserLineup = $objLineupModel->getMyLineupDetails($userId);

            if (!empty($contests)) {
                foreach ($contests as $conKey => $conVal) {
                    $contests[$conKey]['entry_count'] = 0;
                    foreach ($contestUserLineup as $ukey => $uval) {
                        if ($conVal['contest_id'] === $uval['contest_id']) {
                            $contests[$conKey]['lineup_id'] = $uval['lineup_id'];
                            $contests[$conKey]['entry_count'] = $contests[$conKey]['entry_count'] + 1;
                        }
                    }
                }
            }
        }
        if ($contests) {
            $count = count($contests);

            $index = rand(0, $count - 1);
            $this->view->contestPromotion = $contests[$index];
        }
//-------------------------------------------------------------------------------------------------// 

		if ($activepromos) {
            $this->view->session->activePromos = $activepromos;
        }
        if ($activeOffers) {
            $this->view->offers = $activeOffers;
        }

        if ($sportsDetails) {
            $this->view->sports = $sportsDetails;
        }

        if ($contestTypeDetails) {
            $this->view->contestType = $contestTypeDetails;
        }
        if ($contests) {
            $count = count($contests);
            $this->view->count = $count;
            $this->view->contest = $contests;
        }

        if ($this->getRequest()->isPost()) {
            $template_name = 'invite-frnd';
            $email = $this->getRequest()->getParam('email');
            $username = 'Sender';
            $subject = 'Invite Mail';
            // $result =$mailer->sendtemplate($template_name,$email,$username,$subject);
        }

        /*         * name: Sarika nayak
         * date: 22/09/2014
         * description: parse rss feeds to show in news slider in homepage  
         */
        $url = 'http://mlb.mlb.com/partnerxml/gen/news/rss/mlb.xml';
        $client = new Zend_Http_Client($url);
        $response = $client->request();
        $data = simplexml_load_string($response->getBody()); //echo "<pre>"; print_r($data); echo "</pre>"; die;
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
//                    print_r($parseData) ;
//                    die;
        if ($parseData):
//         
            $layout = Zend_Layout::getMvcInstance();
            $view = $layout->getView();
            $view->newsdata = $parseData;
        endif;

        foreach ($sportsDetails as $sports) {
            $FPTSplayersArray[] = $objGamePlayers->getPlayerDetailsByFPTS($sports['sports_id']);
        }
        $FPTSplayersArray = array_reduce($FPTSplayersArray, 'array_merge', array());

        $FPTSplayers = $this->rearrangePlayerPics($FPTSplayersArray);
        $FPTSplayersArray = array();
        $i = 0;
        $j = 0;
        foreach ($FPTSplayers as $pkey => $players) {
            if ($j <= 3) {
                $FPTSplayersArray[$i][$j] = $players;
                $j++;
            } else {
                $j = 0;
                $i++;
                $FPTSplayersArray[$i][$j] = $players;
                $j++;
            }
        }
//        echo "<pre>"; print_r($FPTSplayers); echo "</pre>";
//        echo "<pre>"; print_r($FPTSplayersArray); echo "</pre>";die;
        $this->view->FPTSplayers = $FPTSplayersArray;
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
        } //echo "<pre>"; print_r($prizeDetails); echo "</pre>"; die;
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
            //print_r($prizeDetails);die;
        }
        if ($details):
            $this->view->details = $details;
        // print_r($details);die;
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
        } //echo "<pre>"; print_r($prizeDetails); echo "</pre>"; die;
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
