<?php

class Admin_Model_Tickets extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'ticket_system';

    private function __clone() {
//avoid cloning        
    }

//singlton pattern 
    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Admin_Model_Tickets();
        return self::$_instance;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : get ticket details
     * @return      : get all tickets
     */
    public function getValidTickets() {

        try {
            $select = $this->select()
                    ->from(array('t' => 'ticket_system'), array('t.ticket_id', 't.code', 't.bonus_amt', 't.valid_from', 't.valid_upto', 't.limitation', 't.status'))
                    ->setIntegrityCheck(false)
                    ->joinLeft(array('con' => 'contests'), 't.ticket_id = con.ticket_id', array("con.con_status"))
                    ->where('con.con_status = ?', '0')
                    ->order('t.ticket_id DESC');


            $result = $this->getAdapter()->fetchAll($select);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        if ($result) :
            return $result;
        endif;



        // echo "<pre>"; print_r($result); echo "</pre>"; die;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : Activate the ticket by id
     * @params      : ticketId
     */
    public function ticketActive() {
        if (func_num_args() > 0):
            $tid = func_get_arg(0);
            try {
                $data = array('status' => '1');
                $result = $this->update($data, 'ticket_id = "' . $tid . '"');
            } catch (Exception $e) {
                throw new Exception($e);
            }
            if ($result):
                return $result;
            else:
                return 0;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : deactivate the ticket by id
     * @params      : ticketID
     */
    public function ticketDeactive() {
        if (func_num_args() > 0):
            $tid = func_get_arg(0);
            try {
                $data = array('status' => '0');
                $result = $this->update($data, 'ticket_id = "' . $tid . '"');
            } catch (Exception $e) {
                throw new Exception($e);
            }
            if ($result):
                return $result;
            else:
                return 0;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : delete ticket by ticketID
     * @params      : ticketID
     */
    public function ticketDelete() {

        if (func_num_args() > 0):
            $tid = func_get_arg(0);
            try {
                $db = Zend_Db_Table::getDefaultAdapter();
                $where = (array('ticket_id = ?' => $tid));
                $db->delete('ticket_system', $where);
            } catch (Exception $e) {
                throw new Exception($e);
            }
            return $tid;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : get ticket details by ticketID
     * @params      : ticketID
     */
    public function getTicketDetailsById() {
        if (func_num_args() > 0):
            $tid = func_get_arg(0);
            $select = $this->select()
                    ->from($this)
                    ->where('ticket_id=?', $tid);
            $result = $this->getAdapter()->fetchRow($select);

            if ($result) :
                return $result;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : update ticket by ticket by id
     * @params      : ticketID
     */
    public function updateTicketById() {
        if (func_num_args() > 0):
            $ticketId = func_get_arg(0);
            $edit = func_get_arg(1);
            $where = "ticket_id = " . $ticketId;
            $check = $this->update($edit, $where);

            if ($check):
                return 1;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : upload new ticket data
     * @params      : ticket details array
     */
    public function uploadNewTicket() {
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            try {
                $responseId = $this->insert($data);
            } catch (Exception $e) {
                throw new Exception($e);
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
     * Date : 29/07/2014
     * Description : count Tickets
     */
    public function getTotalTickets() {

        $select = $this->select()
                ->from($this, array("Totaltickets" => "COUNT(*)"));

        $result = $this->getAdapter()->fetchRow($select);
        if ($result) {
            return $result['Totaltickets'];
        } else {
            return false;
        }
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 16/08/2014
     * Description  : get ticket details
     * @return      : <array>get all active tickets
     */
    public function getActiveTickets() {
        try {
            $select = $this->select()
                    ->from($this);
//                        ->where('status =?',1);
            $result = $this->getAdapter()->fetchAll($select);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        if ($result) :
            return $result;
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 19/12/2014
     * Description  : get ticket details by ticket code
     * @params      : ticket code
     */
    public function getTicketByCode() {
        if (func_num_args() > 0) {
            $tcode = func_get_arg(0);
            $select = $this->select()
                    ->from($this)
                    ->where('code=?', $tcode);
            try {
                $result = $this->getAdapter()->fetchRow($select);

                if ($result) {
                    return $result;
                } else {
                    return 0;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    public function checkticketcode() { 
        if (func_num_args() > 0) {
            $ticode = func_get_arg(0);
            $select = $this->select()
                    ->where('code=?', $ticode);
            try {
                $result = $this->getAdapter()->fetchRow($select);

                if ($result) {
                    return $result;
                } else {
                    return 0;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

}
