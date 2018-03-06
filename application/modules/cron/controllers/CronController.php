<?php
/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';
require_once '../vendor/autoload.php';

class Cron_CronController extends Zend_Controller_Action {

        public $teams;
        public $serachurl;
        public $teamcode;

    public function init() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    public function playersStatsAction(){
        $sportsName = $this->getRequest()->getParam('sportsname'); 
       
        $objSportsModel = Application_Model_Sports::getInstance();
        $objParser = Engine_Utilities_GameXmlParser::getInstance();
        $objPlayerStatModel = Application_Model_PlayerStats::getInstance();
        
        $sportsID = $objSportsModel->getSportsDetailsByName($sportsName);
		
        if(isset($sportsID) && $sportsName!=""){
            $statsData = $objParser->getPlayerStats($sportsName);
            if(!empty($statsData)){              
                $objPlayerStatModel->insertPlayersStats($statsData,$sportsID['sports_id']);
                $this->playerFppgUpdate($sportsID['sports_id'],$statsData);
            }
        }else{
            echo $sportsName." sport is not Active, please Active it from admin panel";
        }        
    }
	
	/**
     *  Dev :	Prince kumar dwivedi
     *  Desc: 	Update player salary according to fppg 
    **/
	 
	public function updatePlayerSalaryAction(){
		$objUserLineupModel = Application_Model_UserLineup::getInstance();
		$objModelContest = Application_Model_Contests::getInstance();
		$objSportsModel = Application_Model_Sports::getInstance();
		$objGamePlayers    = Application_Model_GamePlayers::getInstance();
		$objLineupModel     = Application_Model_Lineup::getInstance();
		$objParser       = Engine_Utilities_GameXmlParser::getInstance();
		$objSettingsModel = Admin_Model_Settings::getInstance();
		$PlayerSettings = $objSettingsModel->getSettingsDeatils();		//echo date('Y-m-d H:i:s'); die;
		
		if(!empty($PlayerSettings['player_salary_setting'])){
			
			$PlayerSettings = unserialize($PlayerSettings['player_salary_setting']);
			
			if(!empty($PlayerSettings['max_salary_amount']) && !empty($PlayerSettings['min_salary_amount']) && !empty($PlayerSettings['plr_fppg']) && !empty($PlayerSettings['salary_amount']) && ($PlayerSettings['status']==1)){
				
				$sportsName = strtoupper($this->getRequest()->getParam('sportsname')); 
				$sportsID = $objSportsModel->getSportsDetailsByName($sportsName);
				/** get all contests which end date is today   getMatchIdByDate **/
				$today_contests = $objModelContest->getMatchIdByEndDate($sportsID);
				$allPlayerIds = array();
				if(!empty($today_contests))	{
					foreach($today_contests as $con){
						$result = $objUserLineupModel->getLineupsByContestId($con['contest_id']);
						if(!empty($result)){					
							foreach($result as $rkey=>$rval){
								$playerData  = $objLineupModel->getPlayerIdsByLineupId($rval['lineup_id']);
								$pIds         = json_decode($playerData['player_ids'],true);
								$allPlayerIds =  array_merge($allPlayerIds,$pIds);					
							}
						}		
					}
					//echo "<pre>"; print_r($allPlayerIds); die;
					if(!empty($allPlayerIds)) {
						$final_player_array = array_unique($allPlayerIds);
						$inc = 0;
						$save_array = array();
						foreach($final_player_array as $player_id){
							$player_details = $objGamePlayers->getPlayerByPlayerId($player_id);
							
							if(!empty($player_details)){
								
								$new_fppg = $player_details['fppg']; 
								$old_fppg = $player_details['old_fppg'];
								$total_fppg = $new_fppg - $old_fppg;
								//echo "<pre>".$total_fppg;
								if((($total_fppg!=0)) && (($total_fppg>=$PlayerSettings['plr_fppg']) || ($total_fppg<=-($PlayerSettings['plr_fppg'])))){
									$mul = round($total_fppg/$PlayerSettings['plr_fppg']);
									$salary = $mul * $PlayerSettings['salary_amount'];
									$total_salary = $salary + $player_details['plr_value'];
									
									if($total_salary >= $PlayerSettings['max_salary_amount']){						
										$save_array[$inc]['plr_id'] = $player_details['plr_id'];
										$save_array[$inc]['old_fppg'] = $player_details['fppg'];
										$save_array[$inc]['plr_value'] = $PlayerSettings['max_salary_amount'];
									}elseif($total_salary < $PlayerSettings['min_salary_amount']){
										$save_array[$inc]['plr_id'] = $player_details['plr_id'];
										$save_array[$inc]['old_fppg'] = $player_details['fppg'];
										$save_array[$inc]['plr_value'] = $PlayerSettings['min_salary_amount'];
									}else{
										$save_array[$inc]['plr_id'] = $player_details['plr_id'];
										$save_array[$inc]['old_fppg'] = $player_details['fppg'];
										$save_array[$inc]['plr_value'] = $salary + $player_details['plr_value'];
									}
								}
								$inc++;
							}
							
						}
						//echo "<pre>"; print_r($save_array); die;
						if(!empty($save_array)){
							$update_result = $objGamePlayers->updatePlayersSalary($save_array,$sportsID['sports_id']);
							if($update_result){
								return true;
							}
						}
						
						return false;				
					}			
				}
			}			
		}	 		
	}
	
	/**
     *  Dev :Prince kumar dwivedi
     *  Desc: Get injured player of every team and update into database
     */
    
	public function fetchInjuredPlayersAction()
	{
		$objGamePlayers  = Application_Model_GamePlayers::getInstance(); 
		$objGamePlayersDetails  = Application_Model_GamePlayersDetails::getInstance(); 
		$team = $objGamePlayersDetails->getAllTeamCode();
		//echo "<pre>";print_r($team); die;
		foreach($team as $value){
			$teamcode = $value['plr_team_code'];
			if($teamcode  == 'nwe'){
				$teamcode = 'ne';
			}
				
		    //$xml = simplexml_load_file('http://www.goalserve.com/getfeed/15e8f1be05d64b7799bdc5e133ccaaa6/football/'.$teamcode.'_injuries');
			
			$url = 'http://www.goalserve.com/getfeed/15e8f1be05d64b7799bdc5e133ccaaa6/football/'.$teamcode.'_injuries';
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$data = curl_exec($curl);

			$xml = simplexml_load_string($data);
			
			if(!empty($xml)){ 
				foreach($xml as $keys=>$player){
					
					$Arr = (array)$player;
					//echo "<pre>"; print_r($Arr);
					$player_id   = $Arr['@attributes']['player_id']; 
					$injury_code   = $Arr['@attributes']['status']; 
					$description = $Arr['@attributes']['description']; 
					$objGamePlayers->updatePlayerInjuryStatus($player_id,$description,$injury_code);
				}
			}
		}  
	} 
	
    /**
     *  Dev : Vivek Chaudhari
     *  Desc: Get game player rosters and save to the table
     */
    public function playerListAction(){
        $sportsName = $this->getRequest()->getParam('sportsname'); 
        
        $objSportsModel = Application_Model_Sports::getInstance();
        $objParser = Engine_Utilities_GameXmlParser::getInstance();
        
        $sportsID = $objSportsModel->getSportsDetailsByName($sportsName);
		//echo "<pre>"; print_r($objParser); echo "</pre>";die;
        if(isset($sportsID) && $sportsName!=""){
            $rosters = $objParser->getGamePlayers($sportsName);
            
            if($rosters){
                $objGamePlayers = Application_Model_GamePlayers::getInstance();
                $objGamePlayers->bulkInsert($rosters,$sportsID['sports_id']);
            }
            echo "===========Player List=============";
            print "<pre>";
            print_r($rosters);
            print "</pre>";
        }else{
            echo $sportsName." sport is not Active, please Active it from admin panel";
        }
    }
 
    
    /**
     * Desc : get current date gamelist for selected game
     * @param <string> $sportsName hold given sportsname
     */
    public function getGameListAction(){
        
        $objParser      = Engine_Utilities_GameXmlParser::getInstance();
        $objContest     = Application_Model_Contests::getInstance();
        $objSportsModel = Application_Model_Sports::getInstance();
        
        $sportsName = $this->getRequest()->getParam('sportsname');
      
        $sportsID = $objSportsModel->getSportsDetailsByName($sportsName);//get sports id by name
         
        if(isset($sportsID['sports_id'])){ // Add condition (Manoj)
            
            $inactiveContest = $objContest->getInActiveSportContest($sportsID['sports_id']);// get inactive game date by sports id
            
            $gameResponse = $objParser->getGameLists($sportsName);// get game schedule by game name

            if(!empty($inactiveContest) && !empty($gameResponse)){
                
                foreach($inactiveContest as $contest){ 
                    
                    $matchDate = strtotime($contest['start_time']); 
                    
                    if(isset($gameResponse[$matchDate])){
                        
                        $gameStat = json_encode($gameResponse[$matchDate]);
                        
                        $statArray = array('game_date'   => $contest['start_time'],
                                           'game_stat'   => $gameStat,
                                           'sports_id'   => $sportsID['sports_id'],
                                           'last_update' => date('Y-m-d H:i:s'));

                        $objGameStatsModel = Application_Model_GameStats::getInstance();
                        
                        $objGameStatsModel->insertGameStats($statArray);// insert game stats 
                        
                    }
                }
            }
            
        }
        
    }
     /**
     *  Dev : Vivek Chaudhari
     *  Desc: Get future game list of sports and store match details in database 
      * interval : runs per day
     */
    public function getFutureGameListAction(){
        
        $objParser      = Engine_Utilities_GameXmlParser::getInstance();
        $objSportsModel = Application_Model_Sports::getInstance();
        $sportsName     = $this->getRequest()->getParam('sportsname');
        $sportsID       = $objSportsModel->getSportsDetailsByName($sportsName);//get sports id by name
        
        if(isset($sportsID['sports_id'])){ 
            
            $gameResponse = $objParser->getGameLists($sportsName);// get game schedule by game name
         
            $extendDate = date("M.d.Y");
            $extendDate = strtotime($extendDate);
            $extendDate = strtotime("+30 day", $extendDate);
            $extendDate = date('M.d.Y', $extendDate);

            foreach ($gameResponse as $response) {
              
                if ((strtotime($response['match_on']) >= strtotime(date("M.d.Y"))) && (strtotime($response['match_on']) <= strtotime($extendDate))) {
             
                    echo "==========future game list==========";
                    print"<pre>";
                    print_r($response);
                    print"</pre>"; 
                    
                    $objGameStatsModel = Application_Model_GameStats::getInstance();
                    
                    $gameStat = json_encode($response); 
                    
                    $statArray = array('game_date'   => date('Y-m-d',  strtotime($response['formatted_date'])),
                                       'game_stat'   => $gameStat,
                                       'sports_id'   => $sportsID['sports_id'],
                                       'last_update' => date('Y-m-d H:i:s')); 
                    
                    $objGameStatsModel->insertGameStats($statArray);// insert game stats 
                }

            }
        }
        
    }
    
     /**
     *  Dev : Vivek Chaudhari
     *  Desc: Get the contest details, calculate lineups points and store to DB
     */
    public function contestResultAction() { //runs per minute
       
		/* $my_file = APPLICATION_PATH.'/cron_test.txt';
		$handle = fopen($my_file, 'a') or die('Cannot open file:  '.$my_file);
		$data = '\n Cron is running now in contestResultAction function';
		fwrite($handle, $data); */
		
        $gameType  = strtoupper($this->getRequest()->getParam('sportsname'));
        
		$matchDate      = date('d.m.Y'); 
        
        $objContestModel = Application_Model_Contests::getInstance();
        $objParser       = Engine_Utilities_GameXmlParser::getInstance();
        $objLineUp       = Application_Model_Lineup::getInstance();
        $objSportsModel  = Application_Model_Sports::getInstance();
        $objGamePlayers  = Application_Model_GamePlayers::getInstance(); 
        $objUserLineup   = Application_Model_UserLineup::getInstance();
        $sportsID        = $objSportsModel->getSportsDetailsByName($gameType); //get sports id by name   
        
        $sportsId        = $sportsID['sports_id'];
		
		/* $matchDate      = date('07.06.2017');
		$matchStats = $objParser->getMatchStatByDate($gameType, $matchDate);
        $this->updatePlayerPointStats($sportsId,$matchStats);
		die('dsfd'); */
		
        $liveContest = $objContestModel->getActiveLiveContest($sportsId);
		//echo "<pre>"; print_r($liveContest);
        if(!empty($liveContest)){
			$matchDate = date('d.m.Y',strtotime($liveContest[0]['start_time']));
            $matchStats = $objParser->getMatchStatByDate($gameType, $matchDate);
            $this->updatePlayerPointStats($sportsId,$matchStats);
            foreach($liveContest as $lval){
                $contestLineups =  $objUserLineup->getLineupsByConId($lval['contest_id']);
                if($contestLineups){
                    foreach($contestLineups as $cLineup){
                        $playerIds    = json_decode($cLineup['player_ids'],true); //get the player ids of lineup
                        $filterResArr = array();
                        foreach ($playerIds as $plrid) { // find out stats data depend on player ids from match stats
                            $filterRes = @$objParser->filterArray($plrid, $matchStats, 'id');
                            if (!empty($filterRes)) {
                                $filterResArr = array_merge($filterResArr, array_values($filterRes));
                            }
                        } 
                        if ($filterResArr && !empty($filterResArr)) {

                            switch ($gameType) {
                                case 'NFL':
                                    $points = $this->calculateNFLPointsFunctions($filterResArr); 
                                    break;
                                case 'MLB':
                                    $filterResArr = $this->getMlbSpecialPoints($filterResArr);                                
                                    $points = $this->calculateMLBPointsFunctions($filterResArr);                                
                                    break;
                                case 'NBA':
                                    $points = $this->calculateNBAPointsFunctions($filterResArr);
                                    break;
                                case 'NHL':
                                    $points = $this->calculateNHLPointsFunctions($filterResArr);
                                    break;
                              }                            
                                if ($points) {
                                        
                                       // saperate fpts from array 
                                       $fpts = array_map(function($item) {
                                                return $item['fpts'];
                                            }, $points);

                                       // saperate scoring from array 
                                       $scroing = array_map(function($item) {
                                                        return $item['scroing'];
                                                    }, $points);
                                    
                                    $pointsDecodeArr=array();$scoreDecodeArr=array();
                                    if(isset($cLineup['point_details']) && $cLineup['point_details']!="" && $cLineup['point_details']!=null){
                                        $pointsDecodeArr = json_decode($cLineup['point_details'],true);
                                        if(is_array($pointsDecodeArr) && !empty($pointsDecodeArr)){
                                            foreach($fpts as $pKey=>$pVal){
                                                if(array_key_exists($pKey, $pointsDecodeArr)){ 
                                                    $pointsDecodeArr[$pKey] = $pVal;
                                                }else{
                                                    $pointsDecodeArr[$pKey] = $pVal;
                                                }
                                            }
                                        }
                                    }else{
                                        $pointsDecodeArr = $fpts;
                                    }
                                    if(isset($cLineup['scoring']) && $cLineup['scoring']!="" && $cLineup['scoring']!=null){
                                        $scoreDecodeArr = json_decode($cLineup['scoring'],true);
                                        if(is_array($scoreDecodeArr) && !empty($scoreDecodeArr)){
                                            foreach($scroing as $sKey=>$sVal){
                                                if(array_key_exists($sKey, $scoreDecodeArr)){
                                                    $scoreDecodeArr[$sKey] = $sVal;
                                                }else{
                                                    $scoreDecodeArr[$sKey] = $sVal;
                                                }
                                            }
                                        }
                                    }else{
                                        $scoreDecodeArr = $scroing;
                                    }             
                                    $data = array('players_points' => array_sum($pointsDecodeArr),
                                                  'point_details'  => json_encode($pointsDecodeArr),
                                                  'scoring'        => json_encode($scoreDecodeArr));

                                    $objLineUp->updateLineup($data, $cLineup['lineup_id']);
                                }
                         }
                    }

                }
               $this->getRankByContest($lval['contest_id']); //update rank by lineup id for perticular contest in user lineup table
            }
        }
    }
    
   public function getMlbSpecialPoints($filterResArr) {
        $playerStatsModel = Application_Model_PlayerStats::getInstance();
        foreach ($filterResArr as $fkey => $fVal) {
            $plrId = $fVal['id'];
            $teamId = $fVal['team_id'];
            $sportId = "2";
            $result = $playerStatsModel->getPlayerStats($teamId, $sportId);
            if(isset($result['team_stats'])){
            $stats = json_decode($result['team_stats'],true);
            $response = $this->searchArr($stats,'id',$fVal['id']);
            $statsArr = array();
            if(!empty($response)){
                foreach($response as $rVal){
                    $statsArr = array_merge($statsArr,$rVal);
                }
            }
            if(isset($statsArr['caught_stealing'])){
                $filterResArr[$fkey]['caught_stealing'] = $statsArr['caught_stealing'];
            }

            
        }
        }
        return $filterResArr;
    }
    
