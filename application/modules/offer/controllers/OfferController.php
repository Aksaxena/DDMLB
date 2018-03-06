<?php
/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class Offer_OfferController extends Zend_Controller_Action {



    public function init() {

    }

    public function acceptAction(){
        
      
    }
    public function offerAction(){
        
      
    }
    public function manageOffersAction(){
        
      
    }
    
    /*
     * Name        :   Vivek Chaudhari
     * Date        :   23/06/2014
     * Description :   to show a valid offer after inserting into contest         
     */
    
    public function onlineOfferAction(){
        
        $lineupID = $this->getRequest()->getParam('lid');              
         $objLineupModel = Application_Model_Lineup::getInstance();
         $objOffersModel = Application_Model_Offers::getInstance();
         $objGamaPlayerModel = Application_Model_GamePlayers::getInstance();
         $objUserLineupModel = Application_Model_UserLineup::getInstance();
         
         $offers = $objOffersModel->getOfferAndContestDetails();
         if($offers):
             $okey = array_rand($offers,1);
         endif;
         
        $playerId = $objLineupModel->getPlayerIdsByLineupId($lineupID);  
        $playerIds=json_decode($playerId['player_ids']); 
        $position = json_decode($playerId['pos_details'],true);
//        $details = $objGamaPlayerModel->getPlayerList($playerIds); 
       $details = $objGamaPlayerModel->getPlayerListBySport($playerIds,$playerId['sports_id']);
        foreach ($details as $key=>$value){            
            $playerDetails=(array)json_decode($value['plr_details']);   
                $sortKey = array_search($value['plr_id'], $playerIds);
                $data[$sortKey]['name'] = $playerDetails['name'];
                $data[$sortKey]['position'] = $position[$value['plr_id']];
                $data[$sortKey]['plr_value'] = $value['plr_value'];
        } ksort($data);
        $headData = $objUserLineupModel->getContestDetailsByLineup($lineupID);
        if(!empty($headData)){
            $this->view->headData = $headData;
        } 
          if($data){
            $this->view->playerIds = $data;
        }
        if($offers){
            $this->view->offer = $offers[$okey];
        }
        $this->view->lineupId = $lineupID;
    }
    
     /**
     * Developer    : Vivek Chaudhari
     * Description  : update contest entry
     * Date         : 08/08/2014
     * @param <int> $lid
     * @return <array>
     */
    public function oneTimeOfferAction(){
        $lineupId = $this->getRequest()->getParam('lid');
        $userId = $this->view->session->storage->user_id;
        $objContestsModel = Application_Model_Contests::getInstance();
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objUserAccount = Application_Model_UserAccount::getInstance();
        $objUserLineupModel = Application_Model_UserLineup::getInstance();
        $sport = $objLineupModel->getPlayerIdsByLineupId($lineupId);
        $userAmount = $objUserAccount->getUserBalance($userId);
        $contest = $objUserLineupModel->getContestDetailsByLineup($lineupId);
        $this->view->session->storage->userBalance = $userAmount['balance_amt'];
        $sport_id = $sport['sports_id'];
        $result = $objLineupModel->getJoinedContest($userId);
		
        $conIds = array();
		if(!empty($result)) {
			foreach ($result as $key => $value):
				array_push($conIds, $value['contest_id']);
			endforeach;
		}
		//echo $contest['start_time']; die;
		//echo "<pre>";print_r($contest); die('AS');    
        $details = $objContestsModel->getUnjoinedByStartTymeContest($conIds,$sport_id,$contest['match_id']);
        //$details = $objContestsModel->getUnjoinedContest($conIds,$sport_id);
		//echo "<pre>";print_r($details); die('AS');  
        $this->view->enter = $contest;
        $this->view->contest = $details;
        $this->view->balance = $userAmount['balance_amt'];
        $this->view->lineup = $lineupId;
    }
    
    
    public function liveScoreAction(){
        
    }
    
     /*
     * Name        :   Bhojraj Rawte
     * Date        :   30/07/2014
     * Description :   export lineup data ajax 
     */
    public function exportAjaxAction(){         
        $this->_helper->layout()->disableLayout();
        if ($this->getRequest()->isPost()) {
        $lineupID = $this->getRequest()->getPost('lid');  
        
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objOffersModel = Application_Model_Offers::getInstance();
        $objGamaPlayerModel = Application_Model_GamePlayers::getInstance();
        $playerId = $objLineupModel->getLineupByID($lineupID); 
        $result = $objLineupModel->getLineupDetailsbyLid($lineupID);
        $playerIds = json_decode($result['player_ids'],true);    
        $position = json_decode($result['pos_details'],true);
        $details=$objGamaPlayerModel->getPlayerList($playerIds);     
//        echo "<pre>"; print_r($result); echo "</pre>"; die;
        foreach($details as $dkey=>$dval){
            $playerDetails=(array)json_decode($dval['plr_details']); 
            $pkey = array_search($playerDetails['id'], $playerIds);
            $data[$pkey]['name'] = $playerDetails['name'];
            $data[$pkey]['position'] = $position[$playerDetails['id']];
            $data[$pkey]['Player_id'] = $playerDetails['id'];
            $data[$pkey]['team_name'] = $playerDetails['team_name'];
            $team_name = $playerDetails['team_name'];
            $teamResult = $objGamaPlayerModel->getTeamIdByTeam($team_name);
            $data[$pkey]['team_id'] = $teamResult['team_id'];
            $data[$pkey]['sports_id'] = $teamResult['sports_id'];
        }
        
        ksort($data);
        if(isset($data)){
            if(isset($result['sports_id'])){
                $objParser = Engine_Utilities_GameXmlParser::getInstance();
                switch ($result['sports_id']) {
                    case 1:
                           $data = $objParser->arrangeNFLineUp($data);
                        break;
                    case 2:
                        $data = $objParser->arrangeMLBLineUp($data);
                        break;
                    case 3:
                        $data = $objParser->arrangeNBALineUp($data);
                        break;
                    case 4:
                        $data = $objParser->arrangeNHLineUp($data);
                        break;
                    default:
                        break;
                }
            }
        }
        
        if($data){
            $this->view->playerIds = $data;
            $this->view->lineup = $playerId;
        }

    }
    /**Name: Chandra Sekhar Reddy 
     * Date:27/09/2014 
     * Description : To display Contest status,entries and last edit date in export lineup page 
     */
    if (isset($this->view->session->storage->user_id)) {
                $userId = $this->view->session->storage->user_id;
                $objUserLineupModel = Application_Model_UserLineup::getInstance();
                $result = $objLineupModel->getUserLineups($userId);
                $objContestModel = Application_Model_Contests::getInstance();
                $mylineups = array();
                if (!empty($result)) {
                    foreach ($result as $key => $value){
                        if ($value['lineup_id'] == $lineupID) {
                            $mylineups[$key]['contest_id'] = $value['contest_id'];
                            $mylineups[$key]['created_date'] = $value['created_date'];
                            $mylineups[$key]['con_status'] = 0;
                            if ((isset($value['contest_id']) && ($value['contest_id'] != 0))){
                                $contestDetails = $objContestModel->getContestsDetailsById($value['contest_id']);
                                if (isset($contestDetails)){
                                    $mylineups[$key]['con_status'] = $contestDetails['con_status'];
                                }
                            }
                            $countResult = 0;
                            if ((isset($value['lineup_id']) && ($value['lineup_id'] != 0))){
                                $countResult = $objUserLineupModel->getLineupUseCount($value['lineup_id']);
                                $mylineups[$key]['entries'] = $countResult;
                                $key = end(array_values($mylineups));
                            }
                        }
                    }
                }
                $this->view->lineupdetails = $mylineups;
                $this->view->createdcondetails = $key;
            }
    
    }
}
