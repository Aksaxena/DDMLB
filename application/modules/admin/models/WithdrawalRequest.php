<?php

class Admin_Model_WithdrawalRequest extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'withdrawal_request';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Admin_Model_WithdrawalRequest();
        return self::$_instance;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 11/03/2014
     * Description : Get All Pandding Paymet
     */
    public function getWithdrawalPaymentDeatils() {
        $select = $this->select()
                ->from(array('wr' => 'withdrawal_request'))
                ->setIntegrityCheck(false)
                ->join(array('u' => 'users'), 'u.user_id = wr.user_id', array("u.user_name"))
                ->order('wr.withdrawal_id DESC');
//                           ->where('wr.status =?','0');

        $result = $this->getAdapter()->fetchAll($select);
        if ($result) :
            return $result;
        endif;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 12/03/2014
     * Description : Get withdrawal details by id
     */
    public function getWithdrawalDeatilsById() {
        if (func_num_args() > 0):
            $wid = func_get_arg(0);
            try {
                $select = $this->select()
                        ->from(array('wr' => 'withdrawal_request'))
                        ->setIntegrityCheck(false)
                        ->join(array('u' => 'users'), 'u.user_id = wr.user_id', array("u.user_name"))
                        ->where('wr.withdrawal_id =?', $wid);

                $result = $this->getAdapter()->fetchRow($select);
            } catch (Exception $e) {
                throw new Exception($e);
            }
            if ($result) :
                return $result;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 12/03/2014
     * Description : Get All Paymet Details
     */
    public function getPaymentDeatils() {
        $select = $this->select()
                ->from(array('wr' => 'withdrawal_request'))
                ->setIntegrityCheck(false)
                ->join(array('u' => 'users'), 'u.user_id = wr.user_id', array("u.user_name"))
                ->where('wr.status =?', '1');

        $result = $this->getAdapter()->fetchAll($select);
        if ($result) :
            return $result;
        endif;
    }

    /**
     * Developer : Ramanjineyulu
     * Date : 01/07/2014
     * Description : Get All Withdrawal details
     */
    public function getAllDeatils() {
        $select = $this->select()
                ->from(array('wr' => 'withdrawal_request'))
                ->setIntegrityCheck(false)
                ->join(array('u' => 'users'), 'u.user_id = wr.user_id', array("u.user_name"))
                ->order('requested_amt DESC');

        $result = $this->getAdapter()->fetchAll($select);

        if ($result) {
            return $result;
        }
    }

    public function getApprovalDeatils() {
        if (func_num_args() > 0):
            $wid = func_get_arg(0);
            try {
                $data = array('status' => '1');
                $result = $this->update($data, "withdrawal_id = '" . $wid . "'");
            } catch (Exception $e) {
                throw new Exception($e);
            }
            if ($result):
                return $wid;
            else:
                return 0;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 06/08/2014
     * Description : Get depositor Details
     */
    public function getdepositorDeatils() {
        $select = $this->select()
                ->from(array('wr' => 'withdrawal_request'))
                ->setIntegrityCheck(false)
                ->join(array('u' => 'users'), 'u.user_id = wr.user_id', array("u.user_name"))
                ->where('wr.status =?', '1');

        $result = $this->getAdapter()->fetchAll($select);
        if ($result) :
            return $result;
        endif;
    }

    public function updatestatus() {
        if (func_num_args() > 0) {
            $withdrawalId = func_get_arg(0);
            try {
                $row = array(
                    'status' => 1,
                    'pay_date' => date('Y-m-d H:i:s')
                );

                $where = array();
                $where[] = $this->getAdapter()->quoteInto('withdrawal_id = ?', $withdrawalId);

                $result = $this->update($row, $where);
            } catch (Exception $e) {
                throw new Exception('Unable To Update Exception Occured :' . $e);
            }

            if ($result) {
                return $result;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }
    
    public function updateByID() {
        if (func_num_args() > 0):
            $wid = func_get_arg(0);
			$updateData = func_get_arg(1);
            try {
                $result = $this->update($updateData, "withdrawal_id = '" . $wid . "'");
            } catch (Exception $e) {
                throw new Exception($e);
            }
            if ($result):
                return $wid;
            else:
                return 0;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }

}
