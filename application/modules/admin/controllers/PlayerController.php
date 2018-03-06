<?php

/**
 * AdminController
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class Admin_PlayerController extends Zend_Controller_Action {

    public function init() {
        
    }

    /**
     * Developer    : Vivek Chaudhari   
     * Date         : 14/07/2014
     * Description  : show team player details and show teams according to sport then send this data to ajax caller
     */
    public function playerDetailsAction() {
        $objGamePlayersModel = Admin_Model_GamePlayers::getInstance();
        $objSportModel = Admin_Model_Sports::getInstance();
        
        $sports = $objSportModel->getSportsDetails();
        if(!empty($sports)){ 
            $this->view->activesport = $sports;
        } 
        if ($this->getRequest()->isPost()){
            $this->_helper->_layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(TRUE);
            $method = $this->getRequest()->getParam('method');
            
            switch($method){
                case "getTeams":
                    $sport = $this->getRequest()->getParam('sport');
                    $dbteams = $objGamePlayersModel->getTeamsBySport($sport);
                    if (isset($dbteams)) {
                        $teams = array();
                        foreach ($dbteams as $key => $value) {
                            $decode = json_decode($value['plr_details'], true);
                            if (!empty($decode['team_code']) && !empty($decode['team_name'])) {
                                $teams[$decode['team_code']] = $decode['team_name'];
                            }
                        }
                        $teams = array_unique($teams, SORT_REGULAR);
                        if (isset($teams)) {
                            echo json_encode($teams);
                        }
                    } else {
                        echo 0;
                    }
                    break;
                case "getPlayers":
                    $team = $this->getRequest()->getParam('team');
                    $sport = $this->getRequest()->getParam('sport');
                    if($team && $sport){
                        $details = $objGamePlayersModel->getAllPlayerDetails($team,$sport); 
						//echo "<pre>"; print_r($details); die;
                        echo json_encode($details);
                    }else{
                        echo 0;
                    }
                    break;
                default :
                    break;
            }
		}
    }

    public function playerStatsAction() {

        $objSportsModel = Admin_Model_Sports::getInstance();
        $getSportsDetails = $objSportsModel->getSportsDetails();
        if ($getSportsDetails) {
            $this->view->sportsDetails = $getSportsDetails;
        }
        $objParser = Engine_Utilities_GameXmlParser::getInstance();
        $gametype = 'MLB';
        //$gametype = 'NBA';

        if ($gametype == 'MLB') {
            $sports_id = 2;
        } else if ($gametype == 'NFL') {
            $sports_id = 1;
        } else if ($gametype == 'NBA') {
            $sports_id = 3;
        } else if ($gametype == 'NHL') {
            $sports_id = 4;
        }

        $statsData = $objParser->getPlayerStats($gametype);

        if ($statsData) {
            $objPlayerStatModel = Application_Model_PlayerStats::getInstance();
            $objPlayerStatModel->insertPlayersStats($statsData, $sports_id);
        }
    }

    /**
     * Developer : Manoj 
     * Description : get game players 
     */
    public function gamePlayerAction() {

        $objParser = Engine_Utilities_GameXmlParser::getInstance();
        $gametype = 'MLB';
        $playerArray = $objParser->getGamePlayers($gametype);

        if ($gametype == 'MLB') {
            $sports_id = 2;
        } else if ($gametype == 'NFL') {
            $sports_id = 1;
        } else if ($gametype == 'NBA') {
            $sports_id = 3;
        } else if ($gametype == 'NHL') {
            $sports_id = 4;
        }

        if ($playerArray) {
            $objGamePlayers = Application_Model_GamePlayers::getInstance();
            $objGamePlayers->bulkInsert($playerArray, $sports_id);
        }
    }

    /**
     * Developer    : Vivek Chaudhari   
     * Date         : 14/07/2014
     * Description  : edit disability according to player id
     */
    public function editDisabilityAction() {
        $plr_id = $this->getRequest()->getParam('plr_id');
        $ids = explode('@', $plr_id);        
        $plr_id = $ids[0];
        $gameID = $ids[1];
        
        $objGamePlayersModel = Application_Model_GamePlayers::getInstance();
        //$data = $objGamePlayersModel->getPlayerByPlayerId($plr_id);
        $data = $objGamePlayersModel->getPlayerByGameId($gameID);
        
        $playerDetails = json_decode($data['plr_details'],true);       
       

        if ($this->getRequest()->isPost()) {
            $data = array();
            $data['injury_status'] = $this->getRequest()->getParam('injury_status');
            $data['injury_reason'] = $this->getRequest()->getParam('reason');
            $data['fppg'] = $this->getRequest()->getParam('fppg');
            $data['fpts'] = $this->getRequest()->getParam('fpts');
            $data['plr_value'] = $this->getRequest()->getParam('plrvalue');
            $check = $objGamePlayersModel->updateDisability($gameID, $data);
            $this->view->success = $check;
        }
        if (isset($playerDetails) && !empty($playerDetails)):            
            $this->view->plrDetails = $playerDetails;
            $this->view->playerDetails  = $data; 
        endif;
        
    }

    public function playerSalaryAction() {        
        
        $objGamePlayerModel = Application_Model_GamePlayers::getInstance();
        
        
        if ($this->getRequest()->isPost()) {
            if ($_FILES) {
               //echo "<pre>"; print_r($_FILES); echo "</pre>"; die('jbj');
                $upload = new Zend_File_Transfer();
                $upload->addValidator('Extension', false, array('csv'));
                $files = $upload->getFileInfo();

                $errorNotify = 0;
                foreach ($files as $file => $info) {
                    if (!$upload->isUploaded($file)) {

                        $errmsg = "Please select sheet to Upload!";
                        $errorNotify = 1;
                        continue;
                    }
                    if (!$upload->isValid($file)) {

                        $errmsg = "Invalid File extension. Please upload only *.csv file";
                        $errorNotify = 1;
                        continue;
                    }
                }
                           
                 
                if ($errorNotify == 0) {
                    $destination =  getcwd() .'/assets/csv/';
                    $destination = str_replace('\\', "/", $destination);
                    $upload->setDestination($destination);
                    $filename1 = $files['file1']['name'];
                   // $filename2 = $files['file2']['name'];
                    $upload->receive();
                    $filename1 = 'csv/' . $filename1;
                   // $filename2 = 'csv/' . $filename2;
                    
                    $destination1 = getcwd() . '/assets/'.$filename1;
                    $fp1 = fopen($destination1,'r');                  
                    
                    $row = 0;
                    while($csv_line = fgetcsv($fp1)) {                         
                      
                        $salaryData1[$row]['Name'] = $csv_line[0];
                        $salaryData1[$row]['Team'] = $csv_line[1];
                        $salaryData1[$row]['salary'] = $csv_line[2]; 
                        $row ++;    
                      
                    }
                    fclose($fp1); 
                    
					foreach ($salaryData1 as $salaryData){           
                        $salaryData['Name'] = addslashes($salaryData['Name']);
                        $salaryData['salary'] = addslashes($salaryData['salary']);
						//echo "<pre>"; print_r($salaryData); echo "</pre>"; 
						$objGamePlayerModel->updateSalaryByName($salaryData['Name'],$salaryData['salary']);    
                    }
                                      
                   $this->view->success = '1';
                }else{
                    $this->view->error = $errmsg;
                }
            }
            
                    
        }
    }
	
	public function playerSalarySettingAction() {
		$objSettingsModel = Admin_Model_Settings::getInstance();
		
		
		$data = array();
		if ($this->getRequest()->isPost()) {
			$arr['max_salary_amount'] = trim($this->getRequest()->getPost('max_salary_amount'));
			$arr['min_salary_amount'] = trim($this->getRequest()->getPost('min_salary_amount'));
			$arr['plr_fppg'] = trim($this->getRequest()->getPost('plr_fppg'));
			$arr['salary_amount'] = trim($this->getRequest()->getPost('salary_amount'));
			$arr['status'] = trim($this->getRequest()->getPost('status'));
            $data['player_salary_setting'] = serialize($arr);            
            $check = $objSettingsModel->updateSettingsDeatils($data,1);
			if($check){
				$this->view->success = $check;
			}            
        }
		$PlayerSettings = $objSettingsModel->getSettingsDeatils();
		if(!empty($PlayerSettings['player_salary_setting'])){
			$this->view->PlayerSettings = unserialize($PlayerSettings['player_salary_setting']);
		}
		//echo "<pre>"; print_r($this->getRequest()->isPost()); die;
	}

}
