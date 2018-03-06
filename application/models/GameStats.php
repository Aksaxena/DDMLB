<?php

class Application_Model_GameStats extends Zend_Db_Table_Abstract {
    
    private static $_instance = null;
    protected $_name = 'game_stats';
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Application_Model_GameStats();
		return self::$_instance;
    }
    
    /**
     * developer    :- vivek chaudhari
     * date         :- 20/06/2014
     * description  :- insert game details or if game found update
     * @params      :- param1<array> = game  details,  
     */
    public function insertGameStats(){
        
        if(func_num_args()>0):
            $gameStats = func_get_arg(0);
            
            $sports_id = $gameStats['sports_id'];
            $game_date = $gameStats['game_date'];
        
            $sql = $this->select()
                           ->from($this,array('gs_id'))
                           ->where('sports_id = ?',$sports_id)
                           ->where('game_date = ?',$game_date);

            $id = $this->getAdapter()->fetchRow($sql);

            if($id):
                $res = $this->update($gameStats, "gs_id =".$id['gs_id']);
            else:
                $this->insert($gameStats);
            endif;
        
        else:
            throw new Exception("Argument not passed");
        endif;
    }
    
    
    public function getGameStats(){
        if(func_num_args()>0):
            $sports_id = func_get_arg(0);
            $game_date = func_get_arg(1);
            if($sports_id == 1){
                $weekDate = date('Y-m-d',strtotime($game_date. " +1 week"));
                $sql = $this->select()
                        ->from($this,array('game_date','game_stat'))
                        ->where('sports_id = ?',$sports_id)
                        ->where('game_date >= ?',$game_date)
                        ->where('game_date <= ?',$weekDate); 
            }else{
                $weekDate = date('Y-m-d',strtotime($game_date. " +1 week"));    
                $sql = $this->select()
                        ->from($this,array('game_date','game_stat'))
                        ->where('sports_id = ?',$sports_id)
                        ->where('game_date >= ?',$game_date)
                        ->where('game_date <= ?',$weekDate);
            }
            
             $sql = stripslashes($sql); 
           // echo $sql;die;
                try {
                 $result = $this->getAdapter()->fetchRow($sql);
                  //$result = $this->getAdapter()->fetchAll($sql); // change fetchRow to fetchAll (Manoj)
                    if($result){
                        return $result;
                    }
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
        else:
            throw new Exception("Argument not passed");
        endif;  
    }
    
   public function getGameStats2(){
        if(func_num_args()>0):
            $sports_id = func_get_arg(0);
            $game_date = func_get_arg(1);
            if($sports_id == 1){
                $weekDate = date('Y-m-d',strtotime($game_date. " +1 week"));
                $sql = $this->select()
                        ->from($this,array('game_date','game_stat'))
                        ->where('sports_id = ?',$sports_id)
                        ->where('game_date >= ?',$game_date)
                        ->where('game_date <= ?',$weekDate); 
            }else{
                $weekDate = date('Y-m-d',strtotime($game_date. " +1 week"));    
                $sql = $this->select()
                        ->from($this,array('game_date','game_stat'))
                        ->where('sports_id = ?',$sports_id)
                        ->where('game_date >= ?',$game_date)
                        ->where('game_date <= ?',$weekDate);
            }
            
             $sql = stripslashes($sql); 
           // echo $sql;die;
                try {
                 // $result = $this->getAdapter()->fetchRow($sql);
                  $result = $this->getAdapter()->fetchAll($sql); // change fetchRow to fetchAll (Manoj)
                    if($result){
                        return $result;
                    }
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
        else:
            throw new Exception("Argument not passed");
        endif;  
    }
    /**
     * Desv : get future date contest by given date time
     * Dev : Manoj (25th oct)
     * @param $sports_id <Int>
     * @param $game_date <Date>
     * $param $days      <Int>
     * @return $result <Array>
     * @throws Exception
     */
    public function getFutureGameStats(){
        if(func_num_args()>0):
            $sports_id = func_get_arg(0);
            $game_date = func_get_arg(1);
            $days      = func_get_arg(2);
               
            //$weekDate = date('Y-m-d',strtotime($game_date. " +{$days} days"));
            
            $sql = $this->select()
                    ->from($this,array('game_date','game_stat'))
                    ->where('sports_id = ?',$sports_id)
                    ->where('game_date >= ?',$game_date)
                    //->where('game_date <= ?',$weekDate)
                    ->limit(2); 
            
            
             $sql = stripslashes($sql); 
//             echo $sql;
                try {
                    $result = $this->getAdapter()->fetchAll($sql); // change fetchRow to fetchAll (Manoj)

                    if($result){
                        return $result;
                    }
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
        else:
            throw new Exception("Argument not passed");
        endif;  
    }
    
    
    public function getGameStatsNew(){
        if(func_num_args()>0):
            $sports_id = func_get_arg(0);
            $game_date = func_get_arg(1);
            $end_date  = func_get_arg(2);
            
            $sql = $this->select()
                    ->from($this,array('game_date','game_stat'))
                    ->where('sports_id = ?',$sports_id)
                    ->where('game_date >= ?',$game_date)
                    ->where('game_date <= ?',$end_date); 
            
            
             $sql = stripslashes($sql); 
//             echo $sql;die;
                try {
                    $result = $this->getAdapter()->fetchAll($sql); // change fetchRow to fetchAll (Manoj)

                    if($result){
                        return $result;
                    }
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
        else:
            throw new Exception("Argument not passed");
        endif;  
    }
}
?>