    public function searchArr($array, $key, $value) {
        $results = array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, $this->searchArr($subarray, $key, $value));
            }
        }

        return $results;
    }
    
    /**
     * Desc : Calculate MLB points
     * @param <Array> $plrData
     * @return <Array>
     */
    public function calculateMLBPointsFunctions($plrData){

        $points = array();
       
        foreach($plrData as $plrstat){
            
            if( $plrstat['type'] == 'pitchers'){   
				unset($points[$plrstat['id']]['scroing']['1B']);
				unset($points[$plrstat['id']]['scroing']['2B']);
				unset($points[$plrstat['id']]['scroing']['3B']);
				unset($points[$plrstat['id']]['scroing']['HR']);
				unset($points[$plrstat['id']]['scroing']['RBI']);
				unset($points[$plrstat['id']]['scroing']['R']);
				unset($points[$plrstat['id']]['scroing']['HBP']);
				unset($points[$plrstat['id']]['scroing']['SB']);
				unset($points[$plrstat['id']]['scroing']['SF']);
				unset($points[$plrstat['id']]['scroing']['SO']);
                   
				$innings_pitched = 0;
                $strikeouts      = 0;
                $win             = 0;
                $earned_runs     = 0;
                $hit             = 0;
                $BaseonBalls     = 0;
                $homeRunPitcher  = 0;
                $saves           = 0;
                $qltStart        = 0;
               if(isset($plrstat['innings_pitched'])){
                    $innings_pitched   = $plrstat['innings_pitched'] * 2.25;
                    $points[$plrstat['id']]['scroing']['IP'] = $plrstat['innings_pitched'];   
                    
               }
               
               if(isset($plrstat['strikeouts'])){
                   $strikeouts        = $plrstat['strikeouts'] * 2;
                   $points[$plrstat['id']]['scroing']['K']  = $plrstat['strikeouts'];
               }
               
               /*if(isset($plrstat['win'])){
                   $win               = $plrstat['win'] * 4;
                   $points[$plrstat['id']]['scroing']['W']  = $plrstat['win'];
               }*/
               
               if(isset($plrstat['earned_runs'])){
                   $earned_runs       = $plrstat['earned_runs'] * (-2);
                    $points[$plrstat['id']]['scroing']['ER']  = $plrstat['earned_runs'];//$plrstat['earned_runs'];
               }
               if(isset($plrstat['hits'])){
                   $hit               = $plrstat['hits'] * (-1); 
                   $points[$plrstat['id']]['scroing']['H']  = $plrstat['hits']; //$plrstat['hits'];
               }
               
               if(isset($plrstat['walks'])){
                   $BaseonBalls = $plrstat['walks'] * (-0.6);
                   $points[$plrstat['id']]['scroing']['BB'] = $plrstat['walks'];
               }             
               
               if(isset($plrstat['home_runs'])){
                   $homeRunPitcher = $plrstat['home_runs'] * (-3);
                   $points[$plrstat['id']]['scroing']['HR-pitch'] = $plrstat['home_runs'];
               }
               
                if(isset($plrstat['innings_pitched']) && isset($plrstat['earned_runs'])){
                    if(($plrstat['earned_runs'] <= 3) && ($plrstat['innings_pitched'] >= 6)){
                        $qltStart =(2.5);
                        $points[$plrstat['id']]['scroing']['QS'] = 1;
                    }
               }
               
               $points[$plrstat['id']]['fpts'] = $innings_pitched + $strikeouts + $win + $earned_runs + $hit + $BaseonBalls  + $homeRunPitcher + $qltStart;
               
            }else if(!isset($points[$plrstat['id']])){
                
                 $single         = $plrstat['hits'] * 3;
               $doubles        = $plrstat['doubles'] * 5;
               $triples        = $plrstat['triples'] * 8;
               $home_runs      = $plrstat['home_run'] * 10;
               $runs_batted_in = $plrstat['runs_batted_in'] * 2;
               $runs           = $plrstat['runs'] * 2;
               $hit_by_pitch   = $plrstat['hit_by_pitch'] * 2;
               $stolen_bases   = $plrstat['stolen_bases'] * 5;
               
               $sacFly         = $plrstat['sac_fly'] * 1;
               $strikOutHitter = $plrstat['hitter_strikeouts'] * -2;

               $points[$plrstat['id']]['fpts'] = $single + $doubles + $triples + $home_runs + $runs_batted_in + $runs + $hit_by_pitch + $stolen_bases + $sacFly + $strikOutHitter ;
               
               $points[$plrstat['id']]['scroing']['1B']  = $plrstat['hits'];
               $points[$plrstat['id']]['scroing']['2B']  = $plrstat['doubles'];
               $points[$plrstat['id']]['scroing']['3B']  = $plrstat['triples'];
               $points[$plrstat['id']]['scroing']['HR']  = $plrstat['home_run'];
               $points[$plrstat['id']]['scroing']['RBI'] = $plrstat['runs_batted_in'];
               $points[$plrstat['id']]['scroing']['R']   = $plrstat['runs'];
               $points[$plrstat['id']]['scroing']['HBP'] = $plrstat['hit_by_pitch'];
               $points[$plrstat['id']]['scroing']['SB']  = $plrstat['stolen_bases'];
               $points[$plrstat['id']]['scroing']['SF']  = $plrstat['sac_fly'];
               $points[$plrstat['id']]['scroing']['SO']  = $plrstat['hitter_strikeouts'];
                     
            }
            
        }
		//echo "<pre>"; print_r($points); die;
        if(!empty($points)){
            return $points;
        } 
    }
    
    /**
     * Desc : Calculate NFL points
     * Developer : Vivek Chaudhari
     * date : 22/09/2014
     * @param <Array> $plrData
     * @return <Array>
     */
      public function calculateNFLPointsFunctions($plrData) {

        $points = array();

        foreach ($plrData as $pkey => $pvalue) {
            $passPoints = 0;
            $RushPoints = 0;
            $RecvPoints = 0;

            //passing category player data
            if ($pvalue['type'] === "Passing") {

                $PaTD = $pvalue['passing_touch_downs'];
                $PaTDPoints = $pvalue['passing_touch_downs'] * 4;

                if ($pvalue['yards'] > 300) {
                    $PaYds = $pvalue['yards'];
                    $PaYdsPoints = ($pvalue['yards'] * 0.04) + 3;
                } else {
                    $PaYds = $pvalue['yards'];
                    $PaYdsPoints = ($pvalue['yards'] * 0.04);
                }

                $INT = $pvalue['interceptions'];
                $INTPoints = $INT * (-1);

                $points[$pvalue['id']]['scroing']['PaTD'] = $PaTD;
                $points[$pvalue['id']]['scroing']['PaYds'] = $PaYds;
                $points[$pvalue['id']]['scroing']['INT'] = $INT;

                $passPoints = $PaTDPoints + $PaYdsPoints + $INTPoints;
                if (isset($points[$pvalue['id']]['fpts'])) {
                    $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $passPoints;
                } else {
                    $points[$pvalue['id']]['fpts'] = $passPoints;
                }
            }

            //rushing player category data
            if ($pvalue['type'] === "Rushing") {

                if ($pvalue['yards'] > 100) {
                    $RuYds = $pvalue['yards'];
                    $RuYdsPoints = ($pvalue['yards'] * 0.1) + 3;
                } else {
                    $RuYds = $pvalue['yards'];
                    $RuYdsPoints = ($pvalue['yards'] * 0.1);
                }
                $RuTD = $pvalue['rushing_touch_downs'];
                $RuTDPoints = $RuTD * 6;

                $points[$pvalue['id']]['scroing']['RuTD'] = $RuTD;
                $points[$pvalue['id']]['scroing']['RuYds'] = $RuYds;

                $RushPoints = $RuTDPoints + $RuYdsPoints;
                if (isset($points[$pvalue['id']]['fpts'])) {
                    $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $RushPoints;
                } else {
                    $points[$pvalue['id']]['fpts'] = $RushPoints;
                }
            }

            //receiving player category data
            if ($pvalue['type'] === "Receiving") {

                $Rec = $pvalue['total_receptions'];
                $RecPoints = $Rec * 1;
                $RecTD = $pvalue['receiving_touch_downs'];
                $RecTDPoints = $RecTD * 6;

                if ($pvalue['yards'] > 100) {

                    $RecYds = $pvalue['yards'];
                    $RecYdsPoints = ($RecYds * 0.1) + 3;
                } else {

                    $RecYds = $pvalue['yards'];
                    $RecYdsPoints = $RecYds * 0.1;
                }

                $points[$pvalue['id']]['scroing']['REC'] = $Rec;
                $points[$pvalue['id']]['scroing']['RecYds'] = $RecYds;
                $points[$pvalue['id']]['scroing']['RecTD'] = $RecTD;

                $RecvPoints = $RecPoints + $RecTDPoints + $RecYdsPoints;
                if (isset($points[$pvalue['id']]['fpts'])) {
                    $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $RecvPoints;
                } else {
                    $points[$pvalue['id']]['fpts'] = $RecvPoints;
                }

            }
            //---------------data for DST player---------------------
            if ($pvalue['type'] === "team_stats") {

                $points[$pvalue['id']]['scroing']['SACK'] = $pvalue['sacks'];
                $sackPoints = $pvalue['sacks'] * 1;

                $points[$pvalue['id']]['scroing']['INTD'] = $pvalue['interception_TD'];
                $IntdPoints = $pvalue['interception_TD'] * 6;

                $points[$pvalue['id']]['scroing']['INT'] = $pvalue['interceptions'];
                $intPoints = $pvalue['interceptions'] * 2;

                $points[$pvalue['id']]['scroing']['DFR'] = $pvalue['fumbles_recovered'];
                $fumbRecPoints = $pvalue['fumbles_recovered'] * 2;

                $points[$pvalue['id']]['scroing']['STY'] = $pvalue['safeties'];
                $safPoints = $pvalue['safeties'] * 2;

                if ($pvalue['points_allowed'] == 0) {

                    $PA = 10;
                    $points[$pvalue['id']]['scroing']['0 PA'] = 1;
                } else if (($pvalue['points_allowed'] > 0) && ($pvalue['points_allowed'] <= 6)) {

                    $PA = 7;
                    $points[$pvalue['id']]['scroing']['1-6 PA'] = 1;
                } else if (($pvalue['points_allowed'] >= 7) && ($pvalue['points_allowed'] <= 13)) {

                    $PA = 4;
                    $points[$pvalue['id']]['scroing']['7-13 PA'] = 1;
                } else if (($pvalue['points_allowed'] >= 14) && ($pvalue['points_allowed'] <= 20)) {

                    $PA = 1;
                    $points[$pvalue['id']]['scroing']['14-20 PA'] = 1;
                } else if (($pvalue['points_allowed'] >= 21) && ($pvalue['points_allowed'] <= 27)) {

                    $PA = 0;
                    $points[$pvalue['id']]['scroing']['21-27 PA'] = 1;
                } else if (($pvalue['points_allowed'] >= 28) && ($pvalue['points_allowed'] <= 34)) {

                    $PA = -1;
                    $points[$pvalue['id']]['scroing']['28-34 PA'] = 1;
                } else if ($pvalue['points_allowed'] >= 35) {

                    $PA = -4;
                    $points[$pvalue['id']]['scroing']['35+ PA'] = 1;
                }

                $teamStatsPoints = $sackPoints + $IntdPoints + $intPoints + $fumbRecPoints + $safPoints + $PA;

                if (isset($points[$pvalue['id']]['fpts'])) {
                    $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $teamStatsPoints;
                } else {
                    $points[$pvalue['id']]['fpts'] = $teamStatsPoints;
                }
            }

            if ($pvalue['type'] === "kick_returns") {

                $points[$pvalue['id']]['scroing']['KRTD'] = $pvalue['td'];
                if (isset($points[$pvalue['id']]['fpts'])) {
                    $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + ($pvalue['td'] * 6);
                } else {
                    $points[$pvalue['id']]['fpts'] = ($pvalue['td'] * 6);
                }
            }
            if ($pvalue['type'] === "punt_returns") {

                $points[$pvalue['id']]['scroing']['PRTD'] = $pvalue['td'];
                if (isset($points[$pvalue['id']]['fpts'])) {
                    $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + ($pvalue['td'] * 6);
                } else {
                    $points[$pvalue['id']]['fpts'] = ($pvalue['td'] * 6);
                }
            }
            //-----end DST data--------------------
            //kickers player data============

            if ($pvalue['type'] === "kicking") {


                $ext = $pvalue['extra_point'];
                $extArr = explode("/", $ext);
                $extPts = $extArr[0];

                $FGZ39 = $pvalue['field_goals_from_1_19_yards'] + $pvalue['field_goals_from_20_29_yards'] + $pvalue['field_goals_from_30_39_yards'];
                $FGZ39Points = $FGZ39 * 3;

                $FG49 = $pvalue['field_goals_from_40_49_yards'];
                $FG49Points = ($pvalue['field_goals_from_40_49_yards'] * 4);


                $FG50 = $pvalue['field_goals_from_50_yards'];
                $FG50Points = $FG50 * 5;


                $points[$pvalue['id']]['scroing']['ExtPts'] = $extPts;
                $points[$pvalue['id']]['scroing']['FG-0-39'] = $FGZ39;
                $points[$pvalue['id']]['scroing']['FG-40-49'] = $FG49;
                $points[$pvalue['id']]['scroing']['FG-50+'] = $FG50;

                $kickerPoints = $extPts + $FGZ39Points + $FG49Points + $FG50Points;

                if (isset($points[$pvalue['id']]['fpts'])) {
                    $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $kickerPoints;
                } else {
                    $points[$pvalue['id']]['fpts'] = $kickerPoints;
                }
            }
            //-------end kickers player data---------------
        }
        if (!empty($points)) {
            return $points;
        }
    }
    
    
    //without teams stats dat afor DST players, will work in backup
    public function calculateNFLPointsFunctions_old($plrData){
        
        $points = array();

        foreach($plrData as $pkey=>$pvalue){ 
            $passPoints = 0; $RushPoints = 0; $RecvPoints = 0;
            
            //passing category player data
            if($pvalue['type'] === "Passing"){
                
                $PaTD = $pvalue['passing_touch_downs'];
                $PaTDPoints = $pvalue['passing_touch_downs'] * 4;
                
                    if($pvalue['yards'] > 300){
                        $PaYds = $pvalue['yards'];
                        $PaYdsPoints = ($pvalue['yards'] * 0.04)+3;
                        
                    }else{
                        $PaYds = $pvalue['yards'];
                        $PaYdsPoints = ($pvalue['yards'] * 0.04);
                         
                    }
                    
                    $INT = $pvalue['interceptions'];
                    $INTPoints = $INT * (-1);
                    
                    $points[$pvalue['id']]['scroing']['PaTD']   = $PaTD;
                    $points[$pvalue['id']]['scroing']['PaYds']  = $PaYds;
                    $points[$pvalue['id']]['scroing']['INT']    = $INT;
                    
                    $passPoints = $PaTDPoints + $PaYdsPoints + $INTPoints;
                    if(isset($points[$pvalue['id']]['fpts'])){
                        $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $passPoints;
                    }else{
                        $points[$pvalue['id']]['fpts'] = $passPoints;
                    }
                    
            }
            
            //rushing player category data
            if($pvalue['type'] === "Rushing"){
                
                if($pvalue['yards'] > 100){
                    $RuYds = $pvalue['yards'];
                    $RuYdsPoints = ($pvalue['yards'] * 0.1)+3;
                    
                }else{
                    $RuYds = $pvalue['yards'];
                    $RuYdsPoints = ($pvalue['yards'] * 0.1);
                    
                }
                    $RuTD = $pvalue['rushing_touch_downs'];
                    $RuTDPoints = $RuTD*6;
                    
                    $points[$pvalue['id']]['scroing']['RuTD']  = $RuTD;
                    $points[$pvalue['id']]['scroing']['RuYds']  = $RuYds;
                    
                    $RushPoints = $RuTDPoints + $RuYdsPoints;
                    if(isset($points[$pvalue['id']]['fpts'])){
                        $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $RushPoints;
                    }else{
                        $points[$pvalue['id']]['fpts'] =  $RushPoints;
                    }
                    
            }
            
            //receiving player category data
            if($pvalue['type'] === "Receiving"){
                
                    $Rec         = $pvalue['total_receptions'];
                    $RecPoints   = $Rec * 1;
                    $RecTD       = $pvalue['receiving_touch_downs'];
                    $RecTDPoints = $RecTD * 6;
                    
                    if($pvalue['yards'] > 100){
                        
                        $RecYds = $pvalue['yards'];
                        $RecYdsPoints = ($RecYds * 0.1)+3;
                    }else{
                        
                        $RecYds = $pvalue['yards'];
                        $RecYdsPoints = $RecYds * 0.1;
                    }
                    
                    $points[$pvalue['id']]['scroing']['REC']     = $Rec;
                    $points[$pvalue['id']]['scroing']['RecYds']  = $RecYds;
                    $points[$pvalue['id']]['scroing']['RecTD']   = $RecTD;
                    
                    $RecvPoints = $RecPoints + $RecTDPoints + $RecYdsPoints;
                    if(isset($points[$pvalue['id']]['fpts'])){
                        $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $RecvPoints;
                    }else{
                        $points[$pvalue['id']]['fpts'] =  $RecvPoints;
                    }
//                    $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $RecvPoints;
            }
           
            
//---------------data for DST player---------------------
//             if($pvalue['type'] === "team_stats"){
//                 
//                 $points[$pvalue['id']]['scroing']['SACK']  = $pvalue['sacks'];
//                 $sackPoints = $pvalue['sacks'] * 1;
//                 
//                 $points[$pvalue['id']]['scroing']['INTD']  = $pvalue['interception_TD'];
//                 $IntdPoints = $pvalue['interception_TD'] * 6;
//                 
//                 $points[$pvalue['id']]['scroing']['INT']  = $pvalue['interceptions'];
//                 $intPoints = $pvalue['interceptions'] *  2;
//                 
//                 $points[$pvalue['id']]['scroing']['DFR']  = $pvalue['fumbles_recovered'];
//                 $fumbRecPoints = $pvalue['fumbles_recovered'] *2;
//                 
//                 $points[$pvalue['id']]['scroing']['STY']  = $pvalue['safeties'];
//                 $safPoints = $pvalue['safeties'] * 2;
//                 
//                 if($pvalue['points_allowed'] == 0){
//                     
//                        $PA = 10;
//                        $points[$pvalue['id']]['scroing']['0 PA']  = 1;
//                 }else if(($pvalue['points_allowed'] > 0) && ($pvalue['points_allowed'] <=6) ){
//                     
//                         $PA = 7; 
//                         $points[$pvalue['id']]['scroing']['1-6 PA']  = 1;
//                 }else if(($pvalue['points_allowed'] >=7) && ($pvalue['points_allowed'] <=13) ){
//                     
//                        $PA = 4;
//                        $points[$pvalue['id']]['scroing']['7-13 PA']  = 1;
//                }else if(($pvalue['points_allowed'] >=14) && ($pvalue['points_allowed'] <=20) ){
//                    
//                       $PA = 1;
//                       $points[$pvalue['id']]['scroing']['14-20 PA']  = 1;
//                }else if(($pvalue['points_allowed'] >= 21) && ($pvalue['points_allowed'] <=27) ){
//                    
//                        $PA = 0;     
//                        $points[$pvalue['id']]['scroing']['21-27 PA']  = 1;
//                }else if(($pvalue['points_allowed'] >= 28) && ($pvalue['points_allowed'] <=34) ){
//                    
//                        $PA = -1;
//                        $points[$pvalue['id']]['scroing']['28-34 PA']  = 1;
//                }else if($pvalue['points_allowed'] >= 35){
//                    
//                        $PA = -4;
//                        $points[$pvalue['id']]['scroing']['35+ PA']  = 1;
//                }
//             
//                $teamStatsPoints = $sackPoints + $IntdPoints + $intPoints + $fumbRecPoints + $safPoints + $PA ;
//                
//                if(isset($points[$pvalue['id']]['fpts'])){
//                    $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $teamStatsPoints;
//                }else{
//                    $points[$pvalue['id']]['fpts'] = $teamStatsPoints;
//                }
//             }
             
             
             if($pvalue['type'] === "team_stats"){
                 
                 $points[$pvalue['id']]['scroing']['PA']  = $pvalue['totalscore'];
                 if(isset($points[$pvalue['id']]['fpts'])){
                      $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + (intval(($pvalue['totalscore'])/12) * 4);
                  }else{
                      $points[$pvalue['id']]['fpts'] = (intval(($pvalue['totalscore'])/12) * 4);
                  }
             }
             
             if($pvalue['type'] === "fumbles"){
                 
                 if(isset($points[$pvalue['id']]['scroing']['FR'])){
                      $points[$pvalue['id']]['scroing']['FR'] = $points[$pvalue['id']]['scroing']['FR'] + ($pvalue['rec']);
                  }else{
                      $points[$pvalue['id']]['scroing']['FR'] = ($pvalue['rec']);
                  }
                 
                 if(isset($points[$pvalue['id']]['fpts'])){
                      $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + ($pvalue['rec'] * 2);
                  }else{
                      $points[$pvalue['id']]['fpts'] = ($pvalue['rec'] * 2);
                  }
             }
             
//             if($pvalue['type'] === "fumbles"){
//                 
//                 if(isset($points[$pvalue['id']]['scroing']['FR'])){
//                      $points[$pvalue['id']]['scroing']['FR'] = $points[$pvalue['id']]['scroing']['FR'] + ($pvalue['rec']);
//                  }else{
//                      $points[$pvalue['id']]['scroing']['FR'] = ($pvalue['rec']);
//                  }
//                 
//                 if(isset($points[$pvalue['id']]['fpts'])){
//                      $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + ($pvalue['rec'] * 2);
//                  }else{
//                      $points[$pvalue['id']]['fpts'] = ($pvalue['rec'] * 2);
//                  }
//             }
             
             if($pvalue['type'] === "interceptions"){
                 
                 if(isset($pvalue['intercepted_touch_downs']) && !empty($pvalue['intercepted_touch_downs'])){
                 if(isset($points[$pvalue['id']]['scroing']['ItrTD'])){
                      $points[$pvalue['id']]['scroing']['ItrTD'] = $points[$pvalue['id']]['scroing']['ItrTD'] + ($pvalue['intercepted_touch_downs']);
                  }else{
                      $points[$pvalue['id']]['scroing']['ItrTD'] = ($pvalue['intercepted_touch_downs']);
                  }
                 
                 if(isset($points[$pvalue['id']]['fpts'])){
                      $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + ($pvalue['intercepted_touch_downs'] * 2);
                  }else{
                      $points[$pvalue['id']]['fpts'] = ($pvalue['intercepted_touch_downs'] * 2);
                  }
                 }
             }
             
             if($pvalue['type'] === "defensive"){
                 
                 if(isset($pvalue['sacks']) && !empty($pvalue['sacks'])){
                    if(isset($points[$pvalue['id']]['scroing']['sacks'])){
                         $points[$pvalue['id']]['scroing']['sacks'] = $points[$pvalue['id']]['scroing']['sacks'] + ($pvalue['sacks']);
                     }else{
                         $points[$pvalue['id']]['scroing']['sacks'] = ($pvalue['sacks']);
                     }

                    if(isset($points[$pvalue['id']]['fpts'])){
                         $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + ((intval($pvalue['sacks'])/2) * 2);
                     }else{
                         $points[$pvalue['id']]['fpts'] = intval($pvalue['sacks']/2) * 2;
                     }
                }
             }
              if($pvalue['type'] === "kick_returns"){
                  
                  $points[$pvalue['id']]['scroing']['KRTD']  = $pvalue['td'];
                  if(isset($points[$pvalue['id']]['fpts'])){
                      $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + ($pvalue['td'] * 6);
                  }else{
                      $points[$pvalue['id']]['fpts'] = ($pvalue['td'] * 6);
                  }
                  
              }
              if($pvalue['type'] === "punt_returns"){
                  
                  $points[$pvalue['id']]['scroing']['PRTD']  = $pvalue['td'];
                  if(isset($points[$pvalue['id']]['fpts'])){
                      $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + ($pvalue['td'] * 6);
                  }else{
                      $points[$pvalue['id']]['fpts'] = ($pvalue['td'] * 6);
                  }
                  
              }
              //-----end DST data--------------------
          
              
              
              //kickers player data============
              
              if($pvalue['type'] === "kicking"){
                
               
                    $ext = $pvalue['extra_point'];
                    $extArr= explode("/",$ext);
                    $extPts = $extArr[0];
                    
                    $FGZ39 = $pvalue['field_goals_from_1_19_yards'] + $pvalue['field_goals_from_20_29_yards'] + $pvalue['field_goals_from_30_39_yards'];
                    $FGZ39Points = $FGZ39 * 3;
                    
                    $FG49 = $pvalue['field_goals_from_40_49_yards'];
                    $FG49Points = ($pvalue['field_goals_from_40_49_yards'] * 4);
                    
                
                    $FG50 = $pvalue['field_goals_from_50_yards'];
                    $FG50Points = $FG50*5;
                    
                    
                    $points[$pvalue['id']]['scroing']['ExtPts']  = $extPts;
                    $points[$pvalue['id']]['scroing']['FG-0-39']  = $FGZ39;
                    $points[$pvalue['id']]['scroing']['FG-40-49']  = $FG49;
                    $points[$pvalue['id']]['scroing']['FG-50+']  = $FG50;
                    
                    $kickerPoints = $extPts + $FGZ39Points + $FG49Points + $FG50Points;
                    
                    if(isset($points[$pvalue['id']]['fpts'])){
                        $points[$pvalue['id']]['fpts'] = $points[$pvalue['id']]['fpts'] + $kickerPoints;
                    }else{
                        $points[$pvalue['id']]['fpts'] =  $kickerPoints;
                    }
                    
            }
              //-------end kickers player data---------------
        }
         if(!empty($points)){
            return $points;
        }
    }
    
    /**
     * Developer : Vivek Chaudhari
     * Desc : Calculate NBA points
     * @param <Array> $plrData
     * @return <Array>
     */
    public function calculateNBAPointsFunctions($plrData){
        
        $points = array();        
        $i = 0;
       
        
        foreach($plrData as $plrstat){
			 $double = array();

            $point                 = $plrstat['points'];
            $threepoint_goals_made = $plrstat['threepoint_goals_made'] * 0.5;
            $rebound               = $plrstat['total_rebounds'] * 1.25;
            $assists               = $plrstat['assists'] * 1.5;
            $steals                = $plrstat['steals'] * 2;
            $blocks                = $plrstat['blocks'] * 2;
            $turnovers             = $plrstat['turnovers'] * (-0.5);
            
            if($plrstat['points'] > 9){
                
               $double['points'] = $plrstat['points'];
            }
            if($plrstat['total_rebounds'] > 9){
                
                $double['total_rebounds'] = $plrstat['total_rebounds'];
            }
            if($plrstat['assists'] > 9){
                
                $double['assists'] = $plrstat['assists'];
            }
            if($plrstat['blocks'] > 9){
                
                $double['blocks'] = $plrstat['blocks'];
            }
            if($plrstat['steals'] > 9){
                
                $double['steals'] = $plrstat['steals'];
            }
         
            if(count($double) > 1 ){
                $doubleScore = 1;
                $doubleDouble = 1.5;
            }else{
                $doubleScore = 0;
                $doubleDouble = 0;
            }
            
            if(count($double) > 2){
                $tripleScore = 1;
                $tripleDouble = 3;
            }else{
                $tripleScore = 0;
                $tripleDouble = 0;
            }
            
            $points[$plrstat['id']]['fpts'] = $point + $threepoint_goals_made + $rebound + $assists + $steals + $blocks + $turnovers + $doubleDouble + $tripleDouble;
            
            $points[$plrstat['id']]['scroing']['PTS']           = $point;
            $points[$plrstat['id']]['scroing']['3PT']           = $plrstat['threepoint_goals_made'];
            $points[$plrstat['id']]['scroing']['TOT REB']       = $plrstat['total_rebounds'];
            $points[$plrstat['id']]['scroing']['AST']           = $plrstat['assists'];
            $points[$plrstat['id']]['scroing']['STL']           = $plrstat['steals'];
            $points[$plrstat['id']]['scroing']['BLK']           = $plrstat['blocks'];
            $points[$plrstat['id']]['scroing']['TO']            = $plrstat['turnovers'];
            $points[$plrstat['id']]['scroing']['DD']            = $doubleScore;
            $points[$plrstat['id']]['scroing']['TD']            = $tripleScore;
            unset($double);
        }
        
        if(!empty($points)){
            return $points;
        }
    }
    
    /**
     * Dev : Manoj 
     * Desc : Calculate NHL points
     * @param <Array> $plrData
     * @return <Array>
     */
    public function calculateNHLPointsFunctions($plrData){
        
        $points = array();
        $i = 0;
        
        foreach ($plrData as $plrstat){ 
         
            $goals                  = 0;
            $assists                = 0;
            $shots_on_goal          = 0;
            $blocked_shots          = 0;
            $ShortHandedPointBonus  = 0;
            $HatTrickBonus          = 0;
            $sh_goals               = 0;
            $Win                    = 0;
            $saves                  = 0;
            $goals_against          = 0;
            $Shutout_Bonus          = 0;
            
            if(isset($plrstat['goals'])){
                
                $goals = $plrstat['goals'] * 3;
                $points[$plrstat['id']]['scroing']['G'] = $plrstat['goals'];
                
                if ($plrstat['goals'] >= 3) {
                    
                    $hatTric = intval($plrstat['goals'] / 3);
                    $HatTrickBonus = intval($hatTric) * 1.5;
                    $points[$plrstat['id']]['scroing']['HAT']= intval($hatTric);
                }  
            }
            
            if(isset($plrstat['assists'])){
                
                $assists = $plrstat['assists'] * 2;
                $points[$plrstat['id']]['scroing']['A'] = $plrstat['assists'];
            }
            
            if(isset($plrstat['shots_on_goal'])){
                
                $shots_on_goal = $plrstat['shots_on_goal'] * 0.3;
                $points[$plrstat['id']]['scroing']['SOG']=$plrstat['shots_on_goal'];
            }
            
            if(isset($plrstat['blocked_shots'])){
                
                $blocked_shots = $plrstat['blocked_shots'] * 0.5;
                $points[$plrstat['id']]['scroing']['BS']=$plrstat['blocked_shots'];
            }
            
            if(isset($plrstat['goals']) && isset($plrstat['assists'])){
                
                $ShortHandedPointBonus = $plrstat['goals'] + $plrstat['assists'];
                $points[$plrstat['id']]['scroing']['ShPB']=$ShortHandedPointBonus;
            }
            
            if(isset($plrstat['sh_goals'])){
                
                $sh_goals = $plrstat['sh_goals'] * 0.2; 
                $points[$plrstat['id']]['scroing']['SH']=$plrstat['sh_goals'];
            }
            
            if(isset($plrstat['credit']) && $plrstat['credit'] = "W"){
                
                $Win = 3;
                $points[$plrstat['id']]['scroing']['W']=  1;
            }
            
            if(isset($plrstat['saves'])){
                
                $saves = $plrstat['saves'];
                $points[$plrstat['id']]['scroing']['SV']=$saves;
            }
            
            if(isset($plrstat['goals_against'])){
                
                $goals_against = $plrstat['goals_against'] * (-1);
                $points[$plrstat['id']]['scroing']['GA']=$plrstat['goals_against'];
            }
            
            if(isset($plrstat['so'])){
                
                $Shutout_Bonus = $plrstat['so'] * 2;
                $points[$plrstat['id']]['scroing']['SO']=$plrstat['so'];
            }
            
            $points[$plrstat['id']]['fpts'] = $goals + $assists + $shots_on_goal
                                              + $blocked_shots + $ShortHandedPointBonus 
                                              + $HatTrickBonus + $sh_goals + $Win + $saves 
                                              + $goals_against + $Shutout_Bonus;
           
            }

            if (!empty($points)) {
                return $points;
            }
    }
    
    public function giveAffiliate(){
        $objModelContest = Application_Model_Contests::getInstance();
        $objUserLineupModel = Application_Model_UserLineup::getInstance();
        $objSettingsModel = Application_Model_Settings::getInstance();
         $objAffiliteModel = Application_Model_Affiliate::getInstance();
         $objContestTransactionModel = Application_Model_ContestTransactions::getInstance();
         $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
         $objUserAccount = Application_Model_UserAccount::getInstance();  
       if(func_num_args()>0){
           $contestId = func_get_arg(0);
           
           if(isset($contestId) && $contestId!=0){
               $contestData = $objModelContest->getContestsDetailsById($contestId);
               $settings = $objSettingsModel->getSettings();
                if($settings['affilate_commission']!=0){
                    if(isset($contestData['entry_fee']) && $contestData['entry_fee']!=""){
                        $pool = $contestData['prize_pool'];
                        $playLimit = $contestData['play_limit'];
                        $collection = $playLimit * $contestData['entry_fee'];
                        $rake = $collection - $pool;
                        $entryRake = $rake / $playLimit;
                        $commission = ($entryRake * $settings['affilate_commission'])/100;
                        if($commission > 0){ 
                            $lineupResponse = $objUserLineupModel->getLineupsByContestId($contestId);
            
                            if(isset($lineupResponse) && !empty($lineupResponse)){ 
                                 foreach($lineupResponse as $lkey=>$lvalue){
                                     $userId = $lvalue['created_by'];
                                     $affiliteData = $objAffiliteModel->getAffiliateDataByID($userId);
                                     if(isset($affiliteData) && !empty($affiliteData)){
                               
                                        $refTransactions['user_id'] = $affiliteData['affiliate_user_id'];
                                        $refTransactions['transaction_type'] = 'RAF Commission';
                                        $refTransactions['transaction_amount'] = $commission;
                                        $refTransactions['confirmation_code'] = 'N/A';
                                        $refTransactions['description'] = 'Referal amount';
                                        $refTransactions['status'] = '1';
                                        $refTransactions['request_type'] = '8';                
                                        $refTransactions['transaction_date'] = date('Y-m-d');
                                        
                                        $referTxnId = $objUserTransactionsModel->insertUseTransactions($refTransactions);
                                        if($referTxnId){
                                           $objUserAccount->updateBalance($affiliteData['affiliate_user_id'],$commission);
                                           $refTxData['transaction_id'] = $referTxnId;
                                           $refTxData['contest_id'] = $contestId;
                                           $refTxData['user_id'] = $affiliteData['affiliate_user_id'];
                                           $objContestTransactionModel->insertConTransaction($refTxData);
                                        }
                                     }
                                 } 
                            }
                        }
                    }
                }
               
           }
       }
        
    }
    
    
    
    /**
     * Developer : Vivek Chaudhari
     * Desc : check the match status according to current time stamp and update to DB
     */
    public function checkMatchStatusAction() { //run every 1 mins	
		
		/* $my_file = APPLICATION_PATH.'/cron_test.txt';
		$handle = fopen($my_file, 'a') or die('Cannot open file:  '.$my_file);
		$data = '\n Cron is running now in check match status function';
		fwrite($handle, $data); */
		
		$objModelContest = Application_Model_Contests::getInstance();
        $objUserLineupModel       = Application_Model_UserLineup::getInstance();
        $objUserAccountModel      = Application_Model_UserAccount::getInstance();
        $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
        $objNotificationModel     = Application_Model_Notification::getInstance();
        $contests = $objModelContest->getActiveContestsForStatus();
        $currentTimeStamp = strtotime(date('Y-m-d H:i:s'));
		//echo "<pre>"; print_r($contests); die;
        if ($contests) {
            foreach ($contests as $ckey => $cvalue) {
			 	$endTimeStamp = strtotime($cvalue['end_time']);
                $startTimeStamp = strtotime($cvalue['start_time']);
                if ($endTimeStamp > $startTimeStamp) { 
				    //check end time stamp is properly set i.e. more than start tiem stamp
                    if ($endTimeStamp < $currentTimeStamp) {                       
						 //update contest status as completed
						$data = array('status' => 0, 'con_status' => 1);
                        $response = $objModelContest->updateStatus($cvalue['contest_id'], $data);
						
						if(!empty($response)) {
							$this->contestPrize($cvalue['contest_id']);
							//$this->giveAffiliate($cvalue['contest_id']); 
						}
                                             
                    }else if($currentTimeStamp >= $startTimeStamp && $currentTimeStamp < $endTimeStamp){
                        if(isset($cvalue['play_limit']) && $cvalue['play_limit'] != 0){ 
							// check entry limit is unlimited                         
								if($cvalue['total_entry'] < $cvalue['play_limit']){
                                    if(isset($cvalue['con_type_id']) && $cvalue['con_type_id'] != 1){
									// check contest is gauranteed(gauranteed contest can not be cancelled)                                      
                                        $data = array('status' => 0, 'con_status' => 3);
										//update contest status as completed and inactive
                                        $res_data = $objModelContest->updateStatus($cvalue['contest_id'], $data);
										if($res_data){
											$lineupResponse = $objUserLineupModel->getLineupsByContestId($cvalue['contest_id']);
											if(isset($lineupResponse) && !empty($lineupResponse)){
												foreach($lineupResponse as $lkey=>$lvalue){
														if(isset($cvalue['entry_fee']) && $cvalue['entry_fee'] != "" && isset($lvalue['created_by']) && $lvalue['created_by'] != ""){
											 
															$amtRefund = $cvalue['entry_fee'];
															// - $lvalue['bonus']; 
															$objUserAccountModel->updateBalance($lvalue['created_by'],$amtRefund);
															//$objUserAccountModel->addUserBonusAmount($lvalue['created_by'],$lvalue['bonus']);
															
															if(isset($amtRefund) && $amtRefund > 0){
																
																$transactions['user_id'] = $lvalue['created_by'];
																$transactions['transaction_type'] = 'Refund';
																$transactions['transaction_amount'] = $amtRefund;
																$transactions['confirmation_code'] = 'N/A';
																$transactions['description'] = 'Amount credit';
																$transactions['status'] = '1';
																$transactions['request_type'] = '4';                
																$transactions['transaction_date'] = date('Y-m-d');

																$transactionId = $objUserTransactionsModel->insertUseTransactions($transactions); //enter transaction to user transaction table
																
																if(isset($transactionId)){
																	//changes for contest details in transaction history (vivek 3rdOct15)
																	$contestTxData['transaction_id'] = $transactionId;
																	$contestTxData['contest_id'] = $cvalue['contest_id'];
																	$contestTxData['user_id'] = $lvalue['created_by'];

																	$objContestTransactiuonModel = Application_Model_ContestTransactions::getInstance();
																	$objContestTransactiuonModel->insertConTransaction($contestTxData);
																	//end
																}
															} //send notification to the entered user about contest cancellation
															
															$notifyData = array();
															$notifyData['send_to'] = $lvalue['created_by'];
															$notifyData['sent_on'] = date('Y-m-d H:i:s');
															$notifyData['message'] = 'Your contest has been cancelled !'.$cvalue['contest_name']."( ".$cvalue['start_time']." ) & Entry fee ($ ".$cvalue['entry_fee'].") is refunded to your account";
															$objNotificationModel->insertNotification($notifyData);
														}
													}
											}
										}
                                    }else{
                                        $data = array('status' => 1, 'con_status' => 2); //update contest status as live and active-> these are gauranteed contest
                                        $objModelContest->updateStatus($cvalue['contest_id'], $data);
                                    }
                                    
                                }else{
                                    $data = array('status' => 1, 'con_status' => 2); 
									//update contest status as live and active-> these are general contest with limited entries
                                    $objModelContest->updateStatus($cvalue['contest_id'], $data);
                                }
                            
                        }else{ 
                            $data = array('status' => 1, 'con_status' => 2);  //update contest status as live and active-> these are general contest with UNlimited entries
                            $objModelContest->updateStatus($cvalue['contest_id'], $data);
                        }
                    }elseif(($cvalue['start_time_mail']==1)){
						$time = time();
						//echo ($startTimeStamp - $time)."<pre>"; 
						if(($startTimeStamp - $time) <= 3600){
							$data = array('start_time_mail' => 2);
							$objModelContest->updateStatus($cvalue['contest_id'], $data);
							//$this->sendEmailToEnteredUsers($cvalue); 
						}						
					}
                }
            }
        }
    }
    
	public function sendEmailToEnteredUsers($cvalue){
		
		$objUser       = Application_Model_Users::getInstance();
		$objUserLineupModel       = Application_Model_UserLineup::getInstance();
		$objEmaillog = Application_Model_Emaillog::getInstance();
		
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$postmark_config = $config->getOption('postmark');
		//echo "<pre>"; print_r($postmark_config); die;
		$client = new Postmark\PostmarkClient($postmark_config['key']);
		
		$lineupResponse = $objUserLineupModel->getLineupsByContestId($cvalue['contest_id']);
                                     
		if(isset($lineupResponse) && !empty($lineupResponse)){ 
			
           	$template_name = 'game_time';
            $subject = 'Game Reminder';
			$c_name =$cvalue['contest_name'];
			$start_time = $cvalue['start_time'];
            $message = "Your contest $c_name going to live at $start_time Injoy and keep watching your live score on draftdaily";
            
			foreach($lineupResponse as $lkey=>$lvalue){
				$Userdata = $objUser->getUserDetailsByUserId($lvalue['created_by']);
				
				$mergers = [
					"username" => $Userdata['user_name'],
					"message" => $message,
					"subject"=>$subject
				];
			
				$result = $client->sendEmailWithTemplate($postmark_config['email'],$Userdata['email'],$postmark_config['game_time'],$mergers);
				$insertdataemaillog = array(
					'sent_email' => $Userdata['email'],
					'sent_time' => date('Y-m-d H:i:s'),
					'sent_template' => $template_name,
					'message' => $subject
				);
				$insertdataemaillog_result = $objEmaillog->insertEmailLog($insertdataemaillog);
			}
		}
	}
    /**
     * Developer    : Vivek Chaudhari
     * Description  : get array of cancelled contest ids refund money of entered users
     * Date         : 14/10/2014
     * @return      : <array> cancel contest if entry is unfilled till start time and return money to entered user if contest get cancel (except gauranteed contest)
     */
    public function getCancelContestAction(){
        
        $objContestModel          = Application_Model_Contests::getInstance();
        $objUserLineupModel       = Application_Model_UserLineup::getInstance();
        $objUserAccountModel      = Application_Model_UserAccount::getInstance();
        $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
        $objNotificationModel     = Application_Model_Notification::getInstance();
        $objContestTransactionModel = Application_Model_ContestTransactions::getInstance();
        $currTime = date('Y.m.d H:i:s');
        
        if($this->getRequest()->isPost()){
            
            $contestIds       = array();
            $cancelContestIds = array();
            
            $contestIds = $this->getRequest()->getParam('conId');
            
            if(!empty($contestIds)){
                
                foreach($contestIds as $ckey=>$cval){
                    
                    $response = $objContestModel->getContestsById($cval);
                    
                        if($response){ 
                            
                         if(isset($response['play_limit']) && $response['play_limit'] != 0){ // check entry limit is unlimited
                             
                           if(isset($response['start_time']) && date('Y.m.d H:i:s' ,strtotime($response['start_time'])) <= $currTime /* && $response['start_time'] >= $subTime */){ //cancel contest just before 5 minutes of start time
                               
                              if($response['total_entry'] < $response['play_limit']){ //echo $response['total_entry']."--".$response['play_limit']."--".$response['contest_id']."--".$response['start_time']."-start-".$currTime; die;
                                  
                                  if(isset($response['con_type_id']) && $response['con_type_id'] != 1){      // check contest is gauranteed(gauranteed contest can not be cancelled)
                                      
                                        $objContestModel->updateCancelContestStatus($response['contest_id']);  
                                    
                                        $lineupResponse = $objUserLineupModel->getLineupsByContestId($response['contest_id']);
                                        
                                        if(isset($lineupResponse) && !empty($lineupResponse)){ 
                                            
                                            foreach($lineupResponse as $lkey=>$lvalue){
                                                
                                                    if(isset($response['entry_fee']) && $response['entry_fee'] != "" && isset($lvalue['created_by']) && $lvalue['created_by'] != ""){
//                                         
                                                        $amtRefund = $response['entry_fee'] - $lvalue['bonus'];   
                                                        
                                                        $objUserAccountModel->updateBalance($lvalue['created_by'],$amtRefund);
                                                        $objUserAccountModel->addUserBonusAmount($lvalue['created_by'],$lvalue['bonus']);
                                                        
                                                        if(isset($amtRefund) && $amtRefund > 0){
                                                            
                                                            $transactions['user_id'] = $lvalue['created_by'];
                                                            $transactions['transaction_type'] = 'Refund';
                                                            $transactions['transaction_amount'] = $amtRefund;
                                                            $transactions['confirmation_code'] = 'N/A';
                                                            $transactions['description'] = 'Amount credit';
                                                            $transactions['status'] = '1';
                                                            $transactions['request_type'] = '4';                
                                                            $transactions['transaction_date'] = date('Y-m-d');

                                                            $transactionId = $objUserTransactionsModel->insertUseTransactions($transactions); //enter transaction to user transaction table
                                                        
                                                            if(isset($transactionId)){
                                                                //changes for contest details in transaction history (vivek 3rdOct15)
                                                                $contestTxData['transaction_id'] = $transactionId;
                                                                $contestTxData['contest_id'] = $response['contest_id'];
                                                                $contestTxData['user_id'] = $lvalue['created_by'];

                                                                
                                                                $objContestTransactionModel->insertConTransaction($contestTxData);
                                                                //end
                                                            }
                                                        } //send notification to the entered user about contest cancellation
                                                        
                                                        $notifyData = array();
                                                        $notifyData['send_to'] = $lvalue['created_by'];
                                                        $notifyData['sent_on'] = date('Y-m-d H:i:s');
                                                        $notifyData['message'] = 'Your contest has been cancelled !'.$response['contest_name']."( ".$response['start_time']." ) & Entry fee ($ ".$response['entry_fee'].") is refunded in your account";
                                                        $objNotificationModel->insertNotification($notifyData);
                                                    }
                                                }
                                        }
                                        array_push($cancelContestIds, $response['contest_id']);
                                    }
//                                    array_push($cancelContestIds, $response['contest_id']);
                                }
                            }
                        }
                    }
                }
            }
            if(!empty($cancelContestIds)){
                echo json_encode($cancelContestIds);
            }else{
                echo "0";
            }
        }
    }
    
    
         /**
         * Desc: This function will scrap dom details by given url and specific area.
         * Dev: Vinay
         * Date: 19 Aug 2014
         */
	public function domScraperAction(){	
		ini_set('max_execution_time', 0);
		$this->teams     = strtolower($this->getRequest()->getParam("teams")); 
		$this->serachurl = "http://sports.yahoo.com/{$this->teams}/players";
	   
			switch ($this->teams)
			{
				case "nfl":
				{
					   $this->teamcode = "1"; 
					   break;
				}
				case "mlb":
				{
					   $this->teamcode = "2"; 
					   break;
				}
				case "nba":
				{
					   $this->teamcode = "3"; 
					   break;
				}
				case "nhl":
				{
					   $this->teamcode = "4"; 
					   break;
				}
			}
			
			$this->defineVariables();
			
			$playerImages = array();
			//  echo "<pre>"; print_r($this->serachurl);
			//find  page url
			$html = $this->file_get_html($this->serachurl);
								   
			if($html){   
				
				foreach ($html->find('div.W(50%) a') as $tableValue) {
					
					
						if(strpos($tableValue->href, "roster")){  
								if($this->teams ==="mlb"){
										$playerImages = $this->findPlayerPageUrl($tableValue->href, $tableValue->plaintext);
								}else{
									$playerImages = $this->findPlayerPageUrl("http://sports.yahoo.com".$tableValue->href, $tableValue->plaintext);
								}
						}

				}
				
				//echo $html; 
				
				//die;
			}
			echo 'scraping finish'; die;			
    }
        
	/**
	 * Desc: find player page url
	 * Dev: Vinay 
	 * Date: 19 Aug 2014
	 * @param type $url
	 * @param type $teamName
	 * @return type
	 */
	 
	public function findPlayerPageUrl($url, $teamName) {
		
		  
		$playerImageUrls = array();
		
		//find player page url
		$html = $this->file_get_html($url); 
		
		if($html){ 
		
			foreach ($html->find('td a') as $tableValue) {
	   
				$playerPageUrl[$tableValue->title] = 'http://sports.yahoo.com/' . $tableValue->href;
				
				$playername = $tableValue->plaintext;
				
				$pageUrl = 'http://sports.yahoo.com/' . $tableValue->href;

				$teamCode = str_replace(array("http://sports.yahoo.com/{$this->teams}/teams/", "/roster"), '', $url);

				$playerImageUrls[$tableValue->title] = $this->findPlayerImageUrl($pageUrl, $playername, $teamCode, $teamName);
			}
		}
		return $playerImageUrls;
	}

	/**
	 * Desc: find player image url
	 * Dev: Vinay 
	 * Date: 19 Aug 2014
	 * 
	 * @param type $url
	 * @param type $playerName
	 * @param type $teamCode
	 * @param type $teamName
	 * @return type
	 */
	public function findPlayerImageUrl($url, $playerName, $teamCode, $teamName) {
	  
		$playerImageUrl = array();
		//find player image
		$html = $this->file_get_html($url); 
	 
		if($html){
			 
			foreach ($html->find('div.IbBox') as $tableValue) {
				
				$teamCode = str_replace('/', '', $teamCode);
				$playerImageUrl = $tableValue->style;
				
				//manage images here
				$objGamePlayers = Application_Model_GamePlayersDetails::getInstance();
				$playerImage = str_replace(array("background-image:url('", "'", ")background-color:#241773;"), '', $tableValue->style);

				//directory path
				$imagePath  = "images/players/";
				//create image name 
				$imageName = $this->teams . '_' . $teamCode . '_' . str_replace(' ', '_', $playerName);

				$playerArray = array(array('team_code' => $teamCode, 'teamName' => $teamName, 'name' => $playerName, 'playerImage' => $imagePath.$imageName . '.png'));

				//save player image in the specific directories
				// comment on 18-09-2017 uncomment below line for save player image.
				//$this->savePlayerImage($imageName, $playerImage);


				//insert player details into table
				$objGamePlayers->insertscraperdata($playerArray, $this->teamcode);

			}
		} 
		return $playerImageUrl;
	}

	/**
	 * Desc: save image in images directory
	 * Dev: Vinay 
	 * Date: 19 Aug 2014
	 * modified date 27 Aug 2014
	 * @param type $imageName
	 * @param type $playerImage
	 */
	function savePlayerImage($imageName, $playerImage) {
		
		$objDomClass = new Engine_Domparser_Simplehtmldom();
		//echo $playerImage;
		// get image from link
		$imageString = $objDomClass->file_get_contents($playerImage);
		//var_dump($html);die;
		//if file_get_contents return empty response
		if ($imageString == "") {
				
				switch ($this->teams){
						case "mlb":
						$defautlImage = "http://www.sportsbettech.com/img/buttons/btn_mlb_large_NL.png";
						break;
						case "nfl":
						$defautlImage = "https://www.nflregionalcombines.com/Images/NFLShield.png";
						break;                             
				}
				
				// get image from link
				$imageString = file_get_contents($defautlImage);

				// saved the image in specific directory          
				file_put_contents('images/players/' . $imageName . '.png', $imageString);
		} else {
				// saved the image in specific directory          
				file_put_contents('images/players/' . $imageName . '.png', $imageString);
		}
	}

	/**
	  // helper functions
	  // -----------------------------------------------------------------------------
	  // get html dom from file
	  // $maxlen is defined in the code as PHP_STREAM_COPY_ALL which is defined as -1.
	 * 
	 * @param type $url
	 * @param type $use_include_path
	 * @param type $context
	 * @param type $offset
	 * @param type $maxLen
	 * @param type $lowercase
	 * @param type $forceTagsClosed
	 * @param type $target_charset
	 * @param type $stripRN
	 * @param type $defaultBRText
	 * @param type $defaultSpanText
	 * @return boolean|\Engine_Domparser_Simplehtmldom
	 */
	 
	function file_get_html($url, $use_include_path = false, $context = null, $offset = -1, $maxLen = -1, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT) {
			// We DO force the tags to be terminated.
			
			$dom = new Engine_Domparser_Simplehtmldom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
			// For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
			$contents = $dom->file_get_contents($url);

			// Paperg - use our own mechanism for getting the contents as we want to control the timeout.
			//$contents = retrieve_url_contents($url);
			//if (empty($contents) || strlen($contents) > MAX_FILE_SIZE) {
				if (empty($contents)) {
					return false;
					 
			}
			
			// The second parameter can force the selectors to all be lowercase.
			$dom->load($contents, $lowercase, $stripRN);
		  
			return $dom;
	}

	/**
	  // get html dom from string
	 * 
	 * @param type $str
	 * @param type $lowercase
	 * @param type $forceTagsClosed
	 * @param type $target_charset
	 * @param type $stripRN
	 * @param type $defaultBRText
	 * @param type $defaultSpanText
	 * @return boolean|\Engine_Domparser_Simplehtmldom
	 */
	function str_get_html($str, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT) {
			$dom = new Engine_Domparser_Simplehtmldom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
			if (empty($str) || strlen($str) > MAX_FILE_SIZE) {
					$dom->clear();
					return false;
			}
			$dom->load($str, $lowercase, $stripRN);
			return $dom;
	}

        /**
         * dump html dom tree
         * 
         * @param type $node
         * @param type $show_attr
         * @param type $deep
         */
        function dump_html_tree($node, $show_attr = true, $deep = 0) {
                $node->dump($node);
        }

        function defineVariables() {
                define('HDOM_TYPE_ELEMENT', 1);
                define('HDOM_TYPE_COMMENT', 2);
                define('HDOM_TYPE_TEXT', 3);
                define('HDOM_TYPE_ENDTAG', 4);
                define('HDOM_TYPE_ROOT', 5);
                define('HDOM_TYPE_UNKNOWN', 6);
                define('HDOM_QUOTE_DOUBLE', 0);
                define('HDOM_QUOTE_SINGLE', 1);
                define('HDOM_QUOTE_NO', 3);
                define('HDOM_INFO_BEGIN', 0);
                define('HDOM_INFO_END', 1);
                define('HDOM_INFO_QUOTE', 2);
                define('HDOM_INFO_SPACE', 3);
                define('HDOM_INFO_TEXT', 4);
                define('HDOM_INFO_INNER', 5);
                define('HDOM_INFO_OUTER', 6);
                define('HDOM_INFO_ENDSPACE', 7);
                define('DEFAULT_TARGET_CHARSET', 'UTF-8');
                define('DEFAULT_BR_TEXT', "\r\n");
                define('DEFAULT_SPAN_TEXT', " ");
                define('MAX_FILE_SIZE', 600000);
        }

        //end scrapper

    
        /**
         * Dev :- Vivek Chaudhari
         * Desc : calculate prize for users
         * @param type<int> $contestId
         */
		public function updateTransactionForReferer($user_id,$commission,$contestId){
			$objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
			$objUserAccount = Application_Model_UserAccount::getInstance();  
			$objContestTransactionModel = Application_Model_ContestTransactions::getInstance();
			$refTransactions = array();
			$refTransactions['user_id'] = $user_id;
			$refTransactions['transaction_type'] = 'RAF Commission';
			$refTransactions['transaction_amount'] = $commission;
			$refTransactions['confirmation_code'] = 'N/A';
			$refTransactions['description'] = 'Referal amount';
			$refTransactions['status'] = '1';
			$refTransactions['request_type'] = '8';                
			$refTransactions['transaction_date'] = date('Y-m-d');
			
			$referTxnId = $objUserTransactionsModel->insertUseTransactions($refTransactions);
			if($referTxnId){
			   $objUserAccount->updateBalance($user_id,$commission);
			   $refTxData['transaction_id'] = $referTxnId;
			   $refTxData['contest_id'] = $contestId;
			   $refTxData['user_id'] = $user_id;
			   $objContestTransactionModel->insertConTransaction($refTxData);
			}
			return true;
		}
        public function contestPrize($contestId){
            
            $objUser       = Application_Model_Users::getInstance();
            $objReferUser       = Application_Model_ReferFriends::getInstance();
            $objUserLineupModel       = Application_Model_UserLineup::getInstance();
            $objTicketModel           = Application_Model_TicketSystem::getInstance();
            $objModelLineup           = Application_Model_Lineup::getInstance();
            $objUserAccount           = Application_Model_UserAccount::getInstance();
            $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();	
			$objSettingsModel 		  = Application_Model_Settings::getInstance();
			$objProfitModel 		  = Admin_Model_Profit::getInstance();
			$objAffiliteModel = Application_Model_Affiliate::getInstance();
            
            $result = $objUserLineupModel->getLineupsByContestId($contestId);
			
            $resultContest = array(); $index=0;

            if($result){
                 
                foreach($result as $rkey=>$rval){

                    $resultContest[$rval['contest_id']]['fpp']         = $rval['fpp'];    
                    $resultContest[$rval['contest_id']]['pool']        = $rval['prize_pool'];
                    $resultContest[$rval['contest_id']]['total_entry'] = $rval['total_entry'];
                    $resultContest[$rval['contest_id']]['entry_fee']   = $rval['entry_fee'];
                    $resultContest[$rval['contest_id']]['prizes']      = $rval['prizes'];
                    $resultContest[$rval['contest_id']]['play_limit']      = $rval['play_limit'];

                    if(isset($rval['prize_payouts'])){
                        $resultContest[$rval['contest_id']]['prize_payouts'] = $rval['prize_payouts'];
                    }

                    $resultContest[$rval['contest_id']]['players'][$index]['created_by'] = $rval['created_by'];
                    $resultContest[$rval['contest_id']]['players'][$index]['lineup_id']  = $rval['lineup_id'];
                    $resultContest[$rval['contest_id']]['players'][$index]['user_lineup_id']  = $rval['user_lineup_id'];
                    $resultContest[$rval['contest_id']]['players'][$index]['rank']       = $rval['con_rank'];
                    $index++;
                }
             
            }
			//echo "<pre>"; print_r($resultContest); die;
			$settings = $objSettingsModel->getSettings();
            if(!empty($resultContest)){
                 
                foreach($resultContest as $conKey=>$conVal){ //prize distribution starts on contest basis
                
                    //to add profit to the admin
                    if(isset($conVal['entry_fee']) && $conVal['entry_fee']!=0){
                                        
                        $pool = $conVal['pool'];
                        $playLimit = $conVal['play_limit'];
                        $collection = $playLimit * $conVal['entry_fee'];
                        $rake = $collection - $pool;
                        $entryRake = $rake / $playLimit;
						$commission = ($entryRake * $settings['affilate_commission'])/100; 
						$profit = (($conVal['entry_fee']*$conVal['total_entry'])-$conVal['pool']);
						
						foreach ($conVal['players'] as $player){
							$Userdata = $objUser->getUserDetailsByUserId($player['created_by']); 
							
							$ReferUser = $objReferUser->getReferByUserEmail($Userdata['email']);
							//echo "<pre>	";print_r($ReferUser); die;
							if(!empty($ReferUser)){ 
								$this->updateTransactionForReferer($ReferUser['ref_by'],$commission,$conKey);
								$profit = $profit - $commission;
							}
						}
						
                        $time = date('Y-m-d H:i:s A');
                         
						if( isset($profit) && $profit>0){
                             
                                $data['amount'] = $profit;
                                $data['date'] = $time;
                                $objProfitModel->isertProfit($data);
                        }
                    }
                   
                    //add profit function ends
                    //to add fpp points to entered contest---start
                    if(isset($conVal['players']) && is_array($conVal['players']) && isset($conVal['fpp']) && $conVal['fpp']!=0){
                         
                        $objUserAccountModel = Application_Model_UserAccount::getInstance();
                         
                        foreach($conVal['players'] as $pkey=>$pval){
                             
                            if(isset($pval['created_by'])){
                                $objUserAccountModel->updateUserFppAdded($pval['created_by'],$conVal['fpp']);
                            }
                        }
                    }//add fpp function end
                    
                    if($conVal['prizes'] != 6){ // if not custom prize
                         
                        if($conVal['prizes'] == 1){  //winner takes all condition
                             
                            $winAmout = $conVal['pool'];
                            $winners  = array();
                             
                            $firstWinCount = 0;  $r1 = 0;
                             
                            foreach($conVal['players'] as $pkey=>$pval){
                                 
                                if($pval['rank'] == 1){
                                     
                                    $firstWinCount++;
                                    $winners['firstWin']['wincount'] = $firstWinCount;
                                    $winners['firstWin']['players'][$r1] = $pval;
                                    $r1++;
                                }
                            }
							
                            foreach($winners as $wkey=>$wVal){
                                 
                                //$payPrize = number_format(($winAmout/$wVal['wincount']),2);
                                $payPrize = number_format(($winAmout/$wVal['wincount']),2,'.','');  // by prince
                                foreach($wVal['players'] as $pkey=>$pval){
                                     
                                    $user['created_by'] = $pval['created_by'];
                                    $user['user_lineup_id']  = $pval['user_lineup_id'];
                                    $prize['type']      = 0;
                                    $prize['prize']     =  $payPrize;
                                    $this->prizeUpdate($user,$prize);
                                }
                            }
                        }else if($conVal['prizes'] == 2){  //Top two wins condition

							// $winAmount = $conVal['pool'];
                            $winAmoutOneTT  = floor($conVal['pool']*0.7); //1st prize 70% of pool
                            $winAmountTwoTT = floor($conVal['pool']*0.3); // 2nd prize 30% of pool
                            $winners = array();
                             
                            $firstWinCount = 0; $secondWinCount = 0; $r1 = 0; $r2=0;
                             
                            foreach($conVal['players'] as $pkey=>$pval){
                                 
                                if($pval['rank'] == 1){
                                     
                                    $firstWinCount++;
                                    $winners['firstWin']['wincount'] = $firstWinCount;
                                    $winners['firstWin']['winAmount'] = $winAmoutOneTT;
                                    $winners['firstWin']['players'][$r1] = $pval;
                                    $r1++;
                                     
                                }else if($pval['rank'] == 2){
                                     
                                    $secondWinCount++;
                                    $winners['secondWin']['wincount'] = $secondWinCount;
                                    $winners['secondWin']['winAmount'] = $winAmountTwoTT;
                                    $winners['secondWin']['players'][$r2] = $pval;
                                     
                                    $r2++;
                                }
                            }                       
                            foreach($winners as $wkey=>$wVal){
                                $payPrize = number_format(($wVal['winAmount']/$wVal['wincount']),2,'.','');
								// $payPrize = floor($wVal['winAmount']/$wVal['wincount']);
                                 
                                foreach($wVal['players'] as $pkey=>$pval){
                                     
                                    $user['created_by'] = $pval['created_by'];
                                    $user['user_lineup_id'] = $pval['user_lineup_id'];
                                    $prize['type'] = 0;
                                    $prize['prize'] =  $payPrize;
                                    $this->prizeUpdate($user,$prize);
                                }
                            }
                        }else if($conVal['prizes'] == 3){ //top three win condition
                             
							// $winAmout = round($conVal['pool']/3,1);
                            $winAmoutOne    = floor($conVal['pool']*0.5); //1st prize 50% of pool
                            $winAmountTwo   = floor($conVal['pool']*0.3); // 2nd prize 30% of pool
                            $winAmountThree = floor($conVal['pool']*0.2); // 3rd prize 20% of pool
                             
                            $winners = array();
                             
                            $firstWinCount = 0; $secondWinCount = 0; $thirdWinCount = 0; $r1 = 0; $r2=0; $r3=0;
                             
                            foreach($conVal['players'] as $pkey=>$pval){
                                 
                                if($pval['rank'] == 1){
                                     
                                    $firstWinCount++;
                                    $winners['firstWin']['wincount']     = $firstWinCount;
                                    $winners['firstWin']['winAmount']    = $winAmoutOne;
                                    $winners['firstWin']['players'][$r1] = $pval;
                                     
                                    $r1++;
                                }else if($pval['rank'] == 2){
                                     
                                    $secondWinCount++;
                                    $winners['secondWin']['wincount']     = $secondWinCount;
                                    $winners['secondWin']['winAmount']    = $winAmountTwo;
                                    $winners['secondWin']['players'][$r2] = $pval;
                                     
                                    $r2++;
                                }else if($pval['rank'] == 3){
                                     
                                    $thirdWinCount++;
                                    $winners['thirdWin']['wincount']     = $thirdWinCount;
                                    $winners['thirdWin']['winAmount']    = $winAmountThree;
                                    $winners['thirdWin']['players'][$r3] = $pval;
                                     
                                    $r3++;
                                }
                            }
                             
                             foreach($winners as $wkey=>$wVal){
                                 
                                //$payPrize = number_format(($wVal['winAmount']/$wVal['wincount']),2);
								$payPrize = number_format(($wVal['winAmount']/$wVal['wincount']),2,'.','');//by prince
                                 
                                foreach($wVal['players'] as $pkey=>$pval){
                                     
                                    $user['created_by'] = $pval['created_by'];
                                    $user['user_lineup_id']  = $pval['user_lineup_id'];
                                    $prize['type']      = 0;
                                    $prize['prize']     =  $payPrize;
                                     
                                    $this->prizeUpdate($user,$prize);
                                }
                            }
                             
                        }else if($conVal['prizes'] == 4){ //top five win condition
                             
                            // $winAmout = round($conVal['pool']/5,1);
                            $winAmoutOne    = floor($conVal['pool']*0.3); //1st prize 30% of pool
                            $winAmountTwo   = floor($conVal['pool']*0.25); // 2nd prize 25% of pool
                            $winAmountThree = floor($conVal['pool']*0.2); // 3rd prize 20% of pool
                            $winAmountFour  = floor($conVal['pool']*0.15); // 4th prize 15% of pool
                            $winAmountFive  = floor($conVal['pool']*0.1); // 5th prize 10% of pool
                             
                            $winners = array();
                             
                            $firstWinCount = 0; $secondWinCount = 0; $thirdWinCount = 0; $fourWinCount=0; $fifthWinCount = 0; $r1 = 0; $r2=0; $r3=0; $r4=0; $r5=0;
                           
                            foreach($conVal['players'] as $pkey=>$pval){
                                 
                                 if($pval['rank'] == 1){
                                     
                                     $firstWinCount++;
                                     $winners['firstWin']['wincount'] = $firstWinCount;
                                     $winners['firstWin']['winAmount'] = $winAmoutOne;
                                     $winners['firstWin']['players'][$r1] = $pval;
                                     
                                      $r1++;
                                 }else if($pval['rank'] == 2){
                                     
                                     $secondWinCount++;
                                     $winners['secondWin']['wincount'] = $secondWinCount;
                                     $winners['secondWin']['winAmount'] = $winAmountTwo;
                                     $winners['secondWin']['players'][$r2] = $pval;
                                     
                                     $r2++;
                                 }else if($pval['rank'] == 3){
                                     
                                     $thirdWinCount++; 
                                     $winners['thirdWin']['wincount'] = $thirdWinCount;
                                     $winners['thirdWin']['winAmount'] = $winAmountThree;
                                     $winners['thirdWin']['players'][$r3] = $pval;
                                     
                                     $r3++;
                                 }else if($pval['rank'] == 4){
                                     
                                     $fourWinCount++;
                                     $winners['fourthWin']['wincount'] = $fourWinCount;
                                     $winners['fourthWin']['winAmount'] = $winAmountFour;
                                     $winners['fourthWin']['players'][$r4] = $pval;
                                     
                                     $r4++;
                                 }else if($pval['rank'] == 5){
                                     
                                     $fifthWinCount++;
                                     $winners['fifthWin']['wincount'] = $fifthWinCount;
                                     $winners['fifthWin']['winAmount'] = $winAmountFive;
                                     $winners['fifthWin']['players'][$r5] = $pval;
                                     
                                     $r5++;
                                 }
                             }
                             foreach($winners as $wkey=>$wVal){
                                 
                                 //$payPrize = number_format(($wVal['winAmount']/$wVal['wincount']),2);
                                 $payPrize = number_format(($wVal['winAmount']/$wVal['wincount']),2,'.','');  // by prince
                                 
                                 foreach($wVal['players'] as $pkey=>$pval){
                                     
                                     $user['created_by'] = $pval['created_by'];
                                     $user['user_lineup_id']  = $pval['user_lineup_id'];
                                     $prize['type']      = 0;
                                     $prize['prize']     =  $payPrize;
                                     
                                     $this->prizeUpdate($user,$prize);
                                 }
                             }
                             
                         }else if($conVal['prizes'] == 5){  //50/50 win condition...same prize for all 50/50 rankers
                             
                             $playersIn = floor($conVal['total_entry']/2);
                             
                             $winAmout = $conVal['pool'];
                             $winners  = array(); $r50 = 0;
                             
                             foreach($conVal['players'] as $pkey=>$pval){
                                 
                                   if($r50<=$playersIn){
                                       
                                     $winners['fiftyWin']['wincount'] = $playersIn;
                                     $winners['fiftyWin']['players'][$r50] = $pval;
                                     $r50++;
                                   }
                                 }
                             
                             foreach($winners as $wkey=>$wVal){
                                 
                                // $payPrize = number_format(($winAmout/$wVal['wincount']),2);
                                 $payPrize = number_format(($winAmout/$wVal['wincount']),2,'.',''); // by prince
                                 
                                 foreach($wVal['players'] as $pkey=>$pval){
                                     
                                     $user['created_by'] = $pval['created_by'];
                                     $user['user_lineup_id']  = $pval['user_lineup_id'];
                                     $prize['type']      = 0;
                                     $prize['prize']     =  $payPrize;
                                     
                                     $this->prizeUpdate($user,$prize);
                                 }
                             }
                         }
                         
                    }else if($conVal['prizes'] == 6){ // if custom prize
                         
                        if(isset($conVal['prize_payouts']) && !empty($conVal['prize_payouts'])){
                            
                        $prizepayout = json_decode($conVal['prize_payouts'],true);
                        
                        $payoutPOP   = array_pop($prizepayout);// get last index on an array ( Manoj 30th Oct )
                        
                        $maxRank     = $payoutPOP['to'];
						
						$prizepayout = json_decode($conVal['prize_payouts'],true);
                       // echo "<pre>"; print_r($conVal['players']);  die;
                        if(!empty($prizepayout)){
                            
                            foreach($prizepayout as $payout){
                            
                                if($payout['to'] != 0){
                                   
                                     foreach($conVal['players'] as $plrKey=>$plrVal){
                                         
                                         if($plrVal['rank'] >= $payout['from'] && $plrVal['rank'] <= $payout['to']){
                                             
                                             if($payout['type'] == 0){ // amount type
                                                 
                                                 if($user['rank'] <= $maxRank){ // Check user rank under range  ( Manoj 30th Oct )
                                                     $payAmt = $payout['amount']; 
                                                 }else{
                                                     $payAmt = 0;
                                                 }                                                 
                                                 
                                                 $user['created_by'] = $plrVal['created_by'];
                                                 $user['user_lineup_id']  = $plrVal['user_lineup_id'];
                                                 $prize['prize']     = $payAmt;  
                                                 $prize['type']      = 0; 
                                                 
                                                 $this->prizeUpdate($user,$prize);
                                                 
                                             }else if(isset($payout['ticket_id'])){
                                                 
                                                 if($user['rank'] <= $maxRank){ // Check user rank under range ( Manoj 30th Oct )
                                                     
                                                    $data = $objTicketModel->getTicketDetailsById($payout['ticket_id']);
                                                    $this->ticketManager($payout['ticket_id'],$plrVal['created_by']);
                                             }
                                                    
                                         }
                                     }       
                                     
                                   }      
                                   
                                }else{
                                    
                                    foreach($conVal['players'] as $plrKey=>$plrVal){

                                         if($plrVal['rank'] === $payout['from']){
                                             
                                            if($payout['type'] == 0){
                                                
                                                $user['created_by'] = $plrVal['created_by'];
                                                $user['user_lineup_id']  = $plrVal['user_lineup_id'];
                                                $prize['type']      = 0; 
                                                
//                                                if($plrVal['rank'] !=0 && $plrVal['rank'] <= $maxRank){ // Check user rank under range ( Manoj 30th Oct )
                                                     $payAmt = $payout['amount']; 
//                                                 }else{
//                                                     $payAmt = 0;
//                                                 }
                                                     
                                                $prize['prize'] = $payAmt;
                                                
                                                $this->prizeUpdate($user,$prize);
                                                
                                            }else if($payout['type'] == 1 && isset($payout['ticket_id'])){
                                               
//                                                if($user['rank'] <= $maxRank){ // Check user rank under range ( Manoj 30th Oct )
                                                     
                                                $data = $objTicketModel->getTicketDetailsById($payout['ticket_id']);
                                                
                                                $this->ticketManager($payout['ticket_id'],$plrVal['created_by']);
//                                            }
                                            
                                         }
                                            
                                     } 
                                }
                            }
                          }
                            
                        }
                        
                       }
                    }
                }
            } 
        }
        
        /**
         * Dev :- Vivek Chaudhari
         * Desc : just pass user details and prize it will update to user account and add transaction details
         */
        function prizeUpdate($user, $prize){
              
            $objModelLineup           = Application_Model_Lineup::getInstance();
            $objUserAccount           = Application_Model_UserAccount::getInstance();
            $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();
            $objUserLineupModal       = Application_Model_UserLineup::getInstance();
            $objContestTransactionModel = Application_Model_ContestTransactions::getInstance();
            $data = array('con_prize'=>$prize['prize'],'con_prize_type'=>$prize['type']);
            $objUserLineupModal->updateByLid($user['user_lineup_id'],$data);
            
            if($prize['type'] == 0){
                $usrLineupData = $objUserLineupModal->getUserLineupByID($user['user_lineup_id']);
                $contestId = $usrLineupData['contest_id'];
                $reps = $objUserAccount->updateBalance($user['created_by'],$prize['prize']);
				if($reps){
					$transactions['user_id']            = $user['created_by'];
					$transactions['transaction_type']   = 'Prize';
					$transactions['transaction_amount'] = $prize['prize'];
					$transactions['confirmation_code']  = 'N/A';
					$transactions['description']        = 'Wining Amount';
					$transactions['status']             = '1';
					$transactions['request_type']       = '5';                
					$transactions['transaction_date']   = date('Y-m-d');

					$txnId = $objUserTransactionsModel->insertUseTransactions($transactions); 
					if(isset($txnId)){
						$TxData['transaction_id'] = $txnId;
						$TxData['contest_id'] = $contestId;
						$TxData['user_id'] = $user['created_by'];
						$objContestTransactionModel->insertConTransaction($TxData);
					}
					if($prize['prize'] > 0){
						$this->facebookPost($user['created_by'],$prize['prize']);
					}
				}  
            }
        }
        
        
        function facebookPost($userId,$amount){
            $objUserModel = Application_Model_Users::getInstance();
            $objCore = Engine_Core_Core::getInstance();
            $objSettingsModel =  Application_Model_Settings::getInstance();
            $objFacebookModel = Engine_Facebook_Facebookclass::getInstance();
            
            $settings = $objSettingsModel->getSettings();
            $this->_appSetting = $objCore->getAppSetting();
            if(isset($userId) && isset($amount)){
                $response = $objUserModel->getDetailsByUserId($userId);
                $fbID = $response['fb_id'];
                $fbToken = $response['fb_token'];
                $link = $this->_appSetting->hostLink;
                $name = $this->_appSetting->title;    
                $message = "";
                $description = $settings['win_msg']." You won $".$amount; 
                if(isset($fbID) && !empty($fbID) && $fbID!=null && isset($fbToken) && !empty($fbToken) && $fbToken!=null){
                   $objFacebookModel->autopost($fbID,$fbToken,$link,$message,$name,$description);
                }
            }
        }
        
        
     /**
     * Developer    : Vivek chaudhari
     * Date         : 25/08/2014
     * Description  : update ticket user for provided ticket id
     * @param       :  <int>ticket Id.
     * @return      : 
     */ 
       function ticketManager($ticketId,$userId){
//           $userId = $this->view->session->storage->user_id; 
           $objTicketModel = Application_Model_TicketSystem::getInstance();
           
           $data = $objTicketModel->getTicketDetailsById($ticketId); 
           
           $ticketUsers = array();
           
           if(!empty($data['ticket_for'])){ 
               
               $ticketUsers = json_decode($data['ticket_for'],true); 
           }else{ 
               
               $user[] = $userId; 
               $edit = json_encode($user,JSON_FORCE_OBJECT); 
               $ok = $objTicketModel->updateTicketUsers($edit,$ticketId);
           }

           if((is_array($ticketUsers))&&(!empty($ticketUsers))){ 
               
               $check = array_search($userId, $ticketUsers);
               
               if($check === false){
                   
                  array_push($ticketUsers, $userId); 
                  $edit = json_encode($ticketUsers,JSON_FORCE_OBJECT);
                  $ok = $objTicketModel->updateTicketUsers($edit,$ticketId);
               }
           }
       }
       
       /**
        * Developer    : Vivek chaudhari
        * Date         : 10/09/2014
        * Description  : get percentage draft for contest players
        * @param       :  <int>contest Id.
        * @return      : <array>
        */ 
       function percDraftAction($conId){ 
           
          $objUserLineupModel = Application_Model_UserLineup::getInstance();
          $objLineupModel     = Application_Model_Lineup::getInstance();
          
          $lineupData   = $objUserLineupModel->getLineupsByContestId($conId);
          $allPlayerIds = array();
          
          foreach($lineupData as $lkey=>$lvalue){
              
                $lineupId     = $lvalue['lineup_id'];
                $playerData   = $objLineupModel->getPlayerIdsByLineupId($lineupId);
                $pIds         = json_decode($playerData['player_ids'],true);
                $allPlayerIds =  array_merge($allPlayerIds,$pIds);
            }
          $countArray = array_count_values($allPlayerIds);
          $count      = count($allPlayerIds);
          $index      = 0;
          
          foreach($countArray as $ckey=>$cvalue){
              
              $draftPerc = ($cvalue/$count)*100;
              
              $draftPlayerArray[$index]['player_id']  = $ckey;
              $draftPlayerArray[$index]['draft_perc'] = $draftPerc;
              $index++;
          }
          if(isset($draftPlayerArray) && !empty($draftPlayerArray)){
              
              return $draftPlayerArray;
          }
       }

       /**
        * Developer    : Chandra Sekhar Reddy
        * Date         : 08/09/2014
        * Description  : To get the contest_id and and created date by user and insert notification
       */ 
      public function notificationManagerAction(){ 
           $objContests     = Application_Model_UserLineup::getInstance();
           $objNotification = Application_Model_Notification::getInstance();
           
           $allcontests = array();
           $allcontests = $objContests->getAllContests();

             foreach($allcontests as $contests){
                 
                 $data = array();
                 
                 $data['send_to']    = $contests['created_by'];
                 $data['sent_on']    = date('Y-m-d H:i:s');
                 $data['message']    = 'Your Contest Start at '.$contests['start_time'];
                 $data['contest_id'] = $contests['contest_id'];
                 $contest_id         = $contests['contest_id'];
                 
                 $insert = $objNotification->insertNotification($data);
             }                   
       }
       
        /**
        * Developer    : Vivek Chaudhari
        * Description  : get FPPG of player
        * Date         : 23/09/2014
        * @param       : <array> player stats from game parser
        * @return      : <array> player stats with fppg details
        */
       function playerFppgUpdate($sportId, $stats){
           
           $objPlayersModel = Application_Model_GamePlayers::getInstance();
           $objParser       = Engine_Utilities_GameXmlParser::getInstance();
          
           switch($sportId){
               
               case 1 : 
                   
                   if(isset($stats) && !empty($stats)){
                       
                       foreach($stats as $teamStat){ 
                           
                        foreach($teamStat['category'] as $catkey=>$catVal){ 
                            
                            $index = 0;
                            
                            foreach($catVal['player'] as $plrKey=>$plrVal ){
                                
                                if( $catVal['name'] === "Defense"  || $catVal['name'] === "Returning"){
                                    
                                    $playerStats[$teamStat['id']][$catVal['name']][$index] = $plrVal;
                                    $index++;
                                }else{
                                    
                                    $playerStats[$plrVal['id']][$catVal['name']] = $plrVal;
                                }
                            }
                        }
                    }
                    
                    $fppgData =array();
                    
                    if(isset($playerStats) && !empty($playerStats)){
                        
                        foreach($playerStats as $pkey=>$pvalue){
                            
                            $fppgData[$pkey] = $objParser->calculateFppgNFL($pvalue);
                        }
                        if(isset($fppgData) && !empty($fppgData)){
                            
                            $objPlayersModel->bulkUpdatefppgPlayer($fppgData,1);
                        }
                    }
                 }
                    
                   break;
               case 2 :
                   if(isset($stats) && !empty($stats)){ 
                       
                    foreach($stats as $teamStat){ 
                        
                        foreach($teamStat['category'] as $catKey=>$catVal){
                            
                            if(isset($catVal['team'])){
                                
                                foreach($catVal['team']['player'] as $pkey=>$pval){
                                    
                                    $playerStats[$pval['id']][$catVal['name']] = $pval;
                                }
                            }
                        } 
                    }
                   
                    if(isset($playerStats) && !empty($playerStats)){
                        
                        foreach($playerStats as $psKey=>$psVal){
                            
                            $fppgData[$psKey] = $objParser->calculateFppgMLB($psVal);
                        }
//                        echo "<pre>"; print_r($fppgData); die; 
                        if(isset($fppgData) && !empty($fppgData)){
                            
                            $objPlayersModel->bulkUpdatefppgPlayer($fppgData,2);
                        }
                      }
                   }
                   break;
               case 3 :
                        
                if(isset($stats) && !empty($stats)){ 
                       
                    foreach($stats as $teamStat){ 
                        
                        foreach($teamStat['category'] as $catKey=>$catVal){
                            
                            if(isset($catVal['player'])){
                                
                                foreach($catVal['player'] as $pkey=>$pval){
                                    if(isset($playerStats[$pval['id']])){
                                       $playerStats[$pval['id']] =  array_merge($playerStats[$pval['id']],$pval);
                                    }else{
                                        $playerStats[$pval['id']] = $pval;
                                    }
                                    
                                }
                            }
                        } 
                    }
                    
                    if(isset($playerStats) && !empty($playerStats)){
                        
                        foreach($playerStats as $psKey=>$psVal){
                            
                            $fppgData[$psKey] = $objParser->calculateFppgNBA($psVal);
                        }
                        if(isset($fppgData) && !empty($fppgData)){
                            
                            $objPlayersModel->bulkUpdatefppgPlayer($fppgData,3);
                        }
                      }
                   }
                   break;
               case 4 :
                        if(isset($stats) && !empty($stats)){ 
//                        echo "<pre>"; print_r($stats); echo "</pre>";die;
                            foreach($stats as $teamStat){ 

                                foreach($teamStat['category'] as $catKey=>$catVal){

                                    if(isset($catVal['player'])){

                                        foreach($catVal['player'] as $pkey=>$pval){
                                            if(isset($playerStats[$pval['id']])){
                                               $playerStats[$pval['id']] =  array_merge($playerStats[$pval['id']],$pval);
                                            }else{
                                                $playerStats[$pval['id']] = $pval;
                                            }

                                        }
                                    }
                                } 
                            }
//                            echo "<pre>"; print_r($playerStats); echo "</pre>";die;
                             if(isset($playerStats) && !empty($playerStats)){
                        
                                foreach($playerStats as $psKey=>$psVal){

                                    $fppgData[$psKey] = $objParser->calculateFppgNHL($psVal);
                                }
//                                echo "<pre>"; print_r($fppgData); echo "</pre>";//die;
                                if(isset($fppgData) && !empty($fppgData)){

                                    $objPlayersModel->bulkUpdatefppgPlayer($fppgData,4);
                                }
                              }
                        }
                   break;
               default :
                   break;
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
        
        /**
        * Developer    : Vivek Chaudhari
        * Description  : update last match stats and update fppg to players
        * Date         : 23/09/2014
        * @param       : <int> sport Id <array> player stats data of match stats
        */
		function updatePlayerPointStats($sportsId,$matchStats){
             
            $objGamePlayers = Application_Model_GamePlayers::getInstance(); 
            
            $lastStats = array(); $index = 0;
             if(!empty($matchStats)){
                 
                 foreach($matchStats as $mtKey=>$mtValue){
                    $lastStats[$mtValue['id']][$mtValue['type']][$index] = $mtValue;
                    $index++;
                }
               
                switch ($sportsId) {
                    case 1 : // 'NFL':
                        $points = $this->calculateNFLPointsFunctions($matchStats); 
                        break;
                    case 2 : // 'MLB':
                        $points = $this->calculateMLBPointsFunctions($matchStats);
                        break;
                    case 3 : //'NBA':
                        $points = $this->calculateNBAPointsFunctions($matchStats);
                        break;
                    case 4 : //'NHL':
                        $points = $this->calculateNHLPointsFunctions($matchStats);
                        break;
                } 
                
             }      
            if(isset($points) && !empty($points)){ 
               $objGamePlayers->bulkUpdatePlayerFpts($points,$sportsId);
            }         
            if(isset($lastStats) && !empty($lastStats)){
                $objGamePlayers->bulkUpdatePlayerStats($lastStats,$sportsId);
            }
        }
        
        /**
        * Developer : Vivek Chaudhari
        * Desc : headcracker to create virtual lineups for contest with different sport 
         * @params <int> $reqLineup-> provide the number of lineups required
         * @param <int> $contestID
        */
        function ManageVirtualPlayers($reqLineup,$contestId){
            
            if($contestId){
                
                $objContestModel = Application_Model_Contests::getInstance();
                $objParser       = Engine_Utilities_GameXmlParser::getInstance();
                $objLineUp       = Application_Model_Lineup::getInstance();
                $objSportsModel  = Application_Model_Sports::getInstance();
                $objGamePlayers  = Application_Model_GamePlayers::getInstance();
                $objAbbreviation = Engine_Utilities_Abbreviations::getInstance();
                $objGameStats    = Application_Model_GameStats::getInstance();
                $conDetails      = $objContestModel->getContestsDetailsById($contestId);
                
                $game_date = date('Y-m-d', strtotime($conDetails['start_time']));
                
                switch ($conDetails['sports_id']) {
                    case 1:
                        $response = $objGameStats->getGameStats($conDetails['sports_id'], $game_date);
                        $abbreviation = $objAbbreviation->getNFLAbbreviations(); // get team Abbreviations
                        break;
                    case 2:
                        $response = $objGameStats->getGameStats($conDetails['sports_id'], $game_date);
                        $abbreviation = $objAbbreviation->getMLBAbbreviations(); // get team Abbreviations
                        break;
                    case 3:
                        $response = $objGameStats->getGameStats($conDetails['sports_id'], $game_date);
                   
                        $abbreviation = $objAbbreviation->getNBAAbbreviations(); // get team Abbreviations
                        break;
                    case 4:
                        $response = $objGameStats->getGameStats($conDetails['sports_id'], $game_date);
                        $abbreviation = $objAbbreviation->getNHLAbbreviations(); // get team Abbreviations
                        break;

                    default:
                        break;
                }

                if (isset($response)) {
             
                    
                    $contest_res = json_decode($response['game_stat'], true);
                    
                    $contestDate = strtotime(date('Y-m-d', strtotime($conDetails['start_time'])));
                    
                    if (isset($abbreviation)) {
                 
                        $abbreviation = (array) json_decode($abbreviation);
                       
                      
                        $teamCode = array();
                        $team = array();
                        $i = 0;
                        
                        //create array to get team code for hometeam and away team
                        foreach ($contest_res['match'] as $matchDetails) { 
                            
                            $hometeamName = array_search($matchDetails['hometeam']['name'], $abbreviation);
                            $awayteamName = array_search($matchDetails['awayteam']['name'], $abbreviation); 
                        
                            $teamCode[$i]['time']             = $matchDetails['formatted_date'].$matchDetails['time'];
                            $teamCode[$i]['hometeam']['name'] = $hometeamName;
                            $teamCode[$i]['hometeam']['id']   = $matchDetails['hometeam']['id'];
                            $teamCode[$i]['awayteam']['name'] = $awayteamName;
                            $teamCode[$i]['awayteam']['id']   = $matchDetails['awayteam']['id'];
                            
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


                            // merge hometeam and away team to get players  "TEST1
                            $mergeTeamName = array_merge($hometeam, $awayteam);
                            $teamString    = implode("','", $mergeTeamName);
                  
                            $playerLists = $objGamePlayers->getPlayersByGameTeam($conDetails['sports_id'], $teamString);
                   
                            switch ($conDetails['sports_id']) {
                                case 1:
                                        $virtualLineup = $this->createNflVirtualLineup($reqLineup,$playerLists);
                                    break;
                                case 2 :
                                        $virtualLineup = $this->createMlbVirtualLineup($reqLineup,$playerLists);
                                    break;
                                case 3 :
                                        $virtualLineup = $this->createNbaVirtualLineup($reqLineup,$playerLists);
                                    break;
                                case 4 :
                                        $virtualLineup = $this->createNhlVirtualLineup($reqLineup,$playerLists);
                                    break;

                                default:
                                    break;
                            }
                            
                            if(isset($virtualLineup) && is_array($virtualLineup) && !empty($virtualLineup)){
                               
                                $objLineupModel     = Application_Model_Lineup::getInstance();
                                $objUserLineupModel = Application_Model_UserLineup::getInstance();
                                
                                foreach($virtualLineup as $vlVal){
                                    
                                    $lineupData = array( 
                                                        'sports_id'=>$conDetails['sports_id'],
                                                        'start_time'=>$conDetails['start_time'],
                                                        'player_ids'=> json_encode($vlVal),
                                                        'created_by'=>4,
                                                        'prize_type'=>0
                                                        );
                                    
                                    $lineupId = $objLineupModel->insertLineup($lineupData);
                                    
                                    if(isset($lineupId)){
                                        
                                        $createDate     = date('Y-m-d H:i:s');
                                        
                                        $userLineupData = array(
                                                            'lineup_id'=>$lineupId,
                                                            'contest_id'=>$contestId,
                                                            'status'=>1,
                                                            'created_date'=>$createDate
                                                          );
                                        
                                        $objUserLineupModel->inserUserLineup($userLineupData);
                                        
                                    }
                                    
                                    $objContestModel->updateTotalEntry($contestId); // update contest entry
                                }
                            }
                            
                        }
                    }
                }
            }
        }
        /**
        * Developer : Vivek Chaudhari
        * Desc : function for array order just pass arguments and array , you will get provided order array
        */
        function array_orderby(){
            
                $args = func_get_args();
                $data = array_shift($args);
                
                foreach ($args as $n => $field) {
                    
                    if (is_string($field)) {
                        
                        $tmp = array();
                        
                        foreach ($data as $key => $row)
                            $tmp[$key] = $row[$field];
                            $args[$n] = $tmp;
                    }
                }
                
                $args[] = &$data;
                
                call_user_func_array('array_multisort', $args);
                
                return array_pop($args);
            }
          
        /**
        * Developer    : Vivek Chaudhari
        * Description  : get NFL virtual lineups according to required numbers
        */     
        function createNflVirtualLineup($reqLineup,$playerLists){
            
            $objParser       = Engine_Utilities_GameXmlParser::getInstance();
            $lineupDetails   = $objParser->lineupDetails(1);
           
            
            $unqLineup = array();
            
            foreach($lineupDetails as $lval){
                
                if(array_search($lval, $unqLineup) === false){
                    array_push($unqLineup,$lval);
                }
            }
            
            $posCount = array_count_values($lineupDetails);
            
            $allPlayers = array(); $i=0;
            
            foreach($playerLists as $pkey=>$pval){
                
                $allPlayers[$i]['id']       = $pval['plr_id'];
                $decode                     = json_decode($pval['plr_details'],true);
                $allPlayers[$i]['pos_code'] = $decode['pos_code'];
                $allPlayers[$i]['fpts']     = $pval['fpts'];
//                $allPlayers[$i]['details'] = $pval['plr_details'];
                $i++;
            }
            
            $index = 0;
            
             foreach($unqLineup as $ulval){
                 
                $selectedPlayers = $objParser->filterArray($ulval,$allPlayers,'pos_code');
                $eachCount       =  $posCount[$ulval];
                $reqPlayersCount = $eachCount * $reqLineup;
                
                $sorted = $this->array_orderby($selectedPlayers, 'fpts', SORT_DESC);
                
                array_splice($sorted, $reqPlayersCount);
                
                $selectedPlayer[$index]['position'] = $ulval;
                $selectedPlayer[$index]['players']  = $sorted;
                $index++;
            }
        }    
        /**
        * Developer    : Vivek Chaudhari
        * Description  : get MLB virtual lineups according to required numbers
        */ 
        function createMlbVirtualLineup($reqLineup,$playerLists){
            
            $objParser       = Engine_Utilities_GameXmlParser::getInstance();
            $lineupDetails   = $objParser->lineupDetails(2);
            
            $unqLineup = array();
            
            foreach($lineupDetails as $lval){
                
                    if(array_search($lval, $unqLineup) === false){
                        array_push($unqLineup,$lval);
                    }
            }
            
            $posCount = array_count_values($lineupDetails);

            $allPlayers = array(); $i=0;
            
            foreach($playerLists as $pkey=>$pval){
                
                $allPlayers[$i]['id']       = $pval['plr_id'];
                $decode                     = json_decode($pval['plr_details'],true);
                $allPlayers[$i]['pos_code'] = $decode['pos_code'];
                $allPlayers[$i]['fpts']     = $pval['fpts'];
                $allPlayers[$i]['details']  = $pval['plr_details'];
                $i++;
            }
            
            $index = 0;
            
             foreach($unqLineup as $ulval){
                 
                $selectedPlayers = $objParser->filterArray($ulval,$allPlayers,'pos_code');
                $eachCount       =  $posCount[$ulval];
                $reqPlayersCount = $eachCount * $reqLineup;
                
                $sorted = $this->array_orderby($selectedPlayers, 'fpts', SORT_DESC);
                
                array_splice($sorted, $reqPlayersCount);
                
                $selectedPlayer[$index]['position'] = $ulval;
                $selectedPlayer[$index]['players'] = $sorted;
                $index++;
            }
//        echo "required->".$reqLineup;    
            for($i= 1; $i<=$reqLineup; $i++){
                
                $lineup = array();
                
                foreach($selectedPlayer as $skey=>$sval){
                    
                    $eachCount  =  $posCount[$sval['position']];                    
                    $randPlayer = array_rand($sval['players'],$eachCount);
                    
                    if(isset($randPlayer) && is_array($randPlayer)){
                        
                        foreach($randPlayer as $rval){
                            
                           array_push($lineup, $sval['players'][$rval]['id']);
                        }
                    }else{
                        
                           array_push($lineup, $sval['players'][$randPlayer]['id']);
                    }
                }
                $virtualLineup[] = $lineup;
            }
 
            if(isset($virtualLineup)){
                
                return $virtualLineup;
            }
        }
        /**
        * Developer    : Vivek Chaudhari
        * Description  : get NBA virtual lineups according to required numbers
        */ 
        function createNbaVirtualLineup($reqLineup,$playerLists){
            
            $objParser       = Engine_Utilities_GameXmlParser::getInstance();
            $lineupDetails   = $objParser->lineupDetails(3);
            
            $unqLineup = array();
            
            foreach($lineupDetails as $lval){
                
                if(array_search($lval, $unqLineup) === false){
                    
                    array_push($unqLineup,$lval);
                }
            }
            
            $posCount = array_count_values($lineupDetails);
            
            $allPlayers = array(); $i=0;
            
            foreach($playerLists as $pkey=>$pval){
                
                $allPlayers[$i]['id']       = $pval['plr_id'];
                $decode                     = json_decode($pval['plr_details'],true);
                $allPlayers[$i]['pos_code'] = $decode['pos_code'];
                $allPlayers[$i]['fpts']     = $pval['fpts'];
                $allPlayers[$i]['details']  = $pval['plr_details'];
                $i++;
            }
             
            $index = 0;
            
             foreach($unqLineup as $ulval){
                 
                 $selectedPlayers = array();
                 
                 if($ulval === "G"){
                     
                     $specialPlayer          = array("PG","SG");
                     $specialSelectedPlayers = array();
                     
                    foreach($specialPlayer as $pVal){
                        
                        $specialSelectedPlayers = $objParser->filterArray($pVal,$allPlayers,'pos_code');
                        $selectedPlayers        = array_merge($selectedPlayers,$specialSelectedPlayers);
                    }
                    
                 }else if($ulval === "F"){
                     
                     $specialPlayer          = array("PF","SF");
                     $specialSelectedPlayers = array();
                     
                    foreach($specialPlayer as $pVal){
                        
                        $specialSelectedPlayers = $objParser->filterArray($pVal,$allPlayers,'pos_code');
                        $selectedPlayers        = array_merge($selectedPlayers,$specialSelectedPlayers);
                    }
                    
                 }else if($ulval === "UTIL"){
                     
                     $specialPlayer          = array("PG","SG","PF","SF");
                     $specialSelectedPlayers = array();
                     
                    foreach($specialPlayer as $pVal){
                        
                        $specialSelectedPlayers = $objParser->filterArray($pVal,$allPlayers,'pos_code');
                        $selectedPlayers        = array_merge($selectedPlayers,$specialSelectedPlayers);
                    }
                 }else{
                     
                     $selectedPlayers = $objParser->filterArray($ulval,$allPlayers,'pos_code');
                 }
//                $reqLineup = 5;
                $eachCount       = $posCount[$ulval];
                if($reqLineup==1){$eachCount = $eachCount+1;}
                $reqPlayersCount = $eachCount * $reqLineup;
                
                $sorted = $this->array_orderby($selectedPlayers, 'fpts', SORT_DESC);
                
                array_splice($sorted, $reqPlayersCount);
                
                $selectedPlayer[$index]['position'] = $ulval;
                $selectedPlayer[$index]['players']  = $sorted;
                $index++;
            }
            $index=0;
            for($i=1; $i<=$reqLineup; $i++){
                
                $lineup =  array();
                $posLineup = array();
                foreach($selectedPlayer as $sVal){
                    
                    do{
                        
                        $randomPlayerKey = array_rand($sVal['players']);
                        $randomPlayer    = $sVal['players'][$randomPlayerKey];                       
                        $check           = array_search($randomPlayer['id'], $lineup);
                        if($check === false){
                            array_push($lineup,$randomPlayer['id']);
                            $posLineup[$randomPlayer['id']] = $sVal['position'];
                        }
                      
                    }while($check != false);
                }
               
                $virtualLineup[$index]['id'] = $lineup;
                $virtualLineup[$index]['position'] = $posLineup;
                $index++;
            }
            if(isset($virtualLineup)){
                return $virtualLineup;
            }else{
                return null;
            }
            
        }
        /**
        * Developer    : Vivek Chaudhari
        * Description  : get NHL virtual lineups according to required numbers
        */ 
        function createNhlVirtualLineup($reqLineup,$playerLists){
            
            $objParser       = Engine_Utilities_GameXmlParser::getInstance();
            $lineupDetails   = $objParser->lineupDetails(4);
            
            $unqLineup = array();
            
            foreach($lineupDetails as $lval){
                
                if($lval != 'UTIL'){
                    
                    if(array_search($lval, $unqLineup) === false){
                        
                        array_push($unqLineup,$lval);
                    }
                }
            }
            
            $posCount   = array_count_values($lineupDetails);            
            
            $allPlayers = array(); $i=0;
            
            foreach($playerLists as $pkey=>$pval){
                
                $allPlayers[$i]['id']       = $pval['plr_id'];
                $decode                     = json_decode($pval['plr_details'],true);
                $allPlayers[$i]['pos_code'] = $decode['pos_code'];
                $allPlayers[$i]['fpts']     = $pval['fpts'];
                $allPlayers[$i]['details']  = $pval['plr_details'];
                $i++;
            }

            $sindex = 0;
            foreach($unqLineup as $ulval){
                
                $selectedPlayers = $objParser->filterArray($ulval,$allPlayers,'pos_code');
                $eachCount       =  $posCount[$ulval];
                if($reqLineup == 1){ $eachCount = $eachCount+ 2;}
                $reqPlayersCount = $eachCount * $reqLineup;
                
                $sorted = $this->array_orderby($selectedPlayers, 'fpts', SORT_DESC);
                array_splice($sorted, $reqPlayersCount);
                
                $selectedPlayer[$sindex]['position'] = $ulval;
                $selectedPlayer[$sindex]['players']  = $sorted;
                $sindex++;
            }
           $index=0;
           
            for($i= 1; $i<=$reqLineup; $i++){
                
                $lineup = array();
                $posLineup = array();
                foreach($selectedPlayer as $skey=>$sval){
                    
                    $eachCount =  $posCount[$sval['position']];
                    
                    if($sval['position'] === "C"){$eachCount = $eachCount +1; /*for utility players */}

                    $randPlayer = array_rand($sval['players'], $eachCount);
                    if (isset($randPlayer) && is_array($randPlayer)) {
                        foreach ($randPlayer as $rval) {
                            array_push($lineup, $sval['players'][$rval]['id']);
                            $posLineup[$sval['players'][$rval]['id']] = $sval['position'];
                        }
                    } else {
                        array_push($lineup, $sval['players'][$randPlayer]['id']);
                        $posLineup[$sval['players'][$randPlayer]['id']] = $sval['position'];
                    }
                }
                $find = array_search("C",$posLineup);
                if($find){$posLineup[$find] = "UTIL";} // make on UTIL position
                
                $virtualLineup[$index]['id'] = $lineup;
                $virtualLineup[$index]['position'] = $posLineup;
                $index++;
            }
            if(isset($virtualLineup)){
                return $virtualLineup;
            }
        }
        
        
   /**
        * Developer    : priyanka varanasi
        * Date         : 30-12-2014
        * Description  : Get registerd emails id to show 
       */
    public function   registerdEmailsAction(){//per day
        $objReferalModel = Static_Model_Referals::getInstance();
        $objSettingsModel = Application_Model_Settings::getInstance(); 
        $objUserModel = Application_Model_Users::getInstance();
        
        $result = $objUserModel->getallemailsregisterd();
       
        $refemails= $objReferalModel->getrefersemails();
        $user= array();
        $usres=array();
        if(!empty($refemails)){
        foreach ($refemails as $key => $value) {
           $user[$key] = $value['email'];
       }
        }
        if(!empty($result)){
      foreach ($result as $key => $pvalue) {
           $usres[$key] = $pvalue['email'];
       }
      }
      echo"****************** list of   referal emails********************";
       echo"<pre>";print_r($user);echo"</pre>";
        echo"****************** list of  emails registerd********************";
       echo"<pre>";print_r($usres);echo"</pre>";
      
       $ref = array_intersect($user, $usres);
      echo"****************** list of  emails registerd in site  through invitaion ********************";
       echo"<pre>";print_r($ref);echo"</pre>";
       if(!empty($ref)){
         $objReferalModel->changeacceptancetoone($ref);  
       }     
        
    }
    
    /**
     * Developer : Vivek Chaudhari
     * Desc : gives the lineups by providing contest details and required lineups
     * @return <array> lineups array
     */
    function getplayerLineups($reqLineup, $value) {
        $objLineUp = Application_Model_Lineup::getInstance();
        $objSportsModel = Application_Model_Sports::getInstance();
        $objGamePlayers = Application_Model_GamePlayers::getInstance();
        $objAbbreviation = Engine_Utilities_Abbreviations::getInstance();
        $objGameStats = Application_Model_GameStats::getInstance();
        $objContest = Application_Model_Contests::getInstance();
        $gameDate = date('Y-m-d', strtotime($value['start_time']));
        switch ($value['sports_id']) {

            case 1:
                $statsresponse = $objGameStats->getGameStats($value['sports_id'], $gameDate);
                $teamabbreviation = $objAbbreviation->getNFLAbbreviations(); // get team Abbreviations
                break;
            case 2:
                $statsresponse = $objGameStats->getGameStats($value['sports_id'], $gameDate);
                $teamabbreviation = $objAbbreviation->getMLBAbbreviations(); // get team Abbreviations
                break;
            case 3:
                $statsresponse = $objGameStats->getGameStats($value['sports_id'], $gameDate);

                $teamabbreviation = $objAbbreviation->getNBAAbbreviations(); // get team Abbreviations
                break;
            case 4:
                $statsresponse = $objGameStats->getGameStats($value['sports_id'], $gameDate);
                $teamabbreviation = $objAbbreviation->getNHLAbbreviations(); // get team Abbreviations
                break;

            default:
                break;
        }         
        if (isset($statsresponse)) {
            $gamestat_res = json_decode($statsresponse['game_stat'], true);

            $contestDate = strtotime(date('Y-m-d', strtotime($value['start_time'])));
            if (isset($teamabbreviation)) {
                $Tabbrevations = (array) json_decode($teamabbreviation);
                $teamAbb = array();
                $group = array();
                $j = 0;

                //create array to get team code for hometeam and away team
                foreach ($gamestat_res['match'] as $matchinfo) {

                    $hometeamNaming = array_search($matchinfo['hometeam']['name'], $Tabbrevations);

                    $awayteamNaming = array_search($matchinfo['awayteam']['name'], $Tabbrevations);

                    $teamAbb[$j]['time'] = $matchinfo['formatted_date'] . $matchinfo['time'];
                    $teamAbb[$j]['hometeam']['name'] = $hometeamNaming;
                    $teamAbb[$j]['hometeam']['id'] = $matchinfo['hometeam']['id'];
                    $teamAbb[$j]['awayteam']['name'] = $awayteamNaming;
                    $teamAbb[$j]['awayteam']['id'] = $matchinfo['awayteam']['id'];

                    $group[$hometeamNaming] = $awayteamNaming;
                    $group[$awayteamNaming] = $hometeamNaming;
                    $j++;
                }
                if (!empty($teamAbb)) {

                    $groupIds = array();

                    foreach ($teamAbb as $pkey => $pvalue) {

                        $groupIds[$pvalue['hometeam']['name']] = $pvalue['hometeam']['id'];
                        $groupIds[$pvalue['awayteam']['name']] = $pvalue['awayteam']['id'];
                    }

                    $hometeam = array_map(function($item) {
                                return strtolower($item['hometeam']['name']);
                            }, $teamAbb);

                    $awayteam = array_map(function($item) {
                                return strtolower($item['awayteam']['name']);
                            }, $teamAbb);


                    // merge hometeam and away team to get players  "TEST1
                    $mergegroupTeamName = array_merge($hometeam, $awayteam);
                    $contestteamString = implode("','", $mergegroupTeamName);

                    $playerLists = $objGamePlayers->getPlayersByGameTeam($value['sports_id'], $contestteamString);

                    switch ($value['sports_id']) {
                        case 1:
                            $virtualcontestLineup = $this->createNflVirtualLineup($reqLineup, $playerLists);
                            break;
                        case 2 :
                            $virtualcontestLineup = $this->createMlbVirtualLineup($reqLineup, $playerLists);
                            break;
                        case 3 :
                            $virtualcontestLineup = $this->createNbaVirtualLineup($reqLineup, $playerLists);
                            break;
                        case 4 :
                            $virtualcontestLineup = $this->createNhlVirtualLineup($reqLineup, $playerLists);
                            break;

                        default:
                            break;
                    }
                    if (isset($virtualcontestLineup) && is_array($virtualcontestLineup) && !empty($virtualcontestLineup)) {
                        return $virtualcontestLineup;
                    }
                }
            }
        }
    }
   
   /**
    * Developer    : Vivek Chaudhari
    * Description  : virtual players logic
   */ 
   public function botPlayersAction(){ 
       $objUsersModel = Application_Model_Users::getInstance();
       $objContestModel = Application_Model_Contests::getInstance();
       $objLineupModel = Application_Model_Lineup::getInstance();
       $objUserLineupModel = Application_Model_UserLineup::getInstance();

       $contests = $objContestModel->getActiveUpcomingContest();
       if(isset($contests) && !empty($contests)){
           foreach($contests as $conVal){
               if($conVal['total_entry'] < $conVal['play_limit']){
                   $reqLineups = $conVal['play_limit']-$conVal['total_entry'];
                   $users = $objUsersModel->getBotPlayers();
                   $userLineups = $this->getplayerLineups($reqLineups,$conVal);
                   $contestName = $conVal['contest_name'];
                   foreach($users as $uval){
                        if(isset($uval['email']) && isset($uval['fb_pwd']) && isset($uval['fb_id']) && $uval['fb_id']!=0){
                            
                            //to check the user already joined the contest or not
                            $check = $objLineupModel->getLineupByUidAndConId($uval['user_id'],$conVal['contest_id']);
                           
                           if($check == 0){
                            $data['contest'] = $contestName;
                            $data['prize'] = $conVal['prize_pool'];
                            $data['username'] = $uval['user_name']; 
                            $posted = $this->wallpost($uval['email'],$uval['fb_pwd'],$uval['fb_id'],$data);
                            $posted= json_decode($posted,true);
                            if(!isset($posted['error']) && isset($posted['id'])){
                                $gkey = array_rand($userLineups);
                                $myLineup = $userLineups[$gkey];
                                if($myLineup){
                                        $lineupData = array( 
                                                'sports_id'=>$conVal['sports_id'],
                                                'start_time'=>$conVal['start_time'],
                                                'player_ids'=> json_encode($myLineup['id'],true),
                                                'pos_details'=>  json_encode($myLineup['position'],true),
                                                'created_by'=>$uval['user_id'],
                                                'prize_type'=>0
                                                );
                                        $virtuallineupId = $objLineupModel->insertLineup($lineupData);
                                    
                                        if (isset($virtuallineupId)) {
    //                                        
                                            $generateDate = date('Y-m-d H:i:s');

                                            $userlineup = array(
                                                'lineup_id' => $virtuallineupId,
                                                'contest_id' => $conVal['contest_id'],
                                                'status' => 1,
                                                'created_date' => $generateDate
                                            );

                                            $userlineupid = $objUserLineupModel->inserUserLineup($userlineup);
                                            unset($userLineups[$gkey]);
                                            $objContestModel->updateTotalEntry($conVal['contest_id']);
                                            echo "User->".$uval['user_name']."=>email->".$uval['email']."=>pwd->".$uval['fb_pwd']." joined the contest :-".$contestName."<br/>";
                                        }
                                    }
                                }else if(isset($posted['error'])){
                                    if($posted['error']['code'] == 200){
                                        echo "User->".$uval['user_name']."=>email->".$uval['email']."=>pwd->".$uval['fb_pwd']." :-".$posted['error']['message']."<br/>";
                                    }
                                }
                        }else{
                            echo "user ".$uval['user_name']. " Already Joined (".$contestName.") contest </br>";
                        }
                            }else{
                                echo "Error :- user ".$uval['user_name']. " cannot join the contest (".$contestName.") because of Technical isssue </br>";
                            }
                        }
                    }else{
                        echo "Player entry for contest '".$conVal['contest_name']."' Already filled. <br/>";
                    }
               }
           }else{
               echo "No Active Upcoming contest Available";
           }
   }
   
   function wallpost($fbemail,$fbpwd,$fbid,$data){
        $objCore = Engine_Core_Core::getInstance();
        $this->_appSetting = $objCore->getAppSetting();
        $app_id = $this->_appSetting->facebookId;
        $secret = $this->_appSetting->facebookSecret;
//        $app_id = '795239007199676';
//        $secret = '03114685d81b5790493ba38a3aafde31';
        $cookie=null;
        $a = $this->cURL("https://login.facebook.com/login.php?login_attempt=1",true,null,"email=$fbemail&pass=$fbpwd");

        preg_match('%Set-Cookie: ([^;]+);%',$a,$b);
        $c = $this->cURL("https://login.facebook.com/login.php?login_attempt=1",true,$b[1],"email=$fbemail&pass=$fbpwd");
        preg_match_all('%Set-Cookie: ([^;]+);%',$c,$d);
        for($i=0;$i<count($d[0]);$i++)
         $cookie.=$d[1][$i].";";
        
        
//        $url = 'https://graph.facebook.com/oauth/access_token';
        $token_params = array(
            'type' => "client_cred",
            'client_id' => $app_id,
            'client_secret' => $secret
            );
    
        $urltoken = $this->cURL("https://graph.facebook.com/oauth/access_token",null,$cookie,$token_params);
        
        $tokenArray = explode("=", $urltoken);
        
        $token2 = $tokenArray[1]; //'CAAWDJVS1WA8BADZCmNPyEZBDwCTOczAB0gmbd2QVZA8XdSUlZAWl9BWC5b9NnP5lyQEROH5DyCsljaTLe1hf7ZCnHNcq8e7k5n8h5139a4jd3z6HAHchw6QFZAclD6tPvj3vIoXGNizAKOXfrCGC0pTBb5ZALwbhTzIGrspSZAfTA2ZBQpu4tdtp0LuR9e9CC3PQiNQhHXGIwZBHdCUlLqh4GX';
        $touid = $fbid; 
        $msg = $data['username']." entered a Contest '".$data['contest']."' to win $".$data['prize'];
        $title= $data['contest'];
        $uri = $this->_appSetting->hostLink; //"http://draftoff.globusapps.com/";
        $desc = "The best place to play daily fantasy sports for cash prizes. We offer the biggest money pools for all of the major pro and college sports.";
        $pic = $this->_appSetting->hostLink . $this->_appSetting->assestPath."images/logo-large@2x1.png";// 'http://draftoff.globusapps.com/assets/images/logo-large@2x1.png';
        $link = $this->_appSetting->hostLink;// 'http://draftoff.globusapps.com/';

        $url = "https://graph.facebook.com/".$touid."/feed";
        $attachment =  array(
            'access_token' => $token2,
            'message' => $msg,
            'name' => $title,
            'link' => $uri,
            'description' => $desc,
            'picture'=>$pic,
            'actions' => json_encode(array('name' => "Play now",'link' => $link))
        );
        
        $check = $this->cURL($url,null,$cookie,$attachment);
        if($check){
            return $check;
        }
   }
   /**
     * Developer : Vivek Chaudhari
     * Desc : Curl function for explicit facebook login and facebook post 
     */
   function cURL($url, $header=NULL, $cookie=NULL, $p=NULL){
       $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt($ch, CURLOPT_NOBODY, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        if ($p) { //if you want to post send $p all required variables
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $p);
        }
        $result = curl_exec($ch);

        if ($result) {
            return $result;
        } else {
            return curl_error($ch);
        }
        curl_close($ch);
    }
    
   /**
    * Developer    : Vivek Chaudhari
    * Description  : get rank of lineups by contest 
   */ 
   public function getRankByContest($contestId){
       
        $objLineUp  = Application_Model_Lineup::getInstance();
        $objUserLineup = Application_Model_UserLineup::getInstance();
        if($contestId){
             $result = $objUserLineup->getLineupsByConId($contestId);
        }
        
        if(isset($result)) {
            
            $contestResults = array();
            $index = 0;
            $value=array();foreach ($result as  $key=>$row){$value[$key]=$row['players_points'];}
            array_multisort($value, SORT_DESC,$result);
            foreach ($result as $conKey => $conVal) {
                
                $contestResults[$conVal['contest_id']][$index]['players_points'] = $conVal['players_points'];
                $contestResults[$conVal['contest_id']][$index]['lineup_id'] = $conVal['lineup_id'];
                $contestResults[$conVal['contest_id']][$index]['user_lineup_id'] = $conVal['user_lineup_id'];
                $index++;
            }
            if (!empty($contestResults)) {
                
                foreach ($contestResults as $rkey => $rval) {
                    
                    $i = 0; $rank=array();
                    
                    foreach ($rval as $key => $value) {

                        if (isset($rank[$i - 1]['rank'])) {

                            $rankVal = $rank[$i - 1]['rank'];
                            
                            if ($rank[$i - 1]['value'] == $value['players_points']) {
                                
                                $rank[$i]['rank'] = $rankVal;
                                $plr_rank = $rankVal;
                            } else {
                                
                                $rank[$i]['rank'] = $rankVal + 1;
                                $plr_rank = $rankVal + 1;
                            }
                        } else {

                            $plr_rank = 1;
                            $rank[$i]['rank'] = $plr_rank;
                        } 

                        $rank[$i]['value'] = $value['players_points'];

                        $userLinepId = $value['user_lineup_id'];
                        $data   = array('con_rank' => $plr_rank);
                        $objUserLineup->updateByLid($userLinepId,$data);
                        $i++;
                    } 
                    unset($rank);

                }
            }
        }
        
    }
}
