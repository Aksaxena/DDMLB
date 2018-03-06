<?php

/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';
require_once '../vendor/autoload.php';

class User_UsercontestController extends Zend_Controller_Action {

    public function init() {
        
    }

    public function liveContestAction() {
        
    }

    public function upCommingAction() {
        
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 03/06/2014
     * Description : Get User Contest details
     */
    public function contestAction() { 
        $userID = $this->view->session->storage->user_id;
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objParser = Engine_Utilities_GameXmlParser::getInstance();
        $currentDate = date('Y-m-d H:m:s');
        $subDate = Date('Y-m-d H:m:s', strtotime("-3 days"));
        $contestDetails = $objLineupModel->getUserLineupDetails($userID, $currentDate, $subDate);
   
        if ($contestDetails) {
               foreach($contestDetails as $contest){
                   if($contest['con_status'] == 0){
                       $upcomingcontestData[] = $contest;
                   }
				   if($contest['con_status'] == 1){
                       $completedcontestData[] = $contest;
                   }
				  
               }           
            $searchKey = "con_status";

            $completedcontestData = $objParser->filterArray(1, $contestDetails, $searchKey);
          
            $livecontestData = $objParser->filterArray(2, $contestDetails, $searchKey);
 
                  
            if (isset($upcomingcontestData)) {
                $this->view->upcomingcontestData = $upcomingcontestData;
            }
			//echo "<pre>";print_r($contestDetails); die;
            if (isset($completedcontestData)) {
                $this->view->completedcontestData = $completedcontestData;
            }

            if (isset($livecontestData)) {
                $this->view->livecontestData = $livecontestData;
               
            }
 
        }
  
    }

    public function completedContestAction() {
        
    }

    public function usercontestAjaxHandlerAction() {

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $userID = $this->view->session->storage->user_id;
        $objLineupModel = Application_Model_Lineup::getInstance();
        if ($this->getRequest()->isPost()) {
            $method = $this->getRequest()->getParam('method');

            switch ($method) {
                case 'getcontest':
                    $entryFee = $this->getRequest()->getParam('entryFee');
                    $day = $this->getRequest()->getParam('day');

                    $subDate = "";
                    $currentDate = date('Y-m-d H:m:s');
                    if ($day != -1) {
                        $subDate = Date('Y-m-d H:m:s', strtotime("-" . $day . " days"));
                    }

                    $filterData = $objLineupModel->getUserFilterLineupDetails($userID, $entryFee, $day, $currentDate, $subDate);
                      // echo "<pre>";    print_r($filterData); echo "</pre>"; die;
                    if (!empty($filterData)) {
                        foreach($filterData as $k=>$v){
                            $filterData[$k]['showtime'] = date('d-M-Y',strtotime($v['start_time']));
                        }
                        echo json_encode($filterData);
                    } else {
                        echo 0;
                    }
                    break;
                    case 'sendInvite': 
						$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
						$postmark_config = $config->getOption('postmark');
                        //$mailer = Engine_Mailer_Mailer::getInstance();
                        $email = $this->getRequest()->getParam('email');
                        $msg = $this->getRequest()->getParam('msg');
                        $useremail = $email;            
                        $message = $msg; 
                        $username = $this->view->session->storage->user_name;
                        $subject = "Invite Friend";
                        $topic = "Invite";
                        $template_name = 'invitefriend';
                     
						try{
							$client = new Postmark\PostmarkClient($postmark_config['key']);
							$result = $client->sendEmailWithTemplate(
								$postmark_config['email'],
								$useremail,
								$postmark_config['invitefriend'], 
								[
									"site_name" => $postmark_config['site_name'],
									"invite_sender_name" => $username,
									"username" => $username,
									"friendmessage" => $message,
									"topic" => $topic,
								] 
							);
						} catch (Exception $e){
						   
						}
						if($result){
							$insertdataemaillog = array(
									'sent_email' => $useremail,
									'sent_time' => date('Y-m-d H:i:s'),
									'sent_template'=>$template_name,
									'message'=>$message
								);  
							$insertdataemaillog_result = $objEmaillog ->insertEmailLog($insertdataemaillog);
						}
		
						echo $result;
						break;    
				default:
					break;
            }
        }
    }

     /**
     * Developer    : vivek chaudhari
     * Date         : 16/06/2014
     * Description  : get lineup details according to contest id
     * Params       : param1<int> = contest Id.
     */
    public function gameCenterAction() {
        $contestId = $this->getrequest()->getParam('contest-id'); 
        $userID = $this->view->session->storage->user_id;  
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objUserLineupModel = Application_Model_UserLineup::getInstance(); 
        $objContestModel = Application_Model_Contests::getInstance();
        $standings = $objUserLineupModel->getContestLineups($contestId); /* get all enter teams in that contest */
        $contestData = $objContestModel->getContestsDetailsById($contestId);
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();		
		if(!empty($standings)){		
			foreach($standings as $skey=>$sval){ 
				/* to insert random name for bot players */			
				if($sval['created_by'] == 4){ 	
					$name = $this->generateRandomString(); 	
					$standings[$skey]['user_name'] = $name;	
				}	
			}	
		}
		
        $lineup = $objLineupModel->getLineup($contestId,$userID);
        $scorings = json_decode(($lineup['scoring']), true);
        $playerPoints = json_decode(($lineup['point_details']), true);
        $playerIds = json_decode(($lineup['player_ids']),true);
        $position = json_decode(($lineup['pos_details']),true);
        $data = array();
		if(!empty($playerIds)){
			
			foreach ($playerIds as $key => $value) {
				$player_name = $objLineupModel->getNameByPlayerId($value,$lineup['sports_id']); 
				$name = json_decode(($player_name['plr_details']), true);
				$poskey = array_search($value, $playerIds);	
				$data[$poskey]['id'] = $value;	
				$data[$poskey]['name'] = $name['name'];	
				$data[$poskey]['position'] = $position[$value];
				
				if(!isset($data[$poskey]['position'])){ $data[$poskey]['position'] = $name['pos_code'];  }	
				
					if(isset($playerPoints[$value])){
						$data[$poskey]['point'] = $playerPoints[$value];
						$data[$poskey]['score'] = $scorings[$value];	
					}		
			}	
		}
       
        if(isset($contestData['sports_id'])){
            $objPaser = Engine_Utilities_GameXmlParser::getInstance();
            switch($contestData['sports_id']){
                case 1 :
                    $data = $objPaser->arrangeNFLineUp($data);
                    break;
                case 2 :
                    $data = $objPaser->arrangeMLBLineUp($data);
                    break;
                case 3 :
                    $data = $objPaser->arrangeNBALineUp($data);
                    break;
                case 4 :
                    $data = $objPaser->arrangeNHLineUp($data);
                    break;
                default :
                    ksort($data);
                    break;
            }
        }
        $this->view->standings = $standings;
        $this->view->lineup = $lineup;
        $this->view->live_scorings = $data;   

    }
    /**
     * Developer    : vivek chaudhari
     * Date         : 16/06/2014
     * Description  : send html response in game center page for selected team
     * Params       : param1<int> = lineup Id.
     */
    public function gameCenterAjaxAction(){
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $objLineupModel = Application_Model_Lineup::getInstance();
        $objUserLineupModel = Application_Model_UserLineup::getInstance();
        $method = $this->getRequest()->getParam('method');
        
        switch ($method):
            case 'eachlineup':
                    $ulid = $this->getRequest()->getParam('lid');
                    $lineup = $objUserLineupModel->getLineupDetByLid($ulid);
                    $scorings = json_decode(($lineup['scoring']), true);
                    $playerPoints = json_decode(($lineup['point_details']), true);
                    $ids = json_decode($lineup['player_ids'],true); 
                    $position = json_decode(($lineup['pos_details']),true);
                    $data = array();
					if(!empty($ids)){
						foreach ($ids as $key => $value) :            
							$player_name = $objLineupModel->getNameByPlayerId($value,$lineup['sports_id']);            
							$name = json_decode(($player_name['plr_details']), true);
							
							$posKey = array_search($value, $ids);
							$data[$posKey]['name'] = $name['name'];
							$data[$posKey]['position'] = $position[$value]; //$name['pos_code'];
							$data[$posKey]['id'] = $value;
							if(!isset($data[$posKey]['position'])){ $data[$posKey]['position'] = $name['pos_code'];  }
							
							if(isset($playerPoints[$value])):
								$data[$posKey]['point'] = $playerPoints[$value];
								foreach($scorings[$value] as $skey=>$score):
									$data[$posKey]['score'][$skey] = $score;
								endforeach;
							endif;
                        endforeach; 
						ksort($data);
                   
                        
						if (isset($lineup['sports_id'])) {
							$objPaser = Engine_Utilities_GameXmlParser::getInstance();
							switch ($lineup['sports_id']) {
								case 1 :
									$data = $objPaser->arrangeNFLineUp($data);
									break;
								case 2 :
									$data = $objPaser->arrangeMLBLineUp($data);
									break;
								case 3 :
									$data = $objPaser->arrangeNBALineUp($data);
									break;
								case 4 :
									$data = $objPaser->arrangeNHLineUp($data);
									break;
								default :
									ksort($data);
									break;
							}
						} 
					}
                    $details['lineup'] = $lineup;
                    $details['data'] = $data;
					echo json_encode($details);
                break;
                
            case 'liveFeed':
                    $ulid = $this->getRequest()->getParam('lid');
                    $contestId = $this->getRequest()->getParam('cid');                   
                    $lineup = $objUserLineupModel->getLineupDetByLid($ulid);
                    $standings = $objUserLineupModel->getLineupsByContestId($contestId); /* get all enter teams in that contest */                  
                    foreach($standings as $skey=>$sval){ /* to insert random name for bot players */
                        if($sval['created_by'] == 4){ 
                            $name = $this->generateRandomString(); 
                            $standings[$skey]['user_name'] = $name;
                        }
                    }  
                    $scorings = json_decode($lineup['scoring'], true);
                    $playerPoints = json_decode($lineup['point_details'], true);
                    $ids = json_decode($lineup['player_ids'],true); 
                    $position = json_decode($lineup['pos_details'],true);
                    $data = array();
                     foreach ($ids as $key => $value) :            
                        $player_name = $objLineupModel->getNameByPlayerId($value,$lineup['sports_id']);            
                        $name = json_decode(($player_name['plr_details']), true);                        
                        $posKey = array_search($value, $ids);
                        $data[$posKey]['name'] = $name['name'];
                        $data[$posKey]['position'] = $position[$value]; 
                        $data[$posKey]['id'] = $value;
                        if(!isset($data[$posKey]['position'])){ $data[$posKey]['position'] = $name['pos_code'];  }
                            if(isset($playerPoints[$value])):
                                $data[$posKey]['point'] = $playerPoints[$value];
                                foreach($scorings[$value] as $skey=>$score):
									$data[$posKey]['score'][$skey] = $score;
                                endforeach;
                            endif;
                        endforeach; ksort($data);
                     if (isset($lineup['sports_id'])) {
                        $objPaser = Engine_Utilities_GameXmlParser::getInstance();
                        switch ($lineup['sports_id']) {
                            case 1 :
                                $data = $objPaser->arrangeNFLineUp($data);
                                break;
                            case 2 :
                                $data = $objPaser->arrangeMLBLineUp($data);
                                break;
                            case 3 :
                                $data = $objPaser->arrangeNBALineUp($data);
                                break;
                            case 4 :
                                $data = $objPaser->arrangeNHLineUp($data);
                                break;
                            default :
                                ksort($data);
                                break;
                        }
                    }     
                    $details['lineup'] = $lineup;
                    $details['data'] = $data;
                    $details['standings'] = $standings; 
                    $details['yettoplay']=0; if(isset($ids) && isset($scorings)){$details['yettoplay'] = count($ids)- count($scorings);}
                 
                   echo json_encode($details);
                break;
        endswitch;
        
        
    }
    
    
   
//================Algorithm for salary calculation====================//
    /**
     * Developer    : vivek chaudhari
     * Date         : 25/07/2014
     * Description  : calculate salary for each player and store in db
     */
    public function salaryCapAction(){  //Not using , Skipped according to client requirement
        $sport = $this->getRequest()->getParam('sport');
        $objUserContestModel = Application_Model_PlayerStats::getInstance();
        $objGamePlayerModal = Application_Model_GamePlayers::getInstance();
        switch ($sport):
            case 'NFL' : $sportId = 1;
                
            case 'MLB' : $sportId = 2;
                
                $result = $objUserContestModel->playerStats($sportId);
  
//------------json decode team stats of teams---------------------------------------------        
                foreach ($result as $key=>$value){
                    $teams[$key] = json_decode($value['team_stats']);            
                }
        
//----------TEAMS PLAYERS ARE DIVIDED INTO BATTING AND PITCHING CATEGORY, 
//----------BECAUSE FURTHER ALGORITHM REQUIRE SEPERATE CATEGORY FOR POINTS CALCULATION----------- 
                foreach($teams as $key=>$value){
                    $batTeams[$key]=$value[0];
                    $pitchTeams[$key]=$value[1];
                }
        
                $batIndex=0; $pitchIndex=0;
       
                foreach($batTeams as $eachTeam){//storing all batting player details in single array
                    if(isset($eachTeam->team->player)){
                        foreach($eachTeam->team->player as $value){
                            $batPlayerDetail[$batIndex] = $value;    
                            $batIndex++; 
                        }
                    }
                }
                
        
                foreach($pitchTeams as $eachTeam){ //storing all pitching player detail in single array
                    if(isset($eachTeam->team->player)){
                        foreach($eachTeam->team->player as $key=>$value){
                            $pitchPlayerDetail[$pitchIndex] = $value;    
                            $pitchIndex++; 
                        }
                    }
                }
//-----------------ALGORITHM WORKS ACCORDING TO PLAYER CATEGORY(i.e. BATTING and PITCHING)----------------------
                foreach($batPlayerDetail as $value){  //calculate points for for batting players
                    $H='';$B2 ='';$B3='';$HR='';$TB='';$RBI='';$BB='';$K='';$SB='';$CS='';
                    if(isset($value->hits))              {$H = ($value->hits)*3;} 
                    if(isset($value->doubles))           {$B2 = ($value->doubles)*5;} 
                    if(isset($value->triples))           {$B3 = ($value->triples)*8;} 
                    if(isset($value->home_runs))         {$HR = ($value->home_runs)*10;}
                    if(isset($value->total_bases))       {$TB = ($value->total_bases);}
                    if(isset($value->runs_batted_in))    {$RBI = ($value->runs_batted_in)*2;}
                    if(isset($value->walks))             {$BB = ($value->walks)*2;}
                    if(isset($value->strikeouts))        {$K = ($value->strikeouts)*(-2);}
                    if(isset($value->stolen_bases))      {$SB = ($value->stolen_bases)*5;}
                    if(isset($value->caught_stealing))   {$CS = ($value->caught_stealing)*(-2);}
                    $A = $H+$B2+$B3+$HR+$TB+$RBI+$BB+$K+$SB+$CS;
                    $B = ($H-($B2+$B3+$HR))*3;
                    $pts = $A+$B;
                    $value->pts = $pts;
                }
       
                foreach($pitchPlayerDetail as $key=>$value){  //calculate points for pitching players
                    $GS=''; $W=''; $L=''; $S=''; $QS=''; $IP=''; $ER=''; $ERA='';
                    if(isset($value->games_started))     {$GS=($value->games_started)*1; }
                    if(isset($value->wins))              {$W = ($value->wins)*4;}
                    if(isset($value->losses))            {$L=($value->losses)*(-2);}
                    if(isset($value->saves))             {$S=($value->saves)*12;}
                    if(isset($value->quality_starts))    {$QS=($value->quality_starts)*3;}
                    if(isset($value->innings_pitched))   {$IP=($value->innings_pitched)*2;}
                    if(isset($value->earned_runs))       {$ER=($value->earned_runs)*(-1);}
                    if(isset($value->earned_run_average)){$ERA=($value->earned_run_average)*(-2);}
                    $Ap=$GS+$W+$L+$S+$QS+$IP+$ER+$ERA;
                    $value->pts = $Ap;
                } 
//---------POINTS VALUE FOR SALARY CALCULATION ACCORDING TO CATEGORY
//        NEED SINGLE ARRAY OF POINTS VALUE TO CALCULATE AVERAGE AND DEVIATION
                $points=array();
                foreach($batPlayerDetail as $key=>$value){
                    $batPlayerPoints[$key] = $value->pts;
                }
                foreach ($pitchPlayerDetail as $key=>$value){
                    $pitchPlayerPoints[$key]=$value->pts;
                }
                $points = array_merge($batPlayerPoints,$pitchPlayerPoints);
       
//-------------find constabts Z6=average of points, z4=deviation of points--------
                $Z6 = (array_sum($points))/(count($points));
//------------to calculate the deviation-----------------------
                $carry = 0.0;
                foreach ($points as $val) {
                    $d = ((double) $val) - $Z6;
                    $carry += $d * $d;
                };
                $Z4 = sqrt($carry / (count($points)));
        
//--------------calculate z score and store accordingly-----------------------------------
                foreach($batPlayerDetail as $value){
                    $Z_score = (($value->pts)-$Z6)/$Z4;
                    if($Z_score<0){
                        $Z_score = 0;
                    }
                    $value->z_score = $Z_score;
                 }

                foreach($pitchPlayerDetail as $value){
                    $Z_score = (($value->pts)-$Z6)/$Z4;
                    if($Z_score<0){
                        $Z_score = 0;
                    }
                    $value->z_score = $Z_score;
                }
            
//------------calculate constants AA4(for batting) and AC6(for pitching)-----------------------------------------
                $AA4=''; $AA5='';
                foreach($batPlayerDetail as $value){
                    $AA4 +=$value->z_score;
                }
                foreach($pitchPlayerDetail as $value){
                    $AA5 +=$value->z_score;
                }
//-------------calculate AC6= sum of cost of all teams;-----------------------------
                $AC6 = '';
                $teamCost =  $objUserContestModel->getTeamsCost();
                foreach ($teamCost as $key=>$value){
                    if(isset($value['salary'])){
                        $AC6 +=$value['salary'];
                    }
                  }
//              $AC6 = 3216086099;
//----------calculate actuals for each player---------------------------------------
                foreach($batPlayerDetail as $value){
                  $Actuals = (($value->z_score)/$AA4)*$AC6;
                  $Actuals = floor($Actuals);
                  $value->act = $Actuals;
                 }
                foreach($pitchPlayerDetail as $value){
                    $Actuals = (($value->z_score)/$AA5)*$AC6;
                    $Actuals = floor($Actuals);
                    $value->act = $Actuals;
                }
//----------------calculate cap hit-----------------------
                foreach($batPlayerDetail as $value){
                    $CapHit = (($value->act)/$AC6)*660000;
                    $cost = round($CapHit,-2);
                        if($cost<2000){
                            $cost = 2000;
                            }
                    $salary=$cost;
                    $value->salary = $salary;
                 }
                foreach($pitchPlayerDetail as $key=>$value){
                     $CapHit = (($value->act)/$AC6)*660000;
                     $cost = round($CapHit,-2);
                        if($cost<2000){
                            $cost = 2000;
                            }
                     $salary =$cost;
                     $value->salary = $salary;
                 }
        
//-----------------------save salary in db according to player id------------------------------------
//-----------------------NEED CLIENT CLEARIFICATION ON STRORING WHICH SALARY VALUE IF PLAYER FOUND IN BOTH CATEGORY--------
                    $index = 0;
                    foreach($batPlayerDetail as $key=>$value){
                            $batPlayers[$index]['id'] = $value->id;
                            $batPlayers[$index]['salary'] = $value->salary;
                            $index++;
                    } 
//                      echo "<pre>";  print_r($batPlayers); echo "</pre>";die;
//                      $objGamePlayerModal->bulkUpdateSalaryOfEachPlayer($batPlayers);
        break;
        
            case 'NBA' : $sportId = 3;
                    
                    $result = $objUserContestModel->playerStats($sportId);
//-----------------------json decode team stats of teams---------------------------------------------        
                    foreach ($result as $key=>$value){
                        $teams[$key]['team_stats'] = json_decode($value['team_stats']); 
                        $teams[$key]['team_id']    = $value['team_id'];
                    } 
                    $index = 0;
                    foreach($teams as $key=>$value):
                        $teamId = $value['team_id'];
                        $playerScore = $value['team_stats'][0]->player;
                        $playerPts   = $value['team_stats'][1]->player;
                        foreach ($playerScore as $pskey=>$psvalue):
                            $eachPlayer[$index]['id'] = $psvalue->id;
                            $eachPlayer[$index]['team_id'] = $teamId;
                            $eachPlayer[$index]['points'] = $psvalue->points_per_game;
                            $eachPlayer[$index]['rebounds'] = $psvalue->rebounds_per_game;
                            $eachPlayer[$index]['assist'] = $psvalue->assists_per_game;
                            $eachPlayer[$index]['steals'] = $psvalue->steals_per_game;
                            $eachPlayer[$index]['block'] = $psvalue->blocks_per_game;
                            $eachPlayer[$index]['turnovers'] = $psvalue->turnovers_per_game;
                            $eachPlayer[$index]['three_pointer'] = $playerPts[$pskey]->three_point_made_per_game;
                            $index++;         
                        endforeach;
                   endforeach;
                        
                   foreach($eachPlayer as $key=>$value):
                       $x1 = ($value['points'])*(1);
                       $x2 = ($value['rebounds'])*(1.25);
                       $x3 = ($value['assist'])*(1.5);
                       $x4 = ($value['steals'])*(2);
                       $x5 = ($value['block'])*(2);
                       $x6 = ($value['turnovers'])*(-0.5);
                       $x7 = ($value['three_pointer'])*(0.5);
                       $pts = $x1+$x2+$x3+$x4+$x5+$x6+$x7;
                       $eachPlayer[$key]['pts'] = $pts;
                       $pointsArray[] = $pts;
                   endforeach;
                   
                   
//-------------find constabts Z6=average of points, z4=deviation of points--------
                    $Z6 = (array_sum($pointsArray))/(count($pointsArray));
//------------to calculate the deviation-----------------------
                    $carry = 0.0;
                    foreach ($pointsArray as $val):
                        $d = ((double) $val) - $Z6;
                        $carry += $d * $d;
                    endforeach;
                    $Z4 = sqrt($carry / (count($pointsArray)));

                    foreach($eachPlayer as $key=>$value):
                        $Z_score = (($value['pts'])-$Z6)/$Z4;
                        if($Z_score<0):
                            $Z_score = 0;
                        endif;
                        $eachPlayer[$key]['z_score'] = $Z_score;
                        $adjusted[] = $Z_score;
                    endforeach;

                    $adjSum = array_sum($adjusted);
                    $ac6 = 3216086099; $ad6 = 660000;//for now taking dummy values here, need client clearification

                    foreach ($eachPlayer as $key=>$value):
                        $actuals = (($value['z_score'])/$adjSum)*$ac6; //echo $actuals."</br>";
                        $actuals = floor($actuals);
                        $eachPlayer[$key]['actuals'] = $actuals;
                        $capHit = ($actuals/$ac6)*$ad6;
                        $salary = round($capHit,-2);
                            if($salary<2000):
                                $salary = 2000;
                            endif;
                        $eachPlayer[$key]['salary'] = $salary;
                    endforeach;
                 $objGamePlayerModal->bulkUpdateSalaryOfEachPlayer($eachPlayer);
                break;
        endswitch;
    }
    
     function generateRandomString($length = 10) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, strlen($characters) - 1)];
            }
            return $randomString;
        }
}