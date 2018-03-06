<?php

class Application_Model_UserLineup extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'user_lineup';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_UserLineup();
        return self::$_instance;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 02/07/2014
     * Description : Insert user lineup details
     */    
    public function inserUserLineup($userlineup) {
        $id = $this->insert($userlineup);
        if ($id) {
            return $id;
        }
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 03/07/2014
     * Description : Get user lineup details by lineup id
     */    
    public function getUserLineupByID() {
        if(func_num_args() > 0){
            $lineupID = func_get_arg(0);
            try{
               
                $select = $this->select()
                               ->from($this)
                               ->where('user_lineup_id = ?',$lineupID);
                
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
    
//    /**
//     * Developer : Bhojraj Rawte
//     * Date : 04/07/2014
//     * Description : Update userlineup details
//     */      
//     public function updateUserLineupDetails() {
//         if(func_num_args() > 0){
//             $data = func_get_arg(0);
//             $userLineup = func_get_arg(1);
//       $where = "user_lineup_id = ".$userLineup;      
//        $this->update($data, $where);     
//         }
//     }
     
//     /**
//     * Developer : Bhojraj Rawte
//     * Date : 04/07/2014
//     * Description : Get My Lineup Details
//     */
//    public function getMyLineupDetails(){
//        if (func_num_args() > 0) {
//            
//            $userID = func_get_arg(0);
//            $name = $this->select()
//                ->from(array('li'=>'user_lineup'))
//                ->setIntegrityCheck(false)                
//                ->joinLeft(array('sp'=>'sports'),'li.sports_id = sp.sports_id',array('sp.display_name'))
//                ->where('li.created_by=?',$userID)
//                ->order('li.user_lineup_id DESC')
//                ->limit(5);
//       // echo $playerId; die('1');
//        $result = $this->getAdapter()->fetchAll($name);
//        return $result;
////       echo "<pre>"; print_r($result); echo "</pre>"; die;
//        }     
//    }
    
    /**
     * Developer    : Vivek chaudhari
     * Date         : 16/07/2014
     * Description  : get contest details by user lineup id
     * @param       : <int>lineup id
     */ 
    public function getContestDetailsByLineup(){
        if(func_num_args()>0):
            $lid= func_get_arg(0);
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('ul'=>'user_lineup'),array('ul.contest_id'))
                    ->join(array('l'=>'lineup'),'l.lineup_id=ul.lineup_id')
                    ->join(array('c'=>'contests'),'ul.contest_id=c.contest_id',array('c.start_time','c.sports_id','c.contest_name','c.match_id','c.play_limit'))
                    ->where('l.lineup_id=?',$lid); 
//            echo $select;die;
            $result= $this->getAdapter()->fetchRow($select);

                if($result):
                    return $result;
                endif;
            else:
                throw new Exception('Argument not Passed');
            endif;
    }
    
    /**
     * Developer    : Vivek chaudhari
     * Date         : 06/08/2014
     * Description  : get standings (teams) in contest by contest id
     * @param       : <int>contest id
     */ 
    public function getLineupsByContestId(){
        if(func_num_args()>0):
            $cid= func_get_arg(0); 
            $select = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('ul'=>'user_lineup'))
                            ->join(array('l'=>'lineup'),'l.lineup_id=ul.lineup_id',array('l.lineup_id','l.created_by','l.rank','l.players_points','l.bonus'))
                            ->join(array('u'=>'users'),'l.created_by=u.user_id',array('u.user_name'))
                            ->join(array('c'=>'contests'),'ul.contest_id=c.contest_id',array('c.contest_id','c.contest_name','c.sports_id','c.con_type_id','c.entry_fee','c.challenge_limit','c.play_limit','c.play_type','c.find_me','c.start_time','c.end_time','c.prizes','c.status','c.con_status','c.prize_pool','c.match_id','c.fpp','c.total_entry','c.description','c.prize_payouts','c.is_featured','c.ticket_id'))
                            ->where('ul.contest_id=?',$cid)
                            ->order('ul.con_rank','ASC'); 
//            echo $select; die;
            $result= $this->getAdapter()->fetchAll($select);

                if($result):
                    return $result;
                endif;
            else:
                throw new Exception('Argument not Passed');
            endif;
    }
    
    /**
     * Developer    : Vivek chaudhari
     * Date         : 14/08/2014
     * Description  : update last edit time for lineup
     * @param       : <int>lineup_id
     */ 
    public function updateLineupEdit(){
        if(func_num_args()>0):
            $lid = func_get_arg(0);
            $currDate = Date('Y-m-d H:i:s');
            $edit = array("created_date"=>$currDate);
            $where = "lineup_id=".$lid;
            try {
                 $this->update($edit,$where);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        else:
            throw new Exception("Argument not passed");
        endif;
    }
    
    public function getUsersLineupForDay() {
        if (func_num_args() > 0) {
            
            $currentDate = func_get_arg(0);
            $subDate     = func_get_arg(1);
            $sportsId    = func_get_arg(2);
            
            $select = $this->select()
                    ->from(array('ul' => 'user_lineup'), array('ul.contest_id','ul.lineup_id'))                    
                    ->setIntegrityCheck(false)
                    ->join(array('l' => 'lineup'),'ul.lineup_id = l.lineup_id',array('l.created_by','l.lineup_id','l.rank','l.end_time'))
                    ->join(array('c' => 'contests'),'ul.contest_id = c.contest_id',array('c.prize_payouts','c.contest_id','c.prizes','c.prize_pool','c.total_entry','c.entry_fee','c.fpp'))
                    ->where('l.start_time <=?', $subDate)
                    ->where('l.start_time >=?', $currentDate)                    
                    ->orwhere('l.end_time >= l.start_time')
                    ->where('l.sports_id =?', $sportsId)
                    ->order('l.rank ASC');
//            echo $select;die;
            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        }
    }
    
    /**
     * Developer    : Vivek chaudhari
     * Date         : 21/08/2014
     * Description  : get number of contests by lineup id
     * @param       : <int>lineup id
     * @return      : <int> lineup use count
     */ 
    public function getLineupUseCount(){
        if(func_num_args()>0):
            $lid= func_get_arg(0);
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('ul'=>'user_lineup'),array('ul.contest_id'))
                    ->where('ul.contest_id !=0')
                    ->where('ul.lineup_id=?',$lid); 
            $result= $this->getAdapter()->fetchAll($select); 
                if(count($result)):
                    return count($result);
                else:
                    return 0;
                endif;
            else:
                throw new Exception('Argument not Passed');
            endif;
    }
    /**
        * Developer    : Chandra Sekhar Reddy
        * Date         : 08/09/2014
        * Description  : To get the contest_id and and created date by user
       */ 
    public function getAllContests(){
        $currentDate = date('Y:m:d H:i:s');
        $select = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('ul'=>'user_lineup'),array('ul.lineup_id','ul.contest_id'))
                ->join(array('li'=>'lineup'),'ul.lineup_id = li.lineup_id')
                ->where('ul.status=?',0)
                ->where('li.start_time <=?',$currentDate);
        $result = $this->getAdapter()->fetchAll($select);
        if($result):
            return $result;
        else:
            return 0;
        endif;
    }
    
    /**
     * Developer    : Vivek chaudhari
     * Date         : 14/10/2014
     * Description  : get contest details by user lineup id
     * @param       : <int>lineup id
     */ 
    public function getAllContestByLineupId(){
        if(func_num_args()>0){
            $lid= func_get_arg(0);
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('ul'=>'user_lineup'),array('ul.contest_id'))
                     ->join(array('l'=>'lineup'),'l.lineup_id=ul.lineup_id','')
                    ->join(array('c'=>'contests'),'ul.contest_id=c.contest_id',array('c.start_time','c.sports_id','c.contest_name','c.con_status'))
                    ->where('l.lineup_id=?',$lid); 
                try { //echo $select; die;
                    $result= $this->getAdapter()->fetchAll($select);
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }

                if($result){
                    return $result;
                }
            }else{
                throw new Exception('Argument not Passed');
            }
    }
    
        /**
     * Developer    : Vivek chaudhari
     * Date         : 30/11/2014
     * Description  : get contest lineups
     * @param       : <int>contest id
     */ 
    public function getContestLineups(){
        if(func_num_args()>0):
            $cid= func_get_arg(0); 
            $select = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('ul'=>'user_lineup'))
                            ->join(array('l'=>'lineup'),'l.lineup_id=ul.lineup_id',array('l.lineup_id','l.created_by','l.rank','l.players_points','l.bonus'))
                            ->join(array('u'=>'users'),'l.created_by=u.user_id',array('u.user_name'))
