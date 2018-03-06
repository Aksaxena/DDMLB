<?php

//Developer    : Vivek chaudhari
class Application_Model_TicketUsers extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'ticket_users';

    private function __clone() {
        
    }

    public static function getInstance() {
        if (!is_object(self::$_instance))
            self::$_instance = new Application_Model_TicketUsers();
        return self::$_instance;
    }

    /**
     * Developer    : Vivek chaudhari
     * Date         : 18/06/2015
     * Description  : update ticket user for ticket
     * @param       : <int>ticket Id.
     * @return      : <array>ticket details
     */
    public function getUserById() {
        if (func_num_args() > 0) {
            $uId = func_get_arg(0); //user id
            $tId = func_get_arg(1); // ticket id
            $cId = func_get_arg(2); // contest id

            $select = $this->select()
                    ->where('user_id=?', $uId)
                    ->where('ticket_id=?', $tId)
                    ->where('contest_id=?', $cId);

            try {
                $result = $this->getAdapter()->fetchAll($select);
                if ($result) {
                    return $result;
                } else {
                    return 0;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        } else {
            throw new Exception("Argument not passed");
        }
    }

    public function allticketUsers() {

        if (func_num_args() > 0) {

            $tId = func_get_arg(0);
            $cId = func_get_arg(1);
            $select = $this->select()
                    ->where('ticket_id=?', $tId)
                    ->where('contest_id=?', $cId);
            try {
                $result = $this->getAdapter()->fetchAll($select);
                if ($result) {
                    return $result;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        } else {
            throw new Exception("Argumnet not passed");
        }
    }

    public function insertTicketUser() {
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
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

}
