<?php

class Application_Model_Sports extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'sports';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_Sports();
        return self::$_instance;
    }

    public function getSports() {
        $select = $this->select()
                ->from($this, array('display_name', 'sports_id'))
                ->where('status = 1');
        $response = $this->getAdapter()->fetchAll($select);
        if ($response) {
            return $response;
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 22/05/2014
     * Description : GET Sports details
     */
    public function getSportsDetailsByName() {
        if (func_num_args() > 0) {

            $sportsName = func_get_arg(0);

            $select = $this->select()
                    ->from($this, array('sports_id'))
                    ->where('status = 1')
                    ->where('display_name = ?', $sportsName);
            $response = $this->getAdapter()->fetchRow($select);
            if ($response) {
                return $response;
            }
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 06/06/2014
     * Description : GET Sports Name by ID
     */
    public function getSportsDetailsByID() {
        if (func_num_args() > 0) {

            $sportsID = func_get_arg(0);

            $select = $this->select()
                    ->from($this, array('display_name'))
                    ->where('status = 1')
                    ->where('sports_id = ?', $sportsID);
            $response = $this->getAdapter()->fetchRow($select);
            if ($response) {
                return $response;
            }
        }
    }

    public function getSportsAndContest() {
        if (func_num_args() > 0) {
            $date = func_get_arg(0);
            $weekDate = date('Y-m-d', strtotime($date . " +1 week"));
            $select = $this->select()
                    ->from(array('sp' => 'sports'), array('sp.display_name', 'sp.sports_id'))
                    ->setIntegrityCheck(false)
                    ->joinLeft(array('gs' => 'game_stats'), 'sp.sports_id = gs.sports_id', array('gs.game_stat'))
                    ->where('gs.game_date >= ?', $date)
                    ->where('gs.game_date <= ?', $weekDate)
                    ->where('sp.status = 1');

            
            $response = $this->getAdapter()->fetchAll($select);
            if ($response) {
                return $response;
            }
        }
    }

}

?>
