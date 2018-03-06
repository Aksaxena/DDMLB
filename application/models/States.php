<?php
class Application_Model_States extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'states';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_States();
        return self::$_instance;
    }

    public function getStates() {

        $select = $this->select()
                ->from($this)
                ->where('status = 1');
        $result = $this->getAdapter()->fetchAll($select);
        if ($result) {
            return $result;
        }
    }
    public function getStateIdByStateName() {

        if (func_num_args() > 0) {
            $stateName = func_get_arg(0);

            $select = $this->select()
                    ->from($this)
                    ->where('status = 1')
                    ->where('name = ?', $stateName);

            $result = $this->getAdapter()->fetchRow($select);
            if ($result) {
                return $result;
            }
        }
    }

    public function getStateByCountry() {
        if (func_num_args() > 0) {
            $countryId = func_get_arg(0);

            $select = $this->select()
                    ->from($this)
                    ->where('status = 1')
                    ->where('country_id = ?', $countryId);

            $result = $this->getAdapter()->fetchAll($select);
            if ($result) {
                return $result;
            }
        }
    }
}