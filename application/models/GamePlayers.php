<?php
class Application_Model_GamePlayers extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'game_players';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_GamePlayers();
		return self::$_instance;
    }
    /**
    * Developer     : Vivek Chaudhari   
    * Date          : 10/06/2014
    * Description   : insert palyer details or update details if player already exist
    * @params       : <array> player details <int> sport id
    */
    public function bulkInsert(){
        
        if(func_num_args()>0):
            $playerArray = func_get_arg(0);
            $sports_id = func_get_arg(1);
            //to reset player status before updating players coming from roasters
            $reset = array('status'=>0);
            $this->update($reset, "sports_id =".$sports_id);
            foreach($playerArray as $playerDetails):

                $plr_id        = $playerDetails['id'];
                $plr_name      = $playerDetails['name'];
                $plr_team_coad = strtolower($playerDetails['team_code']);
                $plr_position  = $playerDetails['position'];
                $plr_details   = json_encode($playerDetails);
                $last_update   = date('Y-m-d H:i:s');

                $playervalues = array('sports_id' =>$sports_id,
                                      'plr_details'=>$plr_details,
                                      'plr_id'=>$plr_id,
                                      'plr_name'=>$plr_name,
                                      'plr_team_code' =>$plr_team_coad,
                                      'plr_position'=>$plr_position,
                                      'status'=>1,
                                      'last_update'=>$last_update);
    
                 
                 $sql = $this->select()
                             ->from($this,array('gmp_id'))
                             ->where('sports_id = ?',$sports_id)
                             ->where('plr_id = ?',$plr_id);

				$id = $this->getAdapter()->fetchRow($sql);
				if($id):
					$this->update($playervalues, "gmp_id =".$id['gmp_id']);
				else:
					
					$this->insert($playervalues);
				endif;

			endforeach;
            
        else:
            throw new Exception('Argument not passed');
        endif;

    }
	
	
     /**
     * developer    :- vivek chaudhari
     * date         :- 10/10/2014
     * description  :- bulk update fppg points of players
     * @params      :- param1<array> = player stats
     */
    public function updatePlayersSalary(){
		if(func_num_args()>0){
			$playerfppgDetail = func_get_arg(0);
			$sportId = func_get_arg(1);
			foreach ($playerfppgDetail as $player){
				
				$data = array('old_fppg'=>$player['old_fppg'],'plr_value'=>$player['plr_value']);
				$where = array();
				$where[] = $this->getAdapter()->quoteInto('plr_id = ?', $player['plr_id']);
				$where[] = $this->getAdapter()->quoteInto('sports_id = ?', $sportId);
				
				try {
					$update = $this->update($data,$where); 
					if($update){
						return true;
					}
				}catch (Exception $exc) {
					throw new Exception('Unable to update, exception occured'.$exc);
				}
			}
		}else{
			 throw new Exception('Argument not passed');
		}
    }
	
	 /**
     * developer    :- prince kumar dwivedi
     * date         :- 25/08/2016
     * description  :-  update player injury status   
     */
	public function getInjuryCode($injury_code){
		if(!empty($injury_code)){
			switch ($injury_code) {
				case 'out':
					$code = "O"; 
					break;
					
				case 'Questionable':
					$code = "Q"; 
					break;
				
				case 'Injury Reserve':
					$code = "IR"; 
					break;
				
				case 'Doubtful':
					$code = "D"; 
					break;
					
				case 'Sidelined':
					$code = "SL"; 
					break;
					
				case 'Suspended':
					$code = "S"; 
					break;
				
				case 'Probable':
					$code = "P"; 
					break;
				
				case 'Day To Day':
					$code = "DTD"; 
					break;
				
				case '7 Day DL':
					$code = "7Day"; 
					break;
				
				case '15 Day DL':
					$code = "15Day"; 
					break;
				
				case '60 Day DL':
					$code = "60Day"; 
					break;
				
				case 'Off DL':
					$code = "OffDL"; 
					break;
				
				default :
					$code = "IR"; 
					break;
			}
			if($code) {
				return $code;
			}
		}
	}
	 
	 
    public function updatePlayerInjuryStatus(){          
            if(func_num_args()>0):
                $player_id = func_get_arg(0);
                $description = func_get_arg(1);
				if(!empty(func_get_arg(2))){
					$injury_code_title = func_get_arg(2);
					$description = "[".ucfirst($injury_code_title)."] ".ucfirst($description);
					$injury_code = $this->getInjuryCode($injury_code_title);					
				} else {
					$injury_code = '';
					$description = ucfirst($description);
				}
				
                $now = date('Y-m-d H:i:s');
                $data = array('injury_status'=>'1','injury_reason'=>$description ,'injury_code'=>$injury_code, 'last_update'=>$now);
				 
				$where = "`plr_id` = '$player_id'";
				//echo "<pre>". $player_id; 
				//echo "<pre>"; print_r($data); 
					try { 
						$update = $this->update($data,$where);                   
					}catch (Exception $exc) {
						throw new Exception('Unable to update, exception occured'.$exc);
					}              
            else:
                throw new Exception('Argument not passed');
            endif;
    }  

 /**
    * Developer     : Alok kumar saxena   
    * Date          : 21/07/2017
    * Description   : get player details by sport id with search 
    * @params       : <string> search key <int> sport id
    */