//                            ->join(array('c'=>'contests'),'ul.contest_id=c.contest_id')
                            ->where('ul.contest_id=?',$cid)
                            ->order('ul.con_rank','ASC'); 
//            echo $select; die;
            $result= $this->getAdapter()->fetchAll($select);

                if($result):
                    return $result;
                endif;
            else:
                throw new Exception('Argument not Passed');
            endif;
    }
    
   /**
     * Developer    : Bhojraj Rawte
     * Description  : delete lineup entry
     * Date         : 11/08/2014
     * @param <int> $lid
     */
   public function deleteLineup(){
       if(func_num_args()>0){
           $lid = func_get_arg(0);
           $cid = func_get_arg(1);
           
           try {
               $data = array('status'=>'0','contest_id'=>'0');
               $where = (array('lineup_id = ?' => $lid,'contest_id = ?' => $cid));
               $updateres= $this->update($data, $where); 
            } catch (Exception $e) {
                throw new Exception($e);
            }
            return $lid;       
        }else{
            throw new Exception('Argument Not Passed');
        }
   }
    
        /**
     * Developer    : Vivek chaudhari
     * Date         : 06/08/2014
     * Description  : get standings (teams) in contest by contest id
     * @param       : <int>contest id
     */ 
    public function getLineupsByConId(){
        if(func_num_args()>0):
            $cid= func_get_arg(0); 
            $select = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('ul'=>'user_lineup'))
                            ->join(array('l'=>'lineup'),'ul.lineup_id=l.lineup_id')
                            ->where('ul.contest_id=?',$cid); 
