<?php
class Application_Model_GamePlayersDetails extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'game_players_details';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_GamePlayersDetails();
		return self::$_instance;
    }
    
    // Insert sraper details by sports id
     public function insertscraperdata($playerArray,$sports_id){
        
        if(func_num_args()>0):
            $playerArray = func_get_arg(0);
		
            $sports_id = func_get_arg(1); 
          
            foreach($playerArray as $playerDetails):

                $plr_team_coad = strtolower($playerDetails['team_code']);
                //$plr_details   = stripcslashes( json_encode($playerDetails));
                $last_update   = date('Y-m-d H:i:s');

                $playervalues = array('sports_id' =>$sports_id,
                                      'plr_team_code' =>$plr_team_coad,
                                      'last_update'=>$last_update,
                                        'team_name'=>$playerDetails['teamName'],
                                        'player_name'=>$playerDetails['name'],
                                        'player_img_link'=>$playerDetails['playerImage']);
                
                $sql = $this->select()
                             ->from($this,array('gmp_id'))
                             ->where('sports_id = ?',$sports_id)
                             ->where('player_img_link = ?',$playerDetails['playerImage']);

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
    /*
     * Developer : prince kumar dwivedi
     * Description : get team code 
     */
	public function getAllTeamCode(){
            $result = $this->getAdapter()->fetchAll('SELECT DISTINCT `plr_team_code` FROM `game_players_details` ORDER BY `team_name`');
            if($result){
                return $result;
            }
	}
	 
	 
    /*
     * Developer : Manoj Kumar
     * Description : get player image by sprots_id and name
     */
    public function getPlayerImage(){
        
        if(func_num_args()>0){
            $sportsId = func_get_arg(0);
            $playerName = func_get_arg(1);
            
            $sql = $this->select()
                             ->from($this,array('player_img_link'))
                             ->where('sports_id = ?',$sportsId)
                             ->where('player_name = ?',$playerName);

            $result = $this->getAdapter()->fetchRow($sql);
            
            if($result){
                return $result;
            }
        }
    }
    
    /*
     * Developer : Manoj Kumar
     * Description : get player image by sprots_id and name
     */
    public function getTeamCodeByTeamName(){
        
        if(func_num_args()>0){
            $sportsId = func_get_arg(0);
            $teamName = func_get_arg(1);
            
            $sql = $this->select()
                             ->from($this,array('plr_team_code'))
                             ->where('sports_id = ?',$sportsId)
                             ->where('team_name = ?',$teamName);

            $result = $this->getAdapter()->fetchRow($sql);
            
            if($result){
                return $result;
            }
        }
    }
}
?>