public function getPlayersByGameTeamWithSearch(){
        
        if(func_num_args()>0):
            $sports_id = func_get_arg(0);
            $filterVal = func_get_arg(1);
            
            $sql = $this->select()                  
                        ->where('sports_id = ?',$sports_id)
                        ->where('status =?',1)
                        ->where('plr_name LIKE ?', "%$filterVal%");

            $sql = stripcslashes($sql);
          
            try {
                $result = $this->getAdapter()->fetchAll($sql);

                 if($result):
                     return $result;
                 endif;
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
       
        else:
            throw new Exception("Argument not passed");
        endif;
             
    }
    
    /**
    * Developer     : Vivek Chaudhari   
    * Date          : 13/06/2014
    * Description   : get player details by sport id
    * @params       : <string> team code <int> sport id
    */
    public function getPlayersByGameTeam(){
        
        if(func_num_args()>0):
            $sports_id = func_get_arg(0);
            $teamCode = func_get_arg(1);
            
            $sql = $this->select()
//                        ->from($this,array('sports_id','plr_details','plr_team_code','plr_value','fppg'))
                        ->where('sports_id = ?',$sports_id)
                        ->where('status =?',1)
                        ->where('plr_team_code IN (?)',$teamCode);

            $sql = stripcslashes($sql);
//            echo $sql;die;
            try {
                $result = $this->getAdapter()->fetchAll($sql);

                 if($result):
                     return $result;
                 endif;
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
       
        else:
            throw new Exception("Argument not passed");
        endif;
             
    }
    /**
    * Developer     : Vivek Chaudhari   
    * Date          : 16/06/2014
    * Description   : get player details by player id
    * @params       :  <int> player id
    */
    public function getPlayerByPlayerId(){
        if(func_num_args()>0):
            $plr_id = func_get_arg(0);
            
            $select = $this->select()
                       ->from($this)
                       ->where('plr_id = ?',$plr_id);
            $select = stripslashes($select);
                try {
                    $result = $this->getAdapter()->fetchRow($select);

                    if($result):
                        return $result;
                    endif;
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
        else:
            throw new Exception("Argument not passed");
        endif;  
    }
    

    /**
     * Developer : Bhojraj Rawte
     * Date : 26/06/2014
     * Description : Get Player Details
     */    
    public function getPlayerList() {

        if (func_num_args() > 0) {

            $playerIDs = func_get_arg(0); 
            $select = $this->select()
                    ->from($this, array('plr_details','plr_value','plr_id','sports_id','plr_team_code','plr_position','fppg'))
                    ->where('plr_id IN (?)', $playerIDs);
           
            $result = $this->getAdapter()->fetchAll($select);
            //ksort($result);
          //echo"<pre>"; print_r($result);echo "</pre>";die;
            if ($result) {   
                return $result;
            }
        }
    }
     /**
     * Developer    : Ramanjineyulu G
     * Date         : 11/07/2014
     * Description  : Get all Player Details
     */    
    public function getAllPlayerDetails() {    
        if(func_num_args()>0):
            $team = func_get_arg(0); 
            $select = $this->select()
                    ->from($this)
                    ->where('plr_team_code=?',$team);
            $result = $this->getAdapter()->fetchAll($select);
            if($result):
                return $result;
            endif;
        endif;
            
        
    }
   
    /**
     * Developer    : Vivek Chaudhari
     * Date         : 14/07/2014
     * Description  : Get Player Details by player id
     * @params      : param1<int> = player ID
     */ 
    public function getdetailsByPlayerId(){
        if(func_num_args()>0){
            $pid = func_get_arg(0);
            $select = $this->select()
                    ->from($this,'plr_details')
                    ->where('plr_id=?',$pid);
            try {
                $result = $this->getAdapter()->fetchRow($select);
                $decode = json_decode($result['plr_details']);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
            if($decode){
                return $decode;
            }
        }else{
             throw new Exception("Argument not passed");
        }
            
    }
    
    /**
     * Developer    : Vivek Chaudhari
     * Date         : 14/07/2014
     * Description  : update json encoded data i.e. disability of player
     * @params      : param1<int> = player ID, param2<array> = data array with edited values
     */
    public function updateDisability(){
        if(func_num_args()>0){
            $gm_id = func_get_arg(0);            
            $data = func_get_arg(1);            
//            $editJson = json_encode($edit);
              //  $data = array('plr_details'=>$edit);
                try{
                   $check= $this->update($data, 'gmp_id='.$gm_id);
                   if($check){
                       return $check;
                    }
                 }catch(Exception $e){
                    throw new Exception($e);
                 }
         }else{
              throw new Exception("Argument not passed");
            }
    }
    
    /**
     * Developer    : Vivek Chaudhari
     * Date         : 14/07/2014
     * Description  : Get available teams for provided sport
     * @params      : param1<int> = sport ID
     */
    public function getTeamsBySport(){
        if(func_num_args()>0):
            $sportId = func_get_arg(0);
            $select = $this->select()
                            ->from($this,array('plr_details'))
                            ->where('sports_id=?',$sportId);
                try {
                    $result = $this->getAdapter()->fetchAll($select);
                    if($result):
                        return $result;
                    endif;
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }

            else:
                throw new Exception("Argumnet not passed");
        endif;
    }
    
    /**
     * developer    :- vivek chaudhari
     * date         :- 25/07/2014
     * description  :- bulk update player salary
     * @params      :- param1<array> = salary array with player details,  
     */
    public function bulkUpdateSalaryOfEachPlayer(){
            if(func_num_args()>0):
                $playerDetail = func_get_arg(0);
                foreach ($playerDetail as $detail):
                    $data = array('plr_value'=>$detail['salary']);
                    $where = "plr_id=".$detail['id'];
                        try {
                            $update = $this->update($data,$where);
                        }catch (Exception $exc) {
                            throw new Exception('Unable to update, exception occured'.$exc);
                        }
                endforeach;
             else:
                 throw new Exception('Argument not passed');
            endif;
     }
     /**
     * developer    :- vivek chaudhari
     * date         :- 30/07/2014
     * description  :- get players by salary in global player swap section
     * @params      :- param1<string> = position code, param2<int> = player value(salary)  
     * @return      :- <array> player details
     */
     public function getDetailsBySalary(){
         if(func_num_args()>0){
             $posCode = func_get_arg(0);
             $salary = func_get_arg(1);
             $teamCode  = func_get_arg(2);
             try {
                 $select = $this->select()
                        ->from($this,array('plr_details','plr_id','plr_value','fppg'))
                        ->where('status=?',1)
                        ->where('plr_position=?',$posCode)
                        ->where('plr_value<=?',$salary)
                        ->where('plr_team_code IN (?)',$teamCode); 
                 $select = stripcslashes($select);
                    
                 $result = $this->getAdapter()->fetchAll($select);
                    if($result){
                        return $result;
                    }
             } catch (Exception $exc) {
                 echo $exc->getTraceAsString();
             }
                      
         }else{
             throw new Exception("Argument not passed");
         }
             
     }
     
     public function insertscraperdata($playerArray,$sports_id){
        
        if(func_num_args()>0):
            $playerArray = func_get_arg(0);
            $sports_id = func_get_arg(1);
            
            foreach($playerArray as $playerDetails):

//                $plr_id        = $playerDetails['id'];
                $plr_team_coad = strtolower($playerDetails['team_code']);
                $plr_details   = stripcslashes( json_encode($playerDetails));
                $last_update   = date('Y-m-d H:i:s');

                $playervalues = array('sports_id' =>$sports_id,
                                      'plr_details'=>$plr_details,
                                      'plr_team_code' =>$plr_team_coad,
                                      'last_update'=>$last_update);

                 $sql = $this->select()
                             ->from($this,array('gmp_id'))
                             ->where('sports_id = ?',$sports_id)
                             ->where('plr_details = ?',$plr_details);

                 $id = $this->getAdapter()->fetchRow($sql);
                 if($id):
                     $this->update($playervalues, "gmp_id =".$id['gmp_id']);
                  else:
                     $this->insert($playervalues);
                 endif;

             endforeach;
            
        else:
            throw new Exception('Argument not passed');
        endif;

    }
     public function getTeamIdByTeam() {

        if (func_num_args() > 0) {
            $team_name = func_get_arg(0);
            $select = $this->select()
                        ->from(array('p'=>'player_stats'),array('p.team_id','p.sports_id'))
                        ->setIntegrityCheck(false)
                        ->where('p.team_name = ?',$team_name);
        
        $result = $this->getAdapter()->fetchRow($select);
        if($result){
            return $result;
        }
        }
    }
    
    
    /**
     * developer    :- Bhojraj Rawte
     * date         :- 01/10/2014
     * description  :- bulk update player salary
     * @params      :- param1<array> = salary array with player details,  
     */
    public function bulkUpdateSalary(){
            if(func_num_args()>0):
                $id = func_get_arg(0);
                $salary = func_get_arg(1);
                
                    $data = array('plr_value'=>$salary);
                    $where = "plr_id=".$id;
                        try {
                            $update = $this->update($data,$where);
                        }catch (Exception $exc) {
                            throw new Exception('Unable to update, exception occured'.$exc);
                        }                
             else:
                 throw new Exception('Argument not passed');
            endif;
     }    
     
    /**
     * developer    :- vivek chaudhari
     * date         :- 25/09/2014
     * description  :- bulk update player last stats
     * @params      :- param1<array> = player last game status 
     */
    public function bulkUpdatePlayerStats(){
            if(func_num_args()>0){
                $playerStats = func_get_arg(0);
                $sportId = func_get_arg(1);
                foreach ($playerStats as $statKey=>$stat){
                    $lastStats = json_encode($stat);
                    $data = array('last_stats'=>$lastStats);
//                    $where = "plr_id=".$statKey;
                    $where = array();
                    $where[] = $this->getAdapter()->quoteInto('plr_id = ?', $statKey);
                    $where[] = $this->getAdapter()->quoteInto('sports_id = ?', $sportId);
                        try {
                            $update = $this->update($data,$where);
                        }catch (Exception $exc) {
                            throw new Exception('Unable to update, exception occured'.$exc);
                        }
                }
            }else{
                throw new Exception('Argument not passed');
            }
     }
     
    /**
     * developer    :- Bhojraj Rawte
     * date         :- 01/10/2014
     * description  :- bulk update player salary
     * @params      :- param1<array> = salary array with player details,  
     */
    public function updateSalaryByName(){
            if(func_num_args()>0):
                $name = func_get_arg(0);
                $salary = func_get_arg(1);
                
                    $data = array('plr_value'=>$salary);
                    $where = "plr_name='".$name."'";                  
//                   echo "<pre>"; print_r($data); echo "</pre>";
//                   echo "<pre>"; print_r($where); echo "</pre>";
//                   die;
                        try {
                            $update = $this->update($data,$where);                   
                        }catch (Exception $exc) {
                            throw new Exception('Unable to update, exception occured'.$exc);
                        }                
             else:
                 throw new Exception('Argument not passed');
            endif;
     }
     

     /**
     * developer    :- vivek chaudhari
     * date         :- 10/10/2014
     * description  :- bulk update fppg points of players
     * @params      :- param1<array> = player stats
     */
     public function bulkUpdatefppgPlayer(){
            if(func_num_args()>0){
                $playerfppgDetail = func_get_arg(0);
                $sportId = func_get_arg(1);
                foreach ($playerfppgDetail as $fppKey=>$fppVal){
                    if(isset($fppVal['fppg']) && $fppVal['fppg']!="" && $fppVal['fppg']>0){
                        $data = array('fppg'=>$fppVal['fppg']);
//                        $where = "plr_id=".$fppKey;
                        $where = array();
                        $where[] = $this->getAdapter()->quoteInto('plr_id = ?', $fppKey);
                        $where[] = $this->getAdapter()->quoteInto('sports_id = ?', $sportId);
                            try {
                                $update = $this->update($data,$where); 
                            }catch (Exception $exc) {
                                throw new Exception('Unable to update, exception occured'.$exc);
                            }
                    }
                }
            }else{
                 throw new Exception('Argument not passed');
             }
     }
     
     
    /**
     * Developer    : Bhojraj Rawte
     * Date         : 15/10/2014
     * Description  : Get Player Details by player id
     * @params      : param1<int> = player ID
     */ 
    public function getPlayerDetailsByPlayerId(){
        if(func_num_args()>0):
            $pid = func_get_arg(0);
            $select = $this->select()
                    ->from($this,'plr_details')
                    ->where('plr_id=?',$pid);
            $result = $this->getAdapter()->fetchRow($select);
            $result = json_decode($result['plr_details']);
            if($result):
                return $result;
            endif;
         else:
             throw new Exception("Argument not passed");
        endif;
            
    }     
    
    /**
     * developer    :- vivek chaudhari
     * date         :- 25/09/2014
     * description  :- bulk update player last stats
     * @params      :- param1<array> = player last game status 
     */
    public function bulkUpdatePlayerFpts(){
            if(func_num_args()>0){ 
                $points = func_get_arg(0);
                $sportId = func_get_arg(1);
                foreach ($points as $fKey=>$fVal){
                    $fpts = $fVal['fpts'];
                    $data = array('fpts'=>$fpts);
                    $where = array();
                    $where[] = $this->getAdapter()->quoteInto('plr_id = ?', $fKey);
                    $where[] = $this->getAdapter()->quoteInto('sports_id = ?', $sportId);
                        try {
                            $update = $this->update($data,$where);
                        }catch (Exception $exc) {
                            throw new Exception('Unable to update, exception occured'.$exc);
                        }
                }
            }else{
                throw new Exception('Argument not passed');
            }
     }
	 
	 
     
     
     /**
     * Developer    : vinay
     * Date         : 4/11/2014
     * Description  : Get Player Details by fpts
     * @params      : 
     */ 
    public function getPlayerDetailsByFPTS(){
        if(func_num_args()>0){
                $sports_id = func_get_arg(0);
                $select = $this->select()
                       ->from(array('gp' => 'game_players'),array('gp.plr_name','gp.plr_team_code','gp.plr_position','gp.fppg','gp.fpts','gp.sports_id'))
                       ->setIntegrityCheck(false)
                       ->joinLeft(array('gpd' => 'game_players_details'), 'gp.plr_name = gpd.player_name',array('gpd.player_img_link'))
                       ->where('gpd.player_img_link != ?',"")
                        ->where('gp.sports_id = ?',$sports_id)
                        ->order('gp.fpts DESC')
                        ->limit(5);
                $result = $this->getAdapter()->fetchAll($select);
                                
                if ($result):
                        return $result;
                endif;
        }
        }

        
    public function getPlayerByGameId(){
        if(func_num_args()>0):
            $gm_id = func_get_arg(0);
            
            $select = $this->select()
                       ->from($this)
                       ->where('gmp_id = ?',$gm_id);
            $select = stripslashes($select);
                try {
                    $result = $this->getAdapter()->fetchRow($select);

                    if($result):
                        return $result;
                    endif;
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
        else:
            throw new Exception("Argument not passed");
        endif;  
    }        
        
      public function getByIdAndSport(){
        if(func_num_args()>0){
            $plr_id = func_get_arg(0);
			if(empty($plr_id))
				$plr_id = 0;
            $sportId = func_get_arg(1);
            
            $select = $this->select()
                       ->from($this)
                       ->where('sports_id =?',$sportId)
                       ->where('plr_id = ?',$plr_id);
            $select = stripslashes($select);
                try {
                    $result = $this->getAdapter()->fetchRow($select);

                    if($result){
                        return $result;
                    }
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
      }else{
            throw new Exception("Argument not passed");
      }
    }
    
      /**
     * Developer : vivek chaudhari
     * Date : 12/04/2014
     * Description : Get Player Details by sport
     */    
    public function getPlayerListBySport() {

        if (func_num_args() > 0) {

            $playerIDs = func_get_arg(0); 
			
			if(empty($playerIDs))
				$playerIDs = 0;
			
            $sportId = func_get_arg(1);
			
            $select = $this->select()
                    ->from($this, array('plr_details','plr_value','plr_id','sports_id','plr_team_code','plr_position','fppg'))
                    ->where('plr_id IN (?)', $playerIDs)
                    ->where('sports_id=?',$sportId);
            try {
                $result = $this->getAdapter()->fetchAll($select);
                if ($result) {   
                    return $result;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }

            
        }
    }
}
?>
