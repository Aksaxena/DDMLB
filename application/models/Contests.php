<?php
class Application_Model_Contests extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'contests';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_Contests();
        return self::$_instance;
    }

    /**
     * Get all active contests
     */
    public function getActiveContests() {		
        $select = $this->select()
                ->where('status = ?', 1)  
                ->where('con_status = ?', 0)
				->order('start_time ASC')
				->order('is_featured DESC');
        $result = $this->getAdapter()->fetchAll($select);
        if ($result) {
            return $result;
        }
    }
    /**
     * Get all active contests
     */
    public function getActiveContestsForStatus() {		
        $select = $this->select()
                ->where('status = ?', 1) 
				->order('is_featured DESC');
        $result = $this->getAdapter()->fetchAll($select);
        if ($result) {
            return $result;
        }
    }
    /**
     * Get all Active contest by sports
     */
    public function getActiveSportContest() {
        if (func_num_args() > 0) {
            $sportsId = func_get_arg(0);
            $select = $this->select()
                    ->where('sports_id = ?', $sportsId)
                    ->where('status = ?', 1)
                    ->where('con_status = ?', 0);
            $result = $this->getAdapter()->fetchAll($select);
            if ($result) {
                return $result;
            }
        }
    }

    /**
     * Developer     : Vivek Chaudhari   
     * Date          : 06/06/2014
     * Description   : get contest details by contest id and active status
     * @params       : <int> contest details
     */
    public function getContestsById() {
        if (func_num_args() > 0) :
            $contestId = func_get_arg(0);
            try {
                $select = $this->select()
                        ->where('contest_id = ?', $contestId)
                        ->where('status = 1')
                        ->where('con_status = 0');
                $result = $this->getAdapter()->fetchRow($select);

                if ($result):
                    return $result;
                endif;
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        else:
            throw new Exception('Argument not passed');
        endif;
    }

    /**
     * Get all Active contest by sports
     */
    public function getInActiveSportContest() {
        if (func_num_args() > 0) {
            $sportsId = func_get_arg(0);
            $select = $this->select()
                    ->from($this, array('start_time' => new Zend_Db_Expr('DISTINCT(DATE(start_time))'), 'sports_id'))
                    ->where('sports_id = ?', $sportsId)
                    ->where('status = ?', 0);
            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 06/06/2014
     * Description : Set New Contests details
     */
    public function insertContestsDetails() {

        if (func_num_args() > 0) {
            $data = func_get_arg(0);
			//echo "<pre>"; print_r($data); die;
            try {
                $responseId = $this->insert($data);
            } catch (Exception $e) {
                throw new Exception('Unable To Insert Exception Occured :' . $e);
            }

            if ($responseId) {
                return $responseId;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 11/06/2014
     * Description : Get Contests details
     */
    public function getContestsDetailsById() {
        if (func_num_args() > 0):
            $contestID = func_get_arg(0);
            try {
                $select = $this->select()
                        ->where('contest_id =?', $contestID);

                $result = $this->getAdapter()->fetchRow($select);


                if ($result) :
                    return $result;
                endif;
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * developer    :- vivek chaudhari
     * date         :- 20/06/2014
     * description  :- get available username in contest
     * @params      :- <int>contest id
     */
    public function getUsernameByContestId() {
        if (func_num_args() > 0):
            $contestID = func_get_arg(0);
            try {
                $select = $this->select()
                        ->from(array('u' => 'users'), array('u.user_name'))
                        ->setIntegrityCheck(false)
                        ->join(array('l' => 'lineup'), 'l.created_by = u.user_id', array(''))
                        ->join(array('ul' => 'user_lineup'), 'ul.lineup_id = l.lineup_id', array(''))
                        ->where('ul.contest_id=?', $contestID);
                $result = $this->getAdapter()->fetchAll($select);

                if ($result) :
                    return $result;
                endif;
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 18/06/2014
     * Description : Get Match details
     */
    public function getAllMatchIds() {

        $select = $this->select()
                ->from(array('c' => 'contests'), array('c.match_id'))
                ->setIntegrityCheck(false)
                ->join(array('s' => 'sports'), 's.sports_id = c.sports_id', array('s.display_name'))
                ->where('c.status = ?', '1');

        $result = $this->getAdapter()->fetchAll($select);


        if ($result) {
            return $result;
        }
    }

    /**
     * developer    :- vivek chaudhari
     * date         :-19/06/2014
     * description  :- get match id between current time interval
     */
    public function getMatchIdByDate() {
        if (func_num_args() > 0):
            $sportId = func_get_arg(0);
            try {

                //this query gives the one day interval time from local machine
                $between = "start_time BETWEEN (DATE_ADD(CURDATE(),INTERVAL 1 SECOND)) AND (DATE_ADD(CURDATE(),INTERVAL '23:59' HOUR_MINUTE))";
                $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('c' => 'contests'), array('c.match_id', 'c.contest_id'))
                        ->where($between)
                        ->where('sports_id=?', $sportId);

                $result = $this->getAdapter()->fetchAll($select);

                if ($result):
                    return $result;
                endif;
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }

        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
	
	/**
     * developer    :- prince kumar dwivedi
     * date         :- 15/10/2017
     * description  :- get match id between current time interval by its end date
    **/
	
    public function getMatchIdByEndDate() {
        if (func_num_args() > 0):
            $sportId = func_get_arg(0);
            try {

                //this query gives the one day interval time from local machine
                $between = "end_time BETWEEN (DATE_ADD(CURDATE(),INTERVAL 1 SECOND)) AND (DATE_ADD(CURDATE(),INTERVAL '23:59' HOUR_MINUTE))";
                $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from(array('c' => 'contests'), array('c.match_id', 'c.contest_id'))
                        ->where($between)
                        ->where('sports_id=?', $sportId);

                $result = $this->getAdapter()->fetchAll($select);

                if ($result):
                    return $result;
                endif;
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }

        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
	
    /**
     * developer    :- vivek chaudhari
     * date         :-19/06/2014
     * description  :- get match id between current time interval
     * @params      :- param1<int> = match id, param2<int> = changed status, param3<int> = contest id 
     */
    public function updateStatusByMatchId() {
        if (func_num_args() > 0):
            $matchId = func_get_arg(0);
            $status = func_get_arg(1);
            $contestId = func_get_arg(2);

            $data = array('con_status' => $status);
            $where = array('match_id' => $matchId, 'contest_id' => $contestId);
            try {
                $update = $this->update($data, $where);
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
            if ($update):
                return $update;
            endif;
        else:
            throw new Exception('Argument not passed');
        endif;
    }

    /*
     * Developer    :   Abhinish Kumar Singh
     * Date         :   21/06/2014
     * Description  :   Gets the sports details based on the id selected
     */

    public function getSportsById() {
        if (func_num_args() > 0) {

            $sportsId = intval(func_get_arg(0));
            if ($sportsId == 0) {
                $select = $this->select()
                        ->where('status = ?', 1)
                        ->where('con_status = ?', 0)
                        ->order('contest_id DESC');
            } else {
                $select = $this->select()
                        ->where('sports_id = ?', $sportsId)
                        ->where('status = ?', 1)
                        ->where('con_status = ?', 0)
                        ->order('contest_id DESC');
            }

            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        }
    }

    /**
     * Developer : MAnoj
     * Date : 11/06/2014
     * Description : Get Contests Fee
     */
    public function getContestFeeById() {
        if (func_num_args() > 0):
            $contestID = func_get_arg(0);
            try {
                $select = $this->select()
                        ->from($this, array('entry_fee', 'total_entry', 'play_limit', 'fpp', 'ticket_id', 'contest_name'))
                        ->where('contest_id =?', $contestID);

                $result = $this->getAdapter()->fetchRow($select);


                if ($result) :
                    return $result;
                endif;
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 11/06/2014
     * Description : Get Contests details
     */
    public function getContestsForInviteId() {
        if (func_num_args() > 0):
            $contestID = func_get_arg(0);
            try {
                $select = $this->select()
                        ->from(array('c' => 'contests'), array('c.contest_name', 'c.entry_fee', 'c.play_limit', 'c.start_time', 'c.prize_pool', 'c.total_entry', 'c.contest_id'))
                        ->setIntegrityCheck(false)
                        ->joinLeft(array('sp' => 'sports'), 'sp.sports_id = c.sports_id', array('sp.display_name'))
                        ->joinLeft(array('ct' => 'contests_type'), 'ct.con_type_id = c.con_type_id', array('ct.display_name as contest_display_name'))
                        ->where('c.contest_id =?', $contestID);

                $result = $this->getAdapter()->fetchRow($select);


                if ($result) :
                    return $result;
                endif;
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * Description : update contest entry
     * Date : 07/01/2014
     * @param <int> $contest_id
     * @return <int>
     */
    public function updateTotalEntry() {
        if (func_num_args() > 0) {
            $contest_id = func_get_arg(0);
            try {
                $data = array('total_entry' => new Zend_Db_Expr('total_entry + 1'));

                $result = $this->update($data, 'contest_id =' . $contest_id);
                if ($result) {
                    return $result;
                }
            } catch (Exception $e) {
                throw new Exception($e);
            }
        } else {
            throw new Exception("Argument Not Passed");
        }
    }

    public function getEntries() {
        try {
            $select = $this->select()
                    ->from($this, array('contest_id', 'total_entry'))
                    ->where('status = ?', 1)
                    ->where('con_status = ?', 0)
                    ->order('contest_id DESC');

            $result = $this->getAdapter()->fetchAll($select);

            if ($result) :
                return $result;
            endif;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 23/07/2014
     * Description : Get Contests details by User id and Contest Type
     */
    public function getUserCreateContestDetails() {
        if (func_num_args() > 0) {

            $user_id = func_get_arg(0);
            $contest_type = func_get_arg(1);
            $select = $this->select()
                    ->from($this, array('contest_name', 'contest_id', 'entry_fee'))
                    ->where('created_by = ?', $user_id)
                    ->where('con_type_id = ?', $contest_type)
                    ->where('con_status = ?', 0)
                    ->where('status = ?', 1)
                    ->order('contest_id DESC');
            $result = $this->getAdapter()->fetchAll($select);


            if ($result) :
                return $result;
            endif;
        }
    }

    /**
     * Developer : Abhinish Kumar Singh
     * Date : 29/07/2014
     * Description : Get User created contest details based on user_id
     */
    public function getUserContests() {
        if (func_num_args() > 0) {
            $user_id = func_get_arg(0);
            $select = $this->select()
                    ->from($this, array('contest_name', 'contest_id', 'entry_fee'))
                    ->where('created_by = ?', $user_id)
                    ->where('con_status = ?', 0)
                    ->where('status = ?', 1)
                    ->order('contest_id DESC');
            $result = $this->getAdapter()->fetchAll($select);


            if ($result) :
                return $result;
            endif;
        }
    }

    /**
     * Developer    : Vivek Chaudhari
     * Description  : update contest entry
     * Date         : 08/08/2014
     * @param <array> $contest_id
     * @return <array>
     */
    public function getUnjoinedContest() {
        if (func_num_args() > 0):
            $conIds = func_get_arg(0);
            $sport_id = func_get_arg(1);
            $select = $this->select()
                    ->where('contest_id NOT IN (?)', $conIds)
                    ->where('status=1')
                    ->where('con_status!=1')
                    ->where('sports_id=?', $sport_id);					
            $result = $this->getAdapter()->fetchAll($select);
            if ($result):
                return $result;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
	
	
    public function getUnjoinedByStartTymeContest() {
        if (func_num_args() > 0):
            $conIds = func_get_arg(0);
            $sport_id = func_get_arg(1);
            $start_time = func_get_arg(2);
            $play_limit = func_get_arg(3);
            $select = $this->select()
                    ->where('contest_id NOT IN (?)', $conIds)
                    ->where('status=1')
                    ->where('con_status!=1')
                    ->where('match_id =?', $start_time)				
                    ->where('total_entry <?', $play_limit)				
                    ->where('sports_id=?', $sport_id);					
            $result = $this->getAdapter()->fetchAll($select);
            if ($result):
                return $result;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
	
    /**
     * Developer    : Vivek Chaudhari
     * Description  : get all contest upto current time
     * Date         : 14/08/2014
     * @param       : no params
     * @return <array>
     */
    public function getContestsForCancel() {
        $currdate = date('y-m-d H:i:s');
        try {
            $select = $this->select()
                    ->where('start_time<=?', $today)
                    ->where('con_status!=?', 3);

            $result = $this->getAdapter()->fetchAll($select);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }

        if ($result) {
            return $result;
        }
    }

    /**
     * Developer    : Vivek Chaudhari
     * Description  : update contest status for cancelling contest from cron controller
     * Date         : 14/08/2014
     * @param       : <array> contest Ids
     * @return      : empty
     */
    public function updateContestStatus() {

        if (func_num_args() > 0):
            $conIds = func_get_arg(0);
            $data = array('con_status' => 3);
            try {
                foreach ($conIds as $key => $value):
                    $where = "contest_id=" . $value;
                    $this->update($data, $where);
                endforeach;
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        else:
            throw new Exception("Argument not passed");
        endif;
    }

    /**
     * Developer    : Vivek Chaudhari
     * Description  : get all cancelled contest
     * Date         : 14/08/2014
     * @param       : no params
     * @return <array> cancelled contest ids
     */
    public function getCancelContest() {

        try {
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from('contests', array('contest_id'))
                    ->where('con_status =?', 3);
            $result = $this->getAdapter()->fetchAll($select);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }

        if ($result):
            return $result;
        endif;
    }

    /*
     * Developer : Bhojraj Rawte
     * Date : 19/08/2014
     * Description : Update contests entry
     */

    public function updateContestEntry() {

        if (func_num_args() > 0):
            $contestid = func_get_arg(0);
            // $contestdata = array('total_entry' =>new Zend_DB_Expr ('total_entry + 1') );
            $data = array('total_entry' => new Zend_Db_Expr('total_entry - 1 '));
            try {
                $result = $this->update($data, 'contest_id = "' . $contestid . '"');
                if ($result) {
                    return $result;
                } else {
                    return 0;
                }
            } catch (Exception $e) {
                throw new Exception($e);
            }
        else :
            throw new Exception('Argument Not Passed');
        endif;
    }

    public function getcontestdetailsbyconid($conId) {
        if (func_num_args() > 0):
            $contestid = func_get_arg(0);
            $select = $this->select()
                    ->from('contests')
                    ->where('contest_id="' . $contestid . '"');
            $result = $this->getAdapter()->fetchAll($select);
            if ($result) {
                return $result;
            } else {
                return 0;
            }
        endif;
    }

    /*
     * Developer : Chandra Sekhar Reddy
     * Date : 16/09/2014
     * Description : Contest Details by contest Id
     */

    public function getcontestdetailsbycontestId() {
        if (func_num_args() > 0):
            $contestid = func_get_arg(0);
            $select = $this->select()
                    ->from('contests')
                    ->setIntegrityCheck(false)
                    ->where('contest_id IN (?)', $contestid);
            $result = $this->getAdapter()->fetchAll($select);
            if ($result) {
                return $result;
            } else {
                return 0;
            }
        endif;
    }

    /**
     * Developer    : Vivek Chaudhari
     * Description  : cancel contest if entry is not full
     * Date         : 14/10/2014
     * @param       :<int> contest Id
     * @return <array> 
     */
    public function updateCancelContestStatus() {

        if (func_num_args() > 0) {
            $conId = func_get_arg(0);
            $data = array('status' => 0, 'con_status' => 3);
            try {
                $where = "contest_id=" . $conId;
                $this->update($data, $where);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        } else {
            throw new Exception("Argument not passed");
        }
    }

    /**
     * Developer    : Vivek Chaudhari
     * Description  : get gauranteed contest for a day
     * Date         : 22/10/2014
     * @param       : none
     * @return <array> 
     */
    public function getTodaysGauranteedContest() {
        if (func_num_args() > 0) {
            $currDate = func_get_arg(0);
            $subDate = func_get_arg(1);
            $sportId = func_get_arg(2);

            $select = $this->select()
                    ->where('con_type_id=?', 1)
                    ->where('start_time >= ?', $currDate)
                    ->where('start_time <= ?', $subDate)
                    ->where('status = ?', 1)
                    ->where('sports_id = ?', $sportId);
            $result = $this->getAdapter()->fetchAll($select);
            if ($result) {
                return $result;
            }
        } else {
            throw new Exception('Argument not passed');
        }
    }

    /**
     * Description : update contest end date
     * Date : 07/01/2014
     * @param <int> $contest_id
     * @return <int>
     */
    public function updateEndDate() {
        if (func_num_args() > 0) {
            $contest_id = func_get_arg(0);
            $dtae = func_get_arg(1);
            try {
                $data = array('end_time' => $dtae);

                $result = $this->update($data, 'contest_id =' . $contest_id);
                if ($result) {
                    return $result;
                }
            } catch (Exception $e) {
                throw new Exception($e);
            }
        } else {
            throw new Exception("Argument Not Passed");
        }
    }

    /**
     * Description : update contest end date
     * Date : 07/11/2014
     * @param <int> $contest_id, <array> update data array
     * @return <int>
     */
    public function updateStatus() {
        if (func_num_args() > 0) {
            $contest_id = func_get_arg(0);
            $data = func_get_arg(1);
            try {
				$result = $this->update($data, 'contest_id =' . $contest_id);
                if ($result) {
                    return $result;
                } else {
					return false;
				}
            } catch (Exception $e) {
                throw new Exception($e);
            }
        } else {
            throw new Exception("Argument Not Passed");
        }
    }

    public function getcontestnamebyuid() {
        if (func_num_args() > 0) {
            $conid = func_get_arg(0);
            $select = $this->select()
                    ->from($this, array('contest_name'))
                    ->where('contest_id IN(?)', $conid);
            try {
                $select = str_replace("'", "", $select);
                $result = $this->getAdapter()->fetchAll($select);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }

            if ($result) {
                return $result;
            }
        } else {
            throw new Exception('Argument not Passed');
        }
    }

    /**
     * Get all Active contest by sports
     */
    public function getActiveSportContestByDate() {
        if (func_num_args() > 0) {
            $sportsId = func_get_arg(0);
            $lineupDate = func_get_arg(1);
            $afterDate = date('Y-m-d', strtotime('+1 day', strtotime($lineupDate)));
            $select = $this->select()
                    ->where('sports_id = ?', $sportsId)
                    ->where('status = ?', 1)
                    ->where('play_limit != total_entry')
                    ->where('start_time >=?', $lineupDate)
                    ->where('start_time <=?', $afterDate)
                    ->where('con_status = ?', 0);

            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        }
    }

    /**
     * developer    :- vivek chaudhari
     * date         :- 15/12/2014
     * description  :- update contest by contest id
     * @params      :- param1<int> = contest id, param2<array> = update data array 
     */
    public function updateContestById() {
        if (func_num_args() > 0) {
            $contestId = func_get_arg(0);
            $updateData = func_get_arg(1);
			//echo $contestId; echo "<pre>"; print_r($updateData); echo "</pre>";
            try {
                $result = $this->update($updateData, 'contest_id =' . $contestId);
                if ($result) {
                    return $result;
                }
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
        } else {
            throw new Exception('Argument not passed');
        }
    }

    /**
     * Get all Active contest by sports
     */
    public function getActiveUpcomingContest() {
        $select = $this->select()
                ->where('status = ?', 1)
                ->where('con_status = ?', 0);

        $result = $this->getAdapter()->fetchAll($select);

        if ($result) {
            return $result;
        }
    }

    /**
     * Get all Active contest by sports
     */
    public function getActiveLiveContest() {
        $sportId = func_get_arg(0);
        $select = $this->select()
                ->where('status = ?', 1)
                ->where('con_status = ?', 2)
                ->where('sports_id = ?', $sportId);

        $result = $this->getAdapter()->fetchAll($select);

        if ($result) {
            return $result;
        }
    }

   

}

?>