//            echo $select; die;
            $result= $this->getAdapter()->fetchAll($select);

                if($result):
                    return $result;
                endif;
            else:
                throw new Exception('Argument not Passed');
            endif;
    }
    
        /**
     * Developer    : Vivek chaudhari
     * Date         : 12/01/2015
     * Description  : update lineup by user lineup id
     * @param       : <int>user_lineup_id <array>data
     */ 
    public function updateByLid(){
        if(func_num_args()>0){
            $uLid = func_get_arg(0);
            $data = func_get_arg(1);
            try {
                 $this->update($data,"user_lineup_id=".$uLid);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }else{
            throw new Exception("Argument not passed");
        }
    }
    
     public function getLineupDetByLid(){
        if(func_num_args()>0):
            $ulid= func_get_arg(0); 
            $select = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('ul'=>'user_lineup'))
                            ->join(array('l'=>'lineup'),'ul.lineup_id=l.lineup_id')
                            ->join(array('u'=>'users'),'l.created_by=u.user_id',array('u.user_name'))
//                            ->join(array('c'=>'contests'),'ul.contest_id=c.contest_id')
                            ->where('ul.user_lineup_id=?',$ulid);
            //echo $select; die;
            $result= $this->getAdapter()->fetchRow($select);

                if($result):
                    return $result;
                endif;
            else:
                throw new Exception('Argument not Passed');
            endif;
    }
    
    //vivek
     public function getUserContestEntry(){
        if(func_num_args()>0){
            $usr_id= func_get_arg(0); 
            $con_id = func_get_arg(1);
            $select = $this->select()
                            ->setIntegrityCheck(false)
                            ->from(array('ul'=>'user_lineup'))
                            ->join(array('l'=>'lineup'),'ul.lineup_id=l.lineup_id')
                            ->where('ul.contest_id=?',$con_id)
                            ->where('l.created_by=?',$usr_id);
                    try {
                        $result= $this->getAdapter()->fetchAll($select);
                        if($result){
                            return $result;
                        }
                    } catch (Exception $exc) {
                        echo $exc->getTraceAsString();
                    }
        }else{
                throw new Exception('Argument not Passed');
        }
    }
}

?>
