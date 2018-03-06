<?php
/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class Contest_LineupController extends Zend_Controller_Action {
	
    public function init() {

    }
    public function getLineUpAction(){        
      
    }
    
    public function playerStatsAction()
	{       
        $this->_helper->layout()->disableLayout();
        $objParser = Engine_Utilities_GameXmlParser::getInstance();
        $objPlayerStatsModel   = Application_Model_PlayerStats::getInstance();
        $objGamePlayersDetails = Application_Model_GamePlayersDetails::getInstance();
        $objGamePlayers = Application_Model_GamePlayers::getInstance();
		
        if ($this->getRequest()->isPost()) {       
            $playerId   = $this->getRequest()->getParam('pid');
            $teamId     = $this->getRequest()->getParam('tid');
            $sportsId   = $this->getRequest()->getParam('sprid');
            $playerPos  = $this->getRequest()->getParam('pos');
            $playerCode = $this->getRequest()->getParam('pcode');

            $teams = $this->getRequest()->getParam('teams');
            $modalfor = $this->getRequest()->getParam('modal');
            if(isset($modalfor) && $modalfor === "draft"){
                $this->view->draft = 1;
            }
            $this->view->sportsId   = $sportsId; 
            $this->view->playerPos  = $playerPos;
            $this->view->playerCode = $playerCode;
            $this->view->playerId = $playerId;
            $this->view->teams = $teams;
			$this->dstStats = array();

            $gameplayers = array();

            $gameplayers = $objGamePlayers->getByIdAndSport($playerId,$sportsId);
				//echo "<pre>"; print_r($gameplayers); die;
                $teamCode = $gameplayers['plr_team_code'];
                $plrValue = $gameplayers['plr_value'];
                $plrDetails = $gameplayers['plr_details'];
                $players = json_decode($gameplayers['plr_details'],true);
                $this->view->name = $players['name'];
                $playerName = $players['name'];
                $teamcode = $players['team_code'];
                $this->view->teamCode = $teamcode;
                $this->view->team_id = $teamId;
                $this->view->plrValue = $plrValue;
                $this->view->inj_status = $gameplayers['injury_status'];
                $this->view->inj_reason =  $gameplayers['injury_reason'];       
                $lastStats = json_decode($gameplayers['last_stats'],true);
                $this->view->fpts = $gameplayers['fpts'];
                $playerLastStats = array();
            
            $teamStats = $objPlayerStatsModel->getPlayerStats($teamId,$sportsId);
			//echo "<pre>"; print_r($teamStats); die;
            if($teamStats){
                $teamStat = json_decode($teamStats['team_stats'],true);
                $this->view->team_name = $teamStats['team_name'];
               
                /*
                 * Developer    : Vivek chaudhari
                 * Date         : 29/09/2014
                 * Description  : player details and stats in player details popup
                 */
//--------------------------------------------------start-----------------------------------------------------------------
                $playerStat = array();
				// NFL is start here				
                if($sportsId == 1){  
                    $searchInKey = 'id';
                    foreach($teamStat as $statkey=>$statVal){ 
                        $playerStatKey = $this->searchInMultidimensionSearch($playerId, $statVal['player'], $searchInKey); 
                        if(isset($playerStatKey)){  
                           $playerName = $statVal['player'][$playerStatKey]['name'];
                           $playerStat[$statVal['name']] = $statVal['player'][$playerStatKey];
						   //echo "<pre>"; print_r($playerStat); die;
                         }
                    } 
					if(!empty($lastStats)){
						foreach($lastStats as $lsKey=>$lsValue){
							if($lsKey === "Passing"){
								foreach($lsValue as $passValue){
									$playerLastStats['Passing']['yards'] = $passValue['yards']; 
									$playerLastStats['Passing']['yards_per_game'] = $passValue['yards'];
									$playerLastStats['Passing']['interceptions'] = $passValue['interceptions'];
									$playerLastStats['Passing']['passing_touchdowns'] = $passValue['passing_touch_downs'];
									$passPlayed = explode("/",$passValue['comp_att']);
									$playerLastStats['Passing']['completions'] = $passPlayed[0];
									$playerLastStats['Passing']['passing_attempts'] = $passPlayed[1];
									$playerLastStats['Passing']['longest_pass'] = $passValue['yards'];
									$playerLastStats['Passing']['completion_pct'] = $passValue['average'];
									$playerLastStats['Passing']['quaterback_rating'] = $passValue['rating'];
								}
							}
							if($lsKey === "kicking"){
								foreach($lsValue as $recValue){
									 $playerLastStats['kicking']['ext_points'] = $recValue['extra_point'];
									 
									 $playerLastStats['kicking']['39yards'] = $recValue['field_goals_from_1_19_yards'] +  $recValue['field_goals_from_20_29_yards']+ $recValue['field_goals_from_30_39_yards'];
									 
									 
									 $playerLastStats['kicking']['49yards'] = $recValue['field_goals_from_40_49_yards'];
									 
									  $playerLastStats['kicking']['50yards'] = $recValue['field_goals_from_50_yards'];   
								}
							}
							if($lsKey === "defensive"){
								
								 foreach($lsValue as $recValue){
										//echo "<pre>"; print_r($recValue); die; 
									$playerLastStats['Defense']['tackles'] = $recValue['tackles'];
									 
									$playerLastStats['Defense']['unassisted_tackles'] = $recValue['unassisted_tackles'];
									 
									 
									$playerLastStats['Defense']['sacks'] = $recValue['field_goals_from_40_49_yards'];
									 
									$playerLastStats['Defense']['tfl'] = $recValue['tfl']; 
									
									$playerLastStats['Defense']['passes_defended'] = $recValue['passes_defended']; 
									
									$playerLastStats['Defense']['qb_hts'] = $recValue['qb_hts']; 
									
									$playerLastStats['Defense']['interceptions_for_touch_downs'] = $recValue['interceptions_for_touch_downs'];
								}  
							} 						
							if($lsKey === "Receiving"){
								foreach($lsValue as $recValue){
									 $playerLastStats['Receiving']['receiving_yards'] = $recValue['yards'];
									 $playerLastStats['Receiving']['yards_per_game'] = $recValue['yards'];
									 $playerLastStats['Receiving']['receptions'] = $recValue['total_receptions'];
									 $playerLastStats['Receiving']['receiving_touchdowns'] = $recValue['receiving_touch_downs'];
									 $playerLastStats['Receiving']['fumbles'] = 0;
									 $playerLastStats['Receiving']['fumbles_lost'] = 0;
									 
									 $playerLastStats['Receiving']['yards_per_reception_avg'] = $recValue['average'];
									  $playerLastStats['Receiving']['longest_reception'] = $recValue['longest_reception'];
								}
							}
							if($lsKey === "Rushing"){
								foreach($lsValue as $rushValue){ 
									$playerLastStats['Rushing']['rushing_attempts'] = $rushValue['total_rushes'];
									$playerLastStats['Rushing']['yards'] = $rushValue['yards'];
									$playerLastStats['Rushing']['yards_per_game'] = $rushValue['yards'];
									$playerLastStats['Rushing']['fumbles'] = 0;
									$playerLastStats['Rushing']['fumbles_lost'] = 0;
									$playerLastStats['Rushing']['rushing_touchdowns'] = $rushValue['rushing_touch_downs'];
									$playerLastStats['Rushing']['longest_rush'] = $rushValue['longest_rush'];
									
								}
							}
							if($lsKey === "fumbles"){
								foreach($lsValue as $fumValue){
									if(isset($playerLastStats['Rushing'])){
										$playerLastStats['Rushing']['fumbles'] = $fumValue['total'];
										$playerLastStats['Rushing']['fumbles_lost'] = $fumValue['lost'];
									}else if(isset($playerLastStats['Receiving'])){
										$playerLastStats['Receiving']['fumbles'] = $fumValue['total'];
										$playerLastStats['Receiving']['fumbles_lost'] = $fumValue['lost'];
									}
								}
							}
						}
						if(!empty($playerLastStats)){
							$pLastStats = $objParser->calculateFppgNFL($playerLastStats);
						}
					}
				
				
                    if(!empty($playerStat)){
                       $seasonStat = $objParser->calculateFppgNFL($playerStat);
                    } 
                    $plrImage = $objGamePlayersDetails->getPlayerImage($sportsId,$playerName);
                                        
                    if(!empty($plrImage)){
                        $playerImage = $plrImage['player_img_link'];
                    }else{
                        if($sportsId == 1){
                            $playerImage = 'images/nfl_default.png'; 
                        }else if($sportsId == 2){
                            $playerImage = 'images/mlb_default.png';     
                        }else if($sportsId == 3){
                            $playerImage = 'images/nba_default.png';         
                        }else if($sportsId == 4){
                            $playerImage = 'images/nhl_default.png';             
                        }
                    }
                    $this->view->image = 'images/NFL2/'.$playerId.'.png'; //$playerImage;

					if($playerPos === "DST"){
						$this->view->image = 'images/nfl_default.png'; 
					}

					$playstats = array();
					if(!empty($pLastStats)){
						// echo "<pre>"; print_r($pLastStats); die;	
						$playstats['last_stats'] = $pLastStats;
					}
					if(!empty($seasonStat)){
                // echo "<pre>"; print_r($seasonStat); die;						
						$playstats['season_stats'] = $seasonStat;
					}
					if(!empty($playstats)){			
						$this->view->playerStat = $playstats;
					}                                            
                }
  //......................NFL is end here ........................	
				
  //....................MLB is start here..........................
                if($sportsId == 2){ 
                    $searchInKey = 'id';
                    foreach($teamStat as $statkey=>$statVal){ 
                        if(isset($statVal['team']['player'])){
                            $playerStatKey = $this->searchInMultidimensionSearch($playerId, $statVal['team']['player'], $searchInKey); 
                            if(isset($playerStatKey)){ 
                                $playerName = $statVal['team']['player'][$playerStatKey]['name'];
                                $playerStat[$statVal['name']] = $statVal['team']['player'][$playerStatKey];
                            }
                        } 
                    } 
                    $plrImage = $objGamePlayersDetails->getPlayerImage($sportsId,$playerName);
                    if(!empty($plrImage)){
                       $playerImage = $plrImage['player_img_link'];
                       
                    }else{
						$playerImage = 'images/mlb_default.png';   
                    }
                    $this->view->image = 'images/mlb/'.$playerId.'.png';
                    if($playerStat){
                        $playerFppgStats =  $objParser->calculateFppgMLB($playerStat);
                        if(!empty($playerFppgStats)){
                            $this->view->playerStat = $playerFppgStats;
                        }
                    }
                    if(isset($lastStats)){
                        foreach($lastStats as $type=>$dval){
                            if($type=="pitchers"){
                                foreach($dval as $fval){$lastStatsDetails = $fval;}
                                break;
                            }else{
                                foreach($dval as $fval)
								{
									$lastStatsDetails = $fval;
								} 
                            }
                        }
                    }
                    if(isset($lastStatsDetails)){
                        $this->view->lastStat = $lastStatsDetails;
                    }
                    
                }
    //....................MLB is end here.......................... 
	//....................NBA is start here..........................
                if($sportsId == 3){ 
                    $plrImage = $objGamePlayersDetails->getPlayerImage($sportsId,$playerName);
                    if(!empty($plrImage)){
                       $playerImage = $plrImage['player_img_link'];
                    }else{
                        if($sportsId == 1){
                            $playerImage = 'images/nfl_default.png'; 
                        }else if($sportsId == 2){
                            $playerImage = 'images/mlb_default.png';     
                        }else if($sportsId == 3){
                            $playerImage = 'images/nba_default.png';         
                        }else if($sportsId == 4){
                            $playerImage = 'images/nhl_default.png';             
                        }
                    }
                    $this->view->image = 'images/NBA/'.$playerId.'.png';
                    $searchInKey = 'id';
                    $seasonStats = array();
                    foreach($teamStat as $statkey=>$statVal){ 
                        $playerStatKey = $this->searchInMultidimensionSearch($playerId, $statVal['player'], $searchInKey); 
                       
                        if(isset($playerStatKey)){
                            if(!empty($seasonStats)){
                                $seasonStats = array_merge($seasonStats,$statVal['player'][$playerStatKey]);
                            }else{
                                $seasonStats =  $statVal['player'][$playerStatKey];
                            }
                            $playerName = $statVal['player'][$playerStatKey]['name'];
                            $playerStat[$statVal['name']] = $statVal['player'][$playerStatKey];
                        }
                    }
                    if(isset($gameplayers['last_stats'])){
                        $decodeStats = json_decode($gameplayers['last_stats'],true);
                        if(!empty($decodeStats)){
                            foreach($decodeStats as $dval){
                                foreach($dval as $fval){
                                    $lastStats = $fval;
                                }
                            }
                        }
                    }
                    
                    if(isset($lastStats)){
                        $lastStats = $objParser->calculateFppgNBA($lastStats);
                        $this->view->lastStat = $lastStats;
                    }
                    if(isset($seasonStats)){
                        $seasonStats = $objParser->calculateFppgNBA($seasonStats);
                    }
                    $this->view->playerStat = $seasonStats;
                }
	//....................NBA is end here..........................	
	
	//....................NHL is start here..........................			
				
                if($sportsId == 4){  
                    foreach($teamStat as $statkey=>$statVal){ 
                        $playerStatKey = $this->searchInMultidimensionSearch($playerId, $statVal['player'], 'id'); 
                        if(isset($playerStatKey)){
                            $playerName = $statVal['player'][$playerStatKey]['name'];
                            $playerStat = $statVal['player'][$playerStatKey];
                        }
                    } 
                    if(!empty($lastStats)){
						foreach($lastStats as $lval){
							foreach($lval as $fval){
							   $lastStat= $fval;
							}
						}
					}
                   
                    if(isset($lastStat)){
                       $lastStat=$objParser->calculateFppgNHL($lastStat);
                    }
                    if(isset($playerStat)){
                       $playerStat=$objParser->calculateFppgNHL($playerStat);
                    }
                    $plrImage = $objGamePlayersDetails->getPlayerImage($sportsId,$playerName);
                    if(!empty($plrImage)){
                       $playerImage = $plrImage['player_img_link'];
                    }else if($sportsId == 4){
                        $playerImage = 'images/nhl_default.png';             
                    }
                    
                    $this->view->image = 'images/NHL/'.$playerId.'.png';  // $playerImage;
                    if(isset($lastStat)){
                        $this->view->lastStat = $lastStat;
                    }
                    if(isset($playerStat)){
                        $this->view->playerStat = $playerStat;
                    }                                    
                }
//....................NHL is end here..........................					
            }
        }
    }
    
    function searchInMultidimensionSearch($searchVal, $array, $searchKey) {
        foreach ($array as $key => $val) { 
            if ($val[$searchKey] === $searchVal) { 
                return $key;
            }
        } 
        return null;
    }
    
    public function playerDetailsAction(){       
        $this->_helper->layout()->disableLayout();
        $objParser = Engine_Utilities_GameXmlParser::getInstance();
        $objPlayerStatsModel   = Application_Model_PlayerStats::getInstance();
        $objGamePlayersDetails = Application_Model_GamePlayersDetails::getInstance();
        $objGamePlayers = Application_Model_GamePlayers::getInstance();
        if ($this->getRequest()->isPost()) {        
            $playerId   = $this->getRequest()->getParam('pid');
            $playerDetails = $objGamePlayers->getPlayerDetailsByPlayerId($playerId);
            //$playerDetails = json_decode($playerDetails['plr_details']);
            
            $teamDetails = $objPlayerStatsModel->getTeamDetailsTeamName($playerDetails->team_name);
            $teamId     = $teamDetails['team_id'];
            $sportsId   = $teamDetails['sports_id'];
            $playerPos  = $playerDetails->position;
            $playerCode = $playerDetails->pos_code;
            $this->view->sportsId   = $sportsId; 
            $this->view->playerPos  = $playerPos;
            $this->view->playerCode = $playerCode;
            $this->view->playerId = $playerId;
            //$this->view->teams = $teams;

            $gameplayers = array();
            $gameplayers = $objGamePlayers->getPlayerByPlayerId($playerId);
            $teamCode = $gameplayers['plr_team_code'];
            $plrValue = $gameplayers['plr_value'];
            $plrDetails = $gameplayers['plr_details'];
            $players = json_decode($gameplayers['plr_details'],true);
            $this->view->name = $players['name'];
            $playerName = $players['name'];
            $teamcode = $players['team_code'];
            $this->view->teamCode = $teamcode;
            $this->view->plrValue = $plrValue;
            $this->view->fppg = $gameplayers['fppg'];
            $this->view->fpts = $gameplayers['fpts'];
            $lastStats = json_decode($gameplayers['last_stats'],true);
            $playerLastStats = array();
            
            $teamStats = $objPlayerStatsModel->getPlayerStats($teamId,$sportsId);
            
            if($teamStats){                
                $teamStat = json_decode($teamStats['team_stats'],true);
                $this->view->team_name = $teamStats['team_name'];

	// ....................NFL satrt here................
	            $playerStat = array();
                if($sportsId == 1){                     
                    $searchInKey = 'id';                  
                    foreach($teamStat as $statkey=>$statVal){ 
                       $playerStatKey = $this->searchInMultidimensionSearch($playerId, $statVal['player'], $searchInKey); 
                       if(isset($playerStatKey)){
                           $playerName = $statVal['player'][$playerStatKey]['name'];
                           $playerStat[$statVal['name']] = $statVal['player'][$playerStatKey];
                         }
                    }
                   
                  if(!empty($lastStats)){
                    foreach($lastStats as $lsKey=>$lsValue){
						 
                        if($lsKey === "Passing"){
                            foreach($lsValue as $passValue){
                                $playerLastStats['Passing']['yards'] = $passValue['yards']; 
                                $playerLastStats['Passing']['yards_per_game'] = $passValue['yards'];
                                $playerLastStats['Passing']['interceptions'] = $passValue['interceptions'];
                                $playerLastStats['Passing']['passing_touchdowns'] = $passValue['passing_touch_downs'];
                                $passPlayed = explode("/",$passValue['comp_att']);
                                $playerLastStats['Passing']['completions'] = $passPlayed[0];
                                $playerLastStats['Passing']['passing_attempts'] = $passPlayed[1];
                                $playerLastStats['Passing']['longest_pass'] = $passValue['yards'];
                                $playerLastStats['Passing']['completion_pct'] = $passValue['average'];
                                $playerLastStats['Passing']['quaterback_rating'] = $passValue['rating'];
                            }
                        }
                        if($lsKey === "Receiving"){
                            foreach($lsValue as $recValue){
                                 $playerLastStats['Receiving']['receiving_yards'] = $recValue['yards'];
                                 $playerLastStats['Receiving']['yards_per_game'] = $recValue['yards'];
                                 $playerLastStats['Receiving']['receptions'] = $recValue['total_receptions'];
                                 $playerLastStats['Receiving']['receiving_touchdowns'] = $recValue['receiving_touch_downs'];
                                 $playerLastStats['Receiving']['fumbles'] = 0;
                                 $playerLastStats['Receiving']['fumbles_lost'] = 0;
                                 
                                 $playerLastStats['Receiving']['yards_per_reception_avg'] = $recValue['average'];
                                  $playerLastStats['Receiving']['longest_reception'] = $recValue['longest_reception'];
                                 
                            }
                        }
						
						
						
                        if($lsKey === "Rushing"){
                            foreach($lsValue as $rushValue){ 
                                $playerLastStats['Rushing']['rushing_attempts'] = $rushValue['total_rushes'];
                                $playerLastStats['Rushing']['yards'] = $rushValue['yards'];
                                $playerLastStats['Rushing']['yards_per_game'] = $rushValue['yards'];
                                $playerLastStats['Rushing']['fumbles'] = 0;
                                $playerLastStats['Rushing']['fumbles_lost'] = 0;
                                $playerLastStats['Rushing']['rushing_touchdowns'] = $rushValue['rushing_touch_downs'];
                                $playerLastStats['Rushing']['longest_rush'] = $rushValue['longest_rush'];
                                
                            }
                        }
                        if($lsKey === "fumbles"){
                            foreach($lsValue as $fumValue){
                                if(isset($playerLastStats['Rushing'])){
                                    $playerLastStats['Rushing']['fumbles'] = $fumValue['total'];
                                    $playerLastStats['Rushing']['fumbles_lost'] = $fumValue['lost'];
                                }else if(isset($playerLastStats['Receiving'])){
                                    $playerLastStats['Receiving']['fumbles'] = $fumValue['total'];
                                    $playerLastStats['Receiving']['fumbles_lost'] = $fumValue['lost'];
                                }
                            }
                        }
                    }
                    if(!empty($playerLastStats)){
                        $pLastStats = $objParser->calculateFppgNFL($playerLastStats);
                    }
                }
                   if(!empty($playerStat)){
                       $seasonStat = $objParser->calculateFppgNFL($playerStat);
                   } 
                  
                    $plrImage = $objGamePlayersDetails->getPlayerImage($sportsId,$playerName);                    
                        if(!empty($plrImage)){
                            $playerImage = $plrImage['player_img_link'];
                        }else{
                            if($sportsId == 1){
                            $playerImage = 'images/nfl_default.png'; 
                            }else if($sportsId == 2){
                            $playerImage = 'images/mlb_default.png';     
                            }else if($sportsId == 3){
                            $playerImage = 'images/nba_default.png';         
                            }else if($sportsId == 4){
                            $playerImage = 'images/nhl_default.png';             
                            }
                        }
                        
                        $this->view->image = $playerImage;
//                        $this->view->name = $playerName;
                        $playstats = array();
                        if(!empty($pLastStats)){
                            $playstats['last_stats'] = $pLastStats;
                        }
                        if(!empty($seasonStat)){
                            $playstats['season_stats'] = $seasonStat;
                        }
                        if(!empty($playstats)){
                            $this->view->playerStat = $playstats;
                        }
                        
//=======================================================end===================================================================                        
                }
                
                if($sportsId == 2){ // MLB
                    $searchInKey = 'id';
                    foreach($teamStat as $statkey=>$statVal){ 
                        if(isset($statVal['team']['player'])){
                            $playerStatKey = $this->searchInMultidimensionSearch($playerId, $statVal['team']['player'], $searchInKey); 
                              if(isset($playerStatKey)){ 
                                $playerName = $statVal['team']['player'][$playerStatKey]['name'];
                                $playerStat[$statVal['name']] = $statVal['team']['player'][$playerStatKey];
                              }
                         } 
                     } 
                      $plrImage = $objGamePlayersDetails->getPlayerImage($sportsId,$playerName);
                    if(!empty($plrImage)){
                       $playerImage = $plrImage['player_img_link'];
                    }else{
                       if($sportsId == 1){
                            $playerImage = 'images/nfl_default.png'; 
                            }else if($sportsId == 2){
                            $playerImage = 'images/mlb_default.png';     
                            }else if($sportsId == 3){
                            $playerImage = 'images/nba_default.png';         
                            }else if($sportsId == 4){
                            $playerImage = 'images/nhl_default.png';             
                            } 
                    }
                    $this->view->image = $playerImage;
                    if($playerStat){
                       $playerFppgStats =  $objParser->calculateFppgMLB($playerStat);
                       if(!empty($playerFppgStats)){
                           $this->view->playerStat = $playerFppgStats;
                       }
                    }  
                             
                       if(isset($gameplayers['last_stats'])){
                        $decodeStats = json_decode($gameplayers['last_stats'],true);
                        if(!empty($decodeStats)){
                        foreach($decodeStats as $dval){
                            foreach($dval as $fval){
                                $lastStats = $fval;
                            }
                        }}
                    }
                    
                    if(isset($lastStats)){
                        $this->view->lastStat = $lastStats;
                    }
                    
                  }
                
               if($sportsId == 3){ // NBA
                    $plrImage = $objGamePlayersDetails->getPlayerImage($sportsId,$playerName);
                    if(!empty($plrImage)){
                       $playerImage = $plrImage['player_img_link'];
                    }else{
                      if($sportsId == 1){
                            $playerImage = 'images/nfl_default.png'; 
                            }else if($sportsId == 2){
                            $playerImage = 'images/mlb_default.png';     
                            }else if($sportsId == 3){
                            $playerImage = 'images/nba_default.png';         
                            }else if($sportsId == 4){
                            $playerImage = 'images/nhl_default.png';             
                            }
                    }
                    $this->view->image = $playerImage;
                     
                    $searchInKey = 'id';
                    $seasonStats = array();
                    foreach($teamStat as $statkey=>$statVal){ 
                       $playerStatKey = $this->searchInMultidimensionSearch($playerId, $statVal['player'], $searchInKey); 
                       
                       if(isset($playerStatKey)){
                           if(!empty($seasonStats)){
                              $seasonStats = array_merge($seasonStats,$statVal['player'][$playerStatKey]);
                           }else{
                               $seasonStats =  $statVal['player'][$playerStatKey];
                           }
                           $playerName = $statVal['player'][$playerStatKey]['name'];
                           $playerStat[$statVal['name']] = $statVal['player'][$playerStatKey];
                         }
                    }
                    if(isset($gameplayers['last_stats'])){
                        $decodeStats = json_decode($gameplayers['last_stats'],true);
                        if(!empty($decodeStats)){
                        foreach($decodeStats as $dval){
                            foreach($dval as $fval){
                                $lastStats = $fval;
                            }
                        }}
                    }
                    
                    if(isset($lastStats)){
//                        $lastStats = $objParser->calculateFppgNBA($lastStats);
//                        echo "<pre>"; print_r($lastStats); echo "</pre>"; die;
                        $this->view->lastStat = $lastStats;
                    }
//                    echo $playerId;
//                    echo "<pre>"; print_r($teamStats); echo "</pre>"; die;
//                    if(isset($seasonStats)){
//                        $seasonStats = $objParser->calculateFppgNBA($seasonStats);
//                    }
                   $this->view->playerStat = $seasonStats;
                   
                }
              
               if($sportsId == 4){ //NHL
                   
                  foreach($teamStat as $statkey=>$statVal){ 
                       $playerStatKey = $this->searchInMultidimensionSearch($playerId, $statVal['player'], 'id'); 
                       if(isset($playerStatKey)){
                           $playerName = $statVal['player'][$playerStatKey]['name'];
                           $playerStat = $statVal['player'][$playerStatKey];
                         }
                   } 
                   if(!empty($lastStats)){
                   foreach($lastStats as $lval){
                       foreach($lval as $fval){
                           $lastStat= $fval;
                       }
                   }}
//                   echo $playerId;
                   
//                   if(isset($lastStat)){
//                       $lastStat=$objParser->calculateFppgNHL($lastStat);
//                   }
//                   echo "<pre>"; print_r($lastStat); echo "</pre>";// die;
                   if(isset($playerStat)){
                       $playerStat=$objParser->calculateFppgNHL($playerStat);
                   }
//                   echo "<pre>"; print_r($playerStat); echo "</pre>";// die;
                   $plrImage = $objGamePlayersDetails->getPlayerImage($sportsId,$playerName);
                    if(!empty($plrImage)){
                       $playerImage = $plrImage['player_img_link'];
                    }else if($sportsId == 4){
                        $playerImage = 'images/nhl_default.png';             
                    }
                    
                    $this->view->image = $playerImage;
                    if(isset($lastStat)){
                        $this->view->lastStat = $lastStat;
                    }
                    if(isset($playerStat)){
                        $this->view->playerStat = $playerStat;
                    }
               }
            }
        }
    }

}
