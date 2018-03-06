<?php

/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class Admin_ContestController extends Zend_Controller_Action {

    public function init() {
    
    }

    public function contestDetailsAction() {
        $objContests = Admin_Model_Contests::getInstance();
        if (!empty($_POST)) {
            $type = $_POST['type'];

            if (isset($type) && $type != -1) {
                $contestDetails = $objContests->getContestsByType($type);
            } else {
                $contestDetails = $objContests->getContests();
            }
        } else {

            $contestDetails = $objContests->getContests();
        }
//        print "Post Data:\n<pre>";print_r($contestDetails);print "</pre>";die();
        if ($contestDetails) :
            $this->view->contest = $contestDetails;
        endif;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 13/03/2014
     * Description : Get Match details
     */
    public function matchDetailsAction() {

        $objSportsModel = Admin_Model_Sports::getInstance();
        $sportsDetails = $objSportsModel->getSportsDetails();
        if ($sportsDetails) :
            $this->view->sports = $sportsDetails;
        endif;
    }


    public function newContestAction() {

        $objContestTypeModel = Admin_Model_ContestsType::getInstance();
        $objContests = Admin_Model_Contests::getInstance();
        $objSports = Admin_Model_Sports::getInstance();
        $objTicketModel = Admin_Model_Tickets::getInstance();
        $tickets = $objTicketModel->getActiveTickets();
        $sportsDetails = $objSports->getSportsDetails();
        $objGameStats = Application_Model_GameStats::getInstance();
        if ($sportsDetails) :
            $this->view->sports = $sportsDetails;
        endif;

        $contestDetails = $objContestTypeModel->getContestTypeDetails();
        if ($contestDetails) :
            $this->view->contest = $contestDetails;
        endif;
//        echo "<pre>"; print_r($contestDetails); echo "</pre>"; die;
        if(!empty($tickets)):
            $this->view->tickets = $tickets;
        endif;
        if ($this->_request->isPost()) {
            $data = array();

            $prizeType = $this->getRequest()->getParam('prize_type');
            $data['prize_pool'] = $this->getRequest()->getPost('prize_pool');
            $data['prizes'] = $prizeType;
			//echo "<pre>"; print_r($this->getRequest()->getPost()); echo "</pre>"; die;
            if ($prizeType == 1) {
                $payout[0]['from'] = 1;
                $payout[0]['to'] = 0;
                $payout[0]['type'] = 0;
                $payout[0]['amount'] = $data['prize_pool'];
                $payout[0]['ticket_id'] = NULL;
                $data['prize_payouts'] = json_encode($payout);
            } elseif ($prizeType == 2) {
                $firstAmt = round($data['prize_pool'] * 0.7);
                $secondAmt = round($data['prize_pool'] * 0.3);
                $payout[0]['from'] = 1;
                $payout[0]['to'] = 0;
                $payout[0]['type'] = 0;
                $payout[0]['amount'] = $firstAmt;
                $payout[0]['ticket_id'] = NULL;
                $payout[1]['from'] = 2;
                $payout[1]['to'] = 0;
                $payout[1]['type'] = 0;
                $payout[1]['amount'] = $secondAmt;
                $payout[1]['ticket_id'] = NULL;
                $data['prize_payouts'] = json_encode($payout);
            } elseif ($prizeType == 5) {
                $limit = $this->getRequest()->getPost('entry_limit');
                $range = floor($limit / 2);

                $amt = round($data['prize_pool'] / ($range));

                $payout[0]['from'] = 1;
                $payout[0]['to'] = $range;
                $payout[0]['type'] = 0;
                $payout[0]['amount'] = $amt;
                $payout[0]['ticket_id'] = NULL;

                $data['prize_payouts'] = json_encode($payout);
            } elseif ($prizeType == 6) {

                $from = $this->getRequest()->getPost('rank_from');
                $to = $this->getRequest()->getPost('rank_to');
                $type = $this->getRequest()->getPost('payout_type');
                $amount = $this->getRequest()->getPost('rank_amt');
                $ticketId = $this->getRequest()->getPost('ticket_id'); 
                $aindex = 0;
                $tindex = 0;
                foreach ($from as $key => $value) {
                    $payout[$key]['from'] = $from[$key];
                    $payout[$key]['to'] = $to[$key];
                    $payout[$key]['type'] = $type[$key];
                    $payout[$key]['amount'] = NULL;
                    $payout[$key]['ticket_id'] = NULL;
                    if (($type[$key]) == 0) { //payout type is zero means prize is amount
                        $payout[$key]['amount'] = $amount[$aindex];
                        $aindex++;
                    } else if (($type[$key]) == 1) { //prize type is one means prize is ticket
                         $payout[$key]['ticket_id'] = $ticketId[$tindex];
                        $tindex++;
                    }
                }
                $data['prize_payouts'] = json_encode($payout);
            }



            $gameid = $this->getRequest()->getPost('sports_id');
            $matchID = $this->getRequest()->getPost('match_id');
            $matchID = explode('@', $matchID);
            $data['match_id'] = $matchID[0];

            $startTime = $this->getRequest()->getPost('start_time');
            $matchdate = date("Y-m-d", strtotime($matchID[2]));
            $time = date("H:i", strtotime($startTime));
            $date = $matchdate . ' ' . $time;
            //echo $matchdate; die;                   
            $data['created_by'] = $this->view->session->storage->user_id;
            $data['start_time'] = $date;
            $data['contest_name'] = $this->getRequest()->getPost('contest_name');
            $data['sports_id'] = $this->getRequest()->getPost('sports_id');
			
            $response = $objGameStats->getGameStats($data['sports_id'], $matchdate);
            if (isset($response)) {
                $contest_res = json_decode($response['game_stat'], true);
                $matchCount = count($contest_res['match']);

                if ($matchCount == 1) {
                    $response = $objGameStats->getFutureGameStats($data['sports_id'], $matchdate, 5);

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
                        $gameLastDate = date('Y-m-d H:i:s', strtotime($gameLastDate . '+4hours'));
                        break;
                    case 2 :
                        $gameLastDate = date('Y-m-d H:i:s', strtotime($gameLastDate . '+4hours'));
                        break;
                    case 3 :
                        $gameLastDate = date('Y-m-d H:i:s', strtotime($gameLastDate . '+3hours'));
                        break;
                    case 4 :
                        $gameLastDate = date('Y-m-d H:i:s', strtotime($gameLastDate . '+3hours'));
                        break;
                }
            }

            $data['end_time'] = $gameLastDate;

            $startTimeStamp = strtotime($data['start_time']);
            $endTimeStamp = strtotime($data['end_time']);

            if ($startTimeStamp == $endTimeStamp) {
                $data['end_time'] = date('Y-m-d H:i:s', strtotime("+5 hours", strtotime($data['end_time'])));
            }


            $data['play_limit'] = $this->getRequest()->getPost('entry_limit');
            $data['challenge_limit'] = $this->getRequest()->getPost('challenge_limit');
            $data['entry_fee'] = $this->getRequest()->getPost('fee');

            $data['fpp'] = $this->getRequest()->getPost('fpp_reword');
            $data['con_type_id'] = $this->getRequest()->getPost('contest_type');
            $data['play_type'] = $this->getRequest()->getPost('play_type');
            $data['description'] = $this->getRequest()->getPost('desctext');

            $check = $this->getRequest()->getPost('ticket');

            if ($check) {

                do { //to get the unique ticket code
                    $code = $this->generateRandomString();
                    $find = $objTicketModel->getTicketByCode($code);
                } while (isset($find) && $find != 0);
                $ticketData = array();
                $ticketData['code'] = $code;
                $ticketData['description'] = $data['contest_name'] . "(" . $data['entry_fee'] . " DFS)";
                $ticketData['bonus_amt'] = $data['entry_fee'];
                $ticketData['valid_from'] = date('y-m-d');
                $ticketData['valid_upto'] = $data['start_time'];
                $ticketID = $objTicketModel->uploadNewTicket($ticketData);
                $data['ticket_id'] = $ticketID;
            }
			//echo "<pre>"; print_r($data); die;
            $objContests->setContestsDetails($data);
			
            $this->view->success = $objContests;
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 25/03/2014
     * Description : Set new contest type.
     */
    public function newContestTypeAction() {
        $objContestTypeModel = Admin_Model_ContestsType::getInstance();
        if ($this->getRequest()->isPost()) :
            $data = array();
            $data['display_name'] = $this->getRequest()->getPost('contest_name');
            $data['status'] = $this->getRequest()->getPost('status');
            $contestDetails = $objContestTypeModel->setContestTypeDetails($data);
            if ($contestDetails):
                $this->view->success = $contestDetails;
            endif;
        endif;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 25/03/2014
     * Description : Get new contest type details.
     */
    public function contestTypeDetailsAction() {
        $objContestTypeModel = Admin_Model_ContestsType::getInstance();
        $contestDetails = $objContestTypeModel->getAllContestTypeDetails();
        if ($contestDetails):
            $this->view->constant = $contestDetails;
        endif;
        //echo "<pre>"; print_r($result); echo "</pre>"; die;
    }

    /**
     * Developer : Vivek Chaudhari
     * Date : 14/06/2014
     * Description : Edit Contest details
     */
    public function editContestAction() {
        $objContestTypeModel = Admin_Model_ContestsType::getInstance();
        $objContests = Admin_Model_Contests::getInstance();
        $contestTypeDetails = $objContestTypeModel->getContestTypeDetails();
        if ($contestTypeDetails) :
            $this->view->contest = $contestTypeDetails;
        endif;
        $contestID = $this->getRequest()->getParam('cid');
        $contestDetails = $objContests->getContestsDetailsById($contestID);
		//echo "<pre>"; print_r($contestDetails); die;
        if ($contestDetails) :
            $this->view->contestDetails = $contestDetails;
        endif;

        if ($this->getRequest()->isPost()) :
            $data = array();
            $data['contest_name'] = $this->getRequest()->getPost('contest_name');
            $data['sports_id'] = $this->getRequest()->getPost('sports_id');
            $data['play_limit'] = $this->getRequest()->getPost('entry_limit');
            $data['challenge_limit'] = $this->getRequest()->getPost('challenge_limit');
            $data['entry_fee'] = $this->getRequest()->getPost('fee');
            $data['match_id'] = $this->getRequest()->getPost('match_id');
            $data['prize_pool'] = $this->getRequest()->getPost('prize_pool');
            $data['fpp'] = $this->getRequest()->getPost('fpp_reword');
            $data['start_time'] = $this->getRequest()->getPost('start_time');
            $data['con_type_id'] = $this->getRequest()->getPost('contest_type');
            $data['play_type'] = $this->getRequest()->getPost('play_type');
            $data['prizes'] = $this->getRequest()->getPost('prize_type');
            $data['description'] = $this->getRequest()->getPost('desctext');
			//echo "<pre>"; print_r($data); die;
            $objContests->updateContestDetails($contestID, $data);
            // print_r( $objContests);die;
            $contestDetails = $objContests->getContestsDetailsById($contestID);
            //print_r($contestDetails);die;
            if ($contestDetails) :
                $this->view->contestDetails = $contestDetails;
                $this->view->success = $contestDetails;
            // $this->_redirect('/admin/contest-details');
            endif;
        endif;
        //echo "<pre>"; print_r($contestDetails); echo "</pre>"; die;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 14/05/2014
     * Description : Edit Contest Type details
     */
    public function editContestTypeAction() {
        $objContestTypeModel = Admin_Model_ContestsType::getInstance();
        $contestID = $this->getRequest()->getParam('cid');
        $contesTypeDetails = $objContestTypeModel->getContestTypeDetailsById($contestID);
        if ($contesTypeDetails) {
            $this->view->contestType = $contesTypeDetails;
        }
        if ($this->getRequest()->isPost()) :
            $data = array();
            $data['display_name'] = $this->getRequest()->getPost('contest_name');
            $data['status'] = $this->getRequest()->getPost('status');
            $check = $objContestTypeModel->updateContestType($data, $contestID);
            $contesTypeDetails = $objContestTypeModel->getContestTypeDetailsById($contestID);
            if ($contesTypeDetails) {
                $this->view->contestType = $contesTypeDetails;
                $this->_redirect('/admin/contest-type-details');
            }
            if ($check) {
                $this->view->success = $check;
            }
        endif;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 15/07/2014
     * Description : Get Contest details
     */
    public function getContestAction() {
        $this->_helper->layout()->disableLayout();
        $data = "";
        $objContestTypeModel = Admin_Model_ContestsType::getInstance();
        $objContests = Admin_Model_Contests::getInstance();
        $objSports = Admin_Model_Sports::getInstance();
        $objGameState = Admin_Model_GameStats::getInstance();

        if ($this->getRequest()->isPost()) {
            $gameid = $this->getRequest()->getParam('sports_id');

            if ($gameid) {

                $sportsData = $objSports->getSportsDetailsByID($gameid);
				
                //print_r($sportsData);die('test');

                $extendDate = date("Y-m-d");
                $extendDate = strtotime($extendDate);
                $extendDate = strtotime("+30 day", $extendDate);
                $extendDate = date('Y-m-d', $extendDate);
                $current_date = date("Y-m-d");


                $contestDetails = $objGameState->getGameStatsByID($gameid, $current_date, $extendDate);
                //     echo "<pre>"; print_r($contestDetails); echo "</pre>"; die;
                $i = 0;
                if (isset($contestDetails)) {
                    foreach ($contestDetails as $contest) {
                        $contestData = json_decode($contest['game_stat']);
                        if (isset($contestData->match)) {
                            foreach ($contestData->match as $mm) {
//                               echo "<pre>"; print_r($mm); echo "</pre>"; die;
                                if ($mm->time != 'TBD') {
                                    @$data[$i]['id'] = $mm->id;
                                    @$data[$i]['match_date'] = $mm->formatted_date;
                                    @$data[$i]['match_time'] = $mm->time;
                                    @$data[$i]['status'] = $mm->status;
                                    @$data[$i]['hometeam'] = $mm->hometeam->name;
                                    @$data[$i]['awayteam'] = $mm->awayteam->name;
                                    $i++;
                                }
                            }
                        }
                    }
                }
                if ($data) {
                    echo json_encode($data);
                } else {
                    $response = new stdClass();
                    $response->message = 'error';
                    $response->code = 198;
                    echo json_encode($response);
                }
            }
        }
    }

    /* Developer: Vini Dubey
     * Date: 06/08/2014
     * Description: Add Featured contest
     */

    public function featuredContestAction() {
        $objContests = Admin_Model_Contests::getInstance();
        $featuredContest = $objContests->getNotFeaturedContests();
        if ($featuredContest) :
            $this->view->contest = $featuredContest;
        endif;
    }

    /*
     * Developer: Bhojraj Rawte
     * Date: 08/08/2014
     * Description: Featured contest Details
     */

    public function featuredContestDetailsAction() {
        $objContests = Admin_Model_Contests::getInstance();
        $featuredContest = $objContests->getFeaturedContests();
        if ($featuredContest) :
            $this->view->contest = $featuredContest;
        endif;
    }

    function generateRandomString($length = 7) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

}
