<?php
class Application_Model_PlayerStats extends Zend_Db_Table_Abstract{
    private static $_instance = null;
    protected $_name = 'player_stats';
    
    private function  __clone() { } //Prevent any copy of this object
    
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_PlayerStats();
		return self::$_instance;
    }
    /**
     * developer    :- vivek chaudhari
     * date         :- 23/06/2014
     * description  :- insert palyer stats or update if found in db
     * @params      :- param1<array> = sport id, param2<string> = game date, 
     */
    public function insertPlayersStats(){
        if(func_num_args()>0):
            $playerStats = func_get_arg(0);
            $sportsId  = func_get_arg(1);

            foreach($playerStats as $stats):
                $teamName = $stats['team'];
                $teamId = $stats['id'];
                $category = json_encode($stats['category']);

                $statArray = array('sports_id' => $sportsId,
                                   'team_name' => $teamName,
                                   'team_id'   => $teamId,
                                   'team_stats'=> $category,
                                   'last_update'=>date('Y-m-d H:i:s'));

                $sql = $this->select()
                           ->from($this,array('stats_id'))
                           ->where('sports_id = ?',$sportsId)
                           ->where('team_id = ?',$teamId);

               $id = $this->getAdapter()->fetchRow($sql);

                    if($id){
//                        $where[] = "sports_id = $sportsId";
//                        $where[] = "team_id = $teamId";
                        $this->update($statArray, "stats_id =".$id['stats_id']);
                    }else{
                        $this->insert($statArray);
                    }
          endforeach;
        else:
            throw new Exception("Argument not passed");
        endif;
        
    }
    /**
     * developer    :- vivek chaudhari
     * date         :- 23/06/2014
     * description  :- get playe details
     * @params      :- param1<int> = tesm id, param2<int> = sport id, 
     */
    public function getPlayerStats() {
        if(func_num_args()>0){
            $teamId = func_get_arg(0);
			if(empty($teamId))
				$teamId = 0;
            $sportsId = func_get_arg(1);
 
            $sql = $this->select()
                    ->where('team_id = ?', $teamId)
                    ->where('sports_id = ?', $sportsId);
          
            $sql = stripslashes($sql);
                try {
                     $result = $this->getAdapter()->fetchRow($sql);
                     if($result){
                          return $result;
                     }
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
        }else{
            throw  new Exception("Argument not passed");
        }
        
    }
    
    
//-----test functions to return player stats value for salary calculation algorithm-----------------
    public function playerStats(){
        if(func_num_args()>0):
            $sportId = func_get_arg(0);
            $select = $this->select()->where('sports_id='.$sportId);
            try {
                 $result = $this->getAdapter()->fetchAll($select);
                    if($result):
                        return $result;
                    endif;
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        else:
            throw new Exception("Argument Not Passed");
        endif;
    }
    
    public function getTeamsCost(){
        $select = $this->select()->setIntegrityCheck(false)
                ->from('team_payouts','salary')
                ->where('count>0');
        $result = $this->getAdapter()->fetchAll($select);
        if($result){
            return $result;
        }
    }
//================================================================================//  
 /*
  * Name: Sarika Nayak
  * date: 08/09/2014
  * description: to get team id from team name
  */   
    
      public function getTeamIdByTeamName(){
        
            $team_name = func_get_arg(0);
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this,array('team_id','sports_id'))
                    
                    ->where('team_name = ?',$team_name);
            $result = $this->getAdapter()->fetchAll($select);

           
            if($result){
                return $result;
            }
             else{
             throw new Exception("Argument not passed");
             }
        }
    
        
         /*
            * Name: Bhojraj Rawte
            * Date: 15/10/2014
            * Description: to get team id from team name
            */   
    
        public function getTeamDetailsTeamName(){
        
            $team_name = func_get_arg(0);
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this,array('team_id','sports_id'))
                    
                    ->where('team_name = ?',$team_name);
            $result = $this->getAdapter()->fetchRow($select);

           
            if($result){
                return $result;
            }
             else{
             throw new Exception("Argument not passed");
             }
        }

}
?>
