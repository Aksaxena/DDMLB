<?php

class Application_Model_Lineup extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'lineup';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_Lineup();
        return self::$_instance;
    }

    public function insertLineup() {
        if(func_num_args()>0):
            $userlineup = func_get_arg(0);
            try {
                $id = $this->insert($userlineup);
                if ($id) :
                    return $id;
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
     * Date : 03/06/2014
     * Description : Get User Lineup details
     */
    public function getUserLineupDetails() {

        if (func_num_args() > 0) {
            $userID = func_get_arg(0);
            $currentDate = func_get_arg(1);
            $subDate = func_get_arg(2);
            
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('l' => 'lineup'))
                    ->join(array('ul' => 'user_lineup'),'ul.lineup_id = l.lineup_id',array('ul.contest_id','ul.con_rank','ul.con_prize'))
                    ->join(array('con' => 'contests'), 'con.contest_id = ul.contest_id', array('con.match_id', 'con.contest_name', 'con.sports_id', 'con.entry_fee', 'con.start_time', 'con.con_status', 'con.prize_pool', 'con.play_limit', 'con.fpp', 'con.total_entry'))
                    ->where('l.created_by =?', $userID)
                    ->order('l.lineup_id DESC');
//                    ->where('con.start_time <=?', $currentDate)
                    //->where('con.start_time >=?', $subDate);
            
//echo $select; die;

            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 04/06/2014
     * Description : Get User Filter Lineup details
     */
    public function getUserFilterLineupDetails() {
        if (func_num_args() > 0) {

            $userID = func_get_arg(0);
            $entryFee = func_get_arg(1);
            $day = func_get_arg(2);
            $currentDate = func_get_arg(3);
            $subDate = func_get_arg(4);


            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('l' => 'lineup'), array('l.rank','l.prize'))
                    ->join(array('ul' => 'user_lineup'),'ul.lineup_id = l.lineup_id',array('ul.contest_id','ul.con_rank','ul.con_prize'))
                    ->join(array('con' => 'contests'), 'con.contest_id = ul.contest_id', array('con.match_id', 'con.contest_name', 'con.sports_id', 'con.entry_fee', 'con.start_time', 'con.con_status', 'con.prize_pool', 'con.play_limit', 'con.fpp', 'con.total_entry'))
                    ->where('con.con_status =?', 1)
                    ->where('l.created_by =?', $userID);

            if ($entryFee != '-1' && $day != '-1') {

                if ($entryFee == -2) {
                    $entryFee = 1;
                    $select->where('con.entry_fee >=?', $entryFee)
                            ->where('con.start_time <=?', $currentDate)
                            ->where('con.start_time >=?', $subDate);
                } else {
                    $select->where('con.entry_fee =?', $entryFee)
                            ->where('con.start_time <=?', $currentDate)
                            ->where('con.start_time >=?', $subDate);
                }
            } else if ($entryFee != -1 && $day == '-1') {
                if ($entryFee == -2) {
                    $entryFee = 1;
                    $select->where('con.entry_fee >=?', $entryFee);
                } else {
                    $select->where('con.entry_fee =?', $entryFee);
                }
            } else if ($entryFee == -1 && $day != '-1') {

                $select->where('con.start_time <=?', $currentDate)
                        ->where('con.start_time >=?', $subDate);
            }

//            echo $select;die;
            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        }
    }

    /**
     * Developer : MAnoj kosare
     * Date : 11/06/2014
     * Description : Get Lineup details for a day
     */
    public function getLineupForDay() {
        if (func_num_args() > 0) {
            
            $currentDate = func_get_arg(0);
            $subDate     = func_get_arg(1);
            $sportsId    = func_get_arg(2);
            
            $select = $this->select()
//                    ->from($this, array('player_ids','created_by','lineup_id'))                    
                    ->setIntegrityCheck(false)
                    ->from(array('l' => 'lineup'), array('l.player_ids','l.created_by','l.lineup_id'))
                    ->join(array('ul' => 'user_lineup'),'ul.lineup_id = l.lineup_id',array('ul.contest_id'))
                    ->join(array('c' => 'contests'),'c.contest_id = ul.contest_id')
                    ->where('c.status =?', 1)
                    ->where('l.start_time <=?', $subDate)
                    ->where('l.start_time >=?', $currentDate)
//                    ->orwhere('l.end_time >= l.start_time') // Manoj (29th Oct 14)
                    ->where('l.sports_id =?', $sportsId);
//            echo $select; //die;
            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        }
    }
    
    public function updateLineup($data,$lineup_id){
        $where = "lineup_id = ".$lineup_id;
        
        $this->update($data, $where);
    }
    /**
     * Developer    : Vivek Chaudhari
     * Date         : 16/06/2014
     * Description  : get lineup table data for contest id in game center action of user module->usercontest controller
     * @params      : contestID<int>, user Id <int>
     * @return      : <array> user lineup details for given contest
     */
    public function getLineup(){
        if(func_num_args()>0):
            $contestId = func_get_arg(0);
            $uid = func_get_arg(1); 
            $select = $this->select()
                            ->from(array('l'=>'lineup')) 
                            ->setIntegrityCheck(false)
                            ->join(array('u'=>'users'),'l.created_by=u.user_id',array('u.user_name'))
                            ->join(array('ul' => 'user_lineup'),'ul.lineup_id = l.lineup_id')
                            ->join(array('c'=>'contests'),'ul.contest_id=c.contest_id',array('c.contest_name','c.con_status','c.start_time','c.prize_pool'))
                            ->where('ul.contest_id=?',$contestId)
                            ->where('u.user_id=?',$uid);
//        echo $select; die;
                    try {
                        $result = $this->getAdapter()->fetchRow($select);
                         if($result):
                             return $result;
                         endif;
                        } catch (Exception $exc) {
                            throw new Exception('Some error occurred, unable to get data');
                        }

        else:
            throw new Exception("Argument not passed");
        endif;
        
    }
    /**
     * Developer    : Vivek Chaudhari
     * Description  : get details by player id
     * Date         : 16/06/2014
     * @param <int> $ player id
     * @return <string> : player details
     */
    public function getNameByPlayerId(){
        if(func_num_args()>0){
            $playerId = func_get_arg(0);
            $sportId = func_get_arg(1);
            $select = $this->select()
                            ->from(array('g'=>'game_players'),array('g.plr_details','g.plr_position','g.plr_value'))
                            ->setIntegrityCheck(false)
                            ->where('g.plr_id=?',$playerId)
                            ->where('g.sports_id=?',$sportId);
                    try {
                        $detail = $this->getAdapter()->fetchRow($select);
                        if($detail){
                            return $detail;
                        }
                    } catch (Exception $exc) {
                        echo $exc->getTraceAsString();
                    }
        
        }else{
            throw new Exception("Argument not passed");
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 25/06/2014
     * Description : Get My Lineup Details
     */
    public function getMyLineupDetailsByUserId(){
        if (func_num_args() > 0) {
            
            $userID = func_get_arg(0);            
            $name = $this->select()
                ->from(array('li'=>'lineup'))
                ->setIntegrityCheck(false)
                ->join(array('ul' => 'user_lineup'),'ul.lineup_id = li.lineup_id',array('ul.contest_id'))
                ->joinLeft(array('con'=>'contests'),'con.contest_id = ul.contest_id',array('con.total_entry','con.con_status'))
                ->joinLeft(array('sp'=>'sports'),'con.sports_id = sp.sports_id',array('sp.display_name'))
                ->where('li.created_by = ?',$userID)
                ->order('li.lineup_id DESC')
                ->limit(5);
       // echo $playerId; die('1');
        $result = $this->getAdapter()->fetchAll($name);
        return $result;
//       echo "<pre>"; print_r($result); echo "</pre>"; die;
        }     
    }
    /**
     * Developer    : Vivek Chaudhari
     * Description  : get player ids for lineup
     * Date         : 03/07/2014
     * @param <int> $lineup Id
     * @return <array>
     */
    public function  getPlayerIdsByLineupId(){
        if(func_num_args()>0):
            $lineup_id = func_get_arg(0);
           
            try {
                $select=$this->select()
                            ->from($this)
                            ->where('lineup_id = ?' , $lineup_id);
               
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
     * Developer : MAnoj kosare
     * Date : 04/07/2014
     * Description : Get Point details for a day
     */
    public function getPointsForDay() {
        if (func_num_args() > 0) {
            
            $currentDate = func_get_arg(0);
            $subDate     = func_get_arg(1);
            $sportsId    = func_get_arg(2);
            
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('l'=>'lineup'), array('l.players_points','l.lineup_id'))
                    ->joinLeft(array('ul'=>'user_lineup'),'l.lineup_id=ul.lineup_id',array('ul.contest_id'))
                    ->where('ul.status =?', 1)
                    ->where('l.start_time <=?', $subDate)
                    ->where('l.start_time >=?', $currentDate)
                    ->where('l.sports_id =?', $sportsId)
                    ->order('l.players_points DESC');
            
            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        }
    }
    
    /**
    * Developer : Bhojraj Rawte
    * Date : 04/07/2014
    * Description : Get My Lineup Details
    */
    public function getMyLineupDetails(){
        if (func_num_args() > 0) {            
            $userID = func_get_arg(0);
            $name = $this->select()
                ->from(array('li'=>'lineup'))
                ->setIntegrityCheck(false)                
                ->joinLeft(array('ul' => 'user_lineup'),'ul.lineup_id = li.lineup_id',array('ul.contest_id','ul.status')) 
                ->joinLeft(array('con' => 'contests'),'ul.contest_id = con.contest_id',array('con.con_status')) 
                ->joinLeft(array('sp'=>'sports'),'li.sports_id = sp.sports_id',array('sp.display_name'))
                   
                ->where('li.created_by=?',$userID)
                ->order('li.lineup_id DESC')
                ->limit(5);
//            echo $name; die;
       // echo $playerId; die('1');
        $result = $this->getAdapter()->fetchAll($name);
        return $result;
//       echo "<pre>"; print_r($result); echo "</pre>"; die;
        }     
    }    
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 11/07/2014
     * Description : Get lineup details by lineup id
     */    
    public function getLineupByID() {
        if(func_num_args() > 0){
            $lineupID = func_get_arg(0);
            try{
               
                $select = $this->select()
                               ->from(array('li'=>'lineup'))
                               ->setIntegrityCheck(false)                
                               ->joinLeft(array('ul' => 'user_lineup'),'ul.lineup_id = li.lineup_id',array('ul.contest_id','ul.created_date','ul.con_rank','ul.con_prize','ul.con_prize_type')) 
                               ->joinLeft(array('sp' => 'sports'),'sp.sports_id = li.sports_id',array('sp.display_name','sp.sports_id')) 
                               ->join(array('u'=>'users'),'u.user_id=li.created_by',array('u.user_name'))
                               ->join(array('ps'=>'player_stats'),'ps.sports_id =li.sports_id',array('team_id'))
                               ->where('li.lineup_id = ?',$lineupID);
               
                $result = $this->getAdapter()->fetchRow($select);
                
            }catch(Exception $e){
                throw new Exception('Unable To Insert Exception Occured :'.$e);
            }
            
            if($result){
                return $result;
            }
        }else{
            throw new Exception('Argument Not Passed');
        }
            
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 11/07/2014
     * Description : Update lineup details
     */      
     public function updateLineupDetails() {
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            $userLineup = func_get_arg(1);
            $where = "lineup_id = " . $userLineup;
            $updateres = $this->update($data, $where);
            //echo $updateres;die('test');
        }
        if ($updateres) {
            return $updateres;
        }
    }

    /**
    * Developer : Bhojraj Rawte
    * Date : 04/07/2014
    * Description : Get My Lineup Details
    */
    public function getContestJoinLineupDetails(){
        if (func_num_args() > 0) {            
            $userID = func_get_arg(0);
            $name = $this->select()
                ->from(array('li'=>'lineup'))
                ->setIntegrityCheck(false)                
                ->joinLeft(array('ul' => 'user_lineup'),'ul.lineup_id = li.lineup_id',array('ul.contest_id')) 
                ->joinLeft(array('con' => 'contests'),'ul.contest_id = con.contest_id',array('con.total_entry','con.con_status')) 
                ->joinLeft(array('sp'=>'sports'),'li.sports_id = sp.sports_id',array('sp.display_name'))
                   
                ->where('li.created_by=?',$userID)
                ->where('ul.status=?',1)
                ->order('li.lineup_id DESC')
                ->limit(5);
       // echo $playerId; die('1');
        $result = $this->getAdapter()->fetchAll($name);
        return $result;
//       echo "<pre>"; print_r($result); echo "</pre>"; die;
        }     
    }     
     
    /**
    * Developer     : Vivek Chaudhari   
    * Date          : 31/07/2014
    * Description   : Get My Lineup Details for swapping 
    * @params       : param1<int> = current user Id
    */
    public function getLineupForSwap(){
       if(func_num_args()>0):
           $uId = func_get_arg(0);
           $curDate = Date('Y-m-d H:m:s');
           $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('l'=>'lineup'))
                        ->join(array('u'=>'user_lineup'),'l.lineup_id=u.lineup_id',array('u.contest_id'))
                        ->where('l.start_time>=?',$curDate)
                        ->where('u.contest_id != 0')
                        ->where('l.created_by=?',$uId)
                        ->order('l.start_time ASC'); 
           $result = $this->getAdapter()->fetchAll($select);
           if($result):
               return $result;
           endif;
               
        else:
               throw new Exception("Argument not passed");
        endif;
    }
    
    /**
     * Developer    : Vivek Chaudhari
     * Description  : update contest entry
     * Date         : 08/08/2014
     * @param <int> $uid
     * @return <array> contest details of user
     */
   public function getJoinedContest(){
       if(func_num_args()>0):
           $uid = func_get_arg(0);
           $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('l'=>'lineup'),'')
                        ->join(array('u'=>'user_lineup'),'l.lineup_id=u.lineup_id',array('u.contest_id'))
                        ->where('l.created_by=?',$uid);
           
           $result = $this->getAdapter()->fetchAll($select);
           if($result):
               return $result;
           endif;
       else:
           throw new Exception ("Argument not passed");
       endif;
   }
   

    
   /**
     * Developer    : Vivek Chaudhari
     * Description  : get current user lineup
     * Date         : 21/08/2014
     * @param <int>  user id
     * @return <array> get user lineups by current time
     */
   public function getUserLineups(){
       if(func_num_args()>0):
           $uid = func_get_arg(0);
//           $curTime = Date('Y-m-d H:i:s');
            $diffTime = date('Y-m-d H:i:s' ,strtotime(date('Y-m-d H:i:s')."-4 days")); 
           $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('l'=>'lineup'))
                        ->joinLeft(array('ul' => 'user_lineup'),'ul.lineup_id = l.lineup_id',array('ul.contest_id','ul.status','ul.created_date'))                         
                        //->where('l.start_time >=?',$curTime)
//                        ->where('ul.status >=?',$curTime)
//                        ->where("userEmail = '" . $email . "' OR userName = '" . $email . "'");  
                        ->where('l.start_time >=?',$diffTime)
                        ->where('l.created_by=?',$uid);
//           echo $select; die;
                try {
                    $select = stripcslashes($select); 
                     $result = $this->getAdapter()->fetchAll($select);
                        if($result){
                            return $result;
                        }
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
       else:
           throw new Exception ("Argument not passed");
       endif;
   }
//   
   public function getlineupdatailsbystartid(){
       if (func_num_args() == 3) {
            $time = func_get_arg(0);
            $userId = func_get_arg(1);
            $sportid = func_get_arg(2);
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('l' => 'lineup'))
                    ->where('l.start_time =?', $time)
                    ->where('l.created_by=?', $userId)
                    ->where('l.sports_id=?', $sportid);
//           echo $select; die;
            $result = $this->getAdapter()->fetchAll($select);
//               echo "<pre>"; print_r($result); echo "</pre>"; die;
            if ($result) {
                
                return $result;
            }
        } else {
            throw new Exception("Argument not passed");
        }
    }

   /**
        * Developer    : Chandra Sekhar Reddy
        * Date         : 09/09/2014
        * Description  : To get the contest_id based on user_id by joining two tables 
       */
   public function getContestId(){
       if(func_num_args()>0):
       $uid = func_get_arg(0);
       $select = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('li'=>'lineup'))
                ->join(array('ul'=>'user_lineup'),'ul.lineup_id = li.lineup_id',array('ul.contest_id'))
                ->where('li.created_by=?',$uid);
        $result = $this->getAdapter()->fetchAll($select);
        if($result):
            return $result;
        endif;
        else:
           throw new Exception ("Argument not passed");
       endif;
   } 
   
   /**
     * Developer    : Vivek Chaudhari
     * Description  : get user lineup details by lineup id
     * Date         : 11/09/2014
     * @param <int>  lineup id
     * @return <array> lineup details
     */
   public function getLineupDetailsbyLid() {
        if (func_num_args() > 0) {
            $lineupID = func_get_arg(0);
            try {
                $select = $this->select()
                        ->from(array('li' => 'lineup'))
                        ->setIntegrityCheck(false)
                        ->joinLeft(array('ul' => 'user_lineup'), 'ul.lineup_id = li.lineup_id')
                        ->where('li.lineup_id = ?', $lineupID);
                $select = stripcslashes($select);
                $result = $this->getAdapter()->fetchRow($select);
                if ($result) {
                    return $result;
                }
            } catch (Exception $e) {
                throw new Exception('Unable To Fetch Exception Occured :' . $e);
            }
        } else {
            throw new Exception('Argument Not Passed');
        }    
    }
    
    /**
     * Developer    : Vivek Chaudhari
     * Description  : get user joined contest lineup details by user id and contest id
     * Date         : 11/09/2014
     * @param <int> 1) user id (2) contest Id
     * @return <array> lineup details
     */
   public function getLineupByUidAndConId(){
       if (func_num_args() > 0) {
            $uID = func_get_arg(0);
            $conID = func_get_arg(1);
            try {
                $select = $this->select()
                        ->from(array('li' => 'lineup'))
                        ->setIntegrityCheck(false)
                        ->joinLeft(array('ul' => 'user_lineup'), 'ul.lineup_id = li.lineup_id')
                        ->where('li.created_by = ?', $uID)
                        ->where('ul.contest_id = ?', $conID)
                        ->where('ul.status = 1'); 
                $select = stripcslashes($select);
                $result = $this->getAdapter()->fetchRow($select); 
                if ($result) {
                    return $result;
                }else{
                    return 0;
                }
            } catch (Exception $e) {
                throw new Exception('Unable To Fetch Exception Occured :' . $e);
            }
        } else {
            throw new Exception('Argument Not Passed');
        }   
   } 
   
   
   public function getUserContestLineups(){
        if(func_num_args()>0):
            $uid= func_get_arg(0); 
            $nextDay = date('Y-m-d H:i:s');
            $select = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('l'=>'lineup'))
//                            ->join(array('c'=>'contests'),'ul.contest_id=c.contest_id')
                            ->where('l.start_time>=?',$nextDay)
                            ->where('l.created_by=?',$uid);
//            echo $select; die;
            $result= $this->getAdapter()->fetchAll($select);

                if($result):
                    return $result;
                endif;
            else:
                throw new Exception('Argument not Passed');
            endif;
    }
    
    
    public function getLineupByLid(){
       if(func_num_args()>0):
           $lId = func_get_arg(0);
           $select = $this->select()
                        ->where('lineup_id=?',$lId); 
           $result = $this->getAdapter()->fetchRow($select);
           if($result):
               return $result;
           endif;
               
        else:
               throw new Exception("Argument not passed");
        endif;
    }
    
        public function getLineupByUid(){
       if(func_num_args()>0):
           $uId = func_get_arg(0);
           $diffTime = func_get_arg(1);
           $select = $this->select()
                        ->where('start_time >=?',$diffTime)
                        ->where('created_by=?',$uId); 
           $result = $this->getAdapter()->fetchAll($select);
           if($result):
               return $result;
           endif;
               
        else:
               throw new Exception("Argument not passed");
        endif;
    }
}
?>
