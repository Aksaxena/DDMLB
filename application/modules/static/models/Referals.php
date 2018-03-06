<?php

class Static_Model_Referals extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'refer_friends';

    private function __clone() {
        
    }

//Avoid Cloning

    public static function getInstance() {
        if (!is_object(self::$_instance))
            self::$_instance = new Static_Model_Referals();
        return self::$_instance;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 02/07/2014
     * Description  :  Get all offer details
     */
    public function getReferalDeatails() {
        if (func_num_args() > 0):
            $userId = func_get_arg(0);
//            $select = $this->select()
//                    ->where('ref_by=?', $userId);

         $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('r'=>'refer_friends'))
                    ->joinLeft(array('u'=>'users'),'r.email=u.email',array('u.fname','u.lname'))
                    ->where('r.ref_by=?', $userId);

            $result = $this->getAdapter()->fetchAll($select);
            if ($result):
                return $result;
            endif;
        else:
            throw new Exception("Argument not passed");
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 02/07/2014
     * Description  : add new referals or update count of request
     */
    public function addReferal() {
        if (func_num_args() > 0):
            $data = func_get_arg(0);

            $this->insert($data);
        else:
            throw new Exception("Argument not passed");
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 03/07/2014
     * Description  : Update reminder count by ref_id
     */
    public function updateReminder() {
        if (func_num_args() > 0):
            $rId = func_get_arg(0);
            $db = Zend_Db_Table::getDefaultAdapter();
            $where = $db->quoteInto('ref_id IN (?)', $rId);
            $udata = array('req_count' => new Zend_Db_Expr('req_count + 1'), 'ref_date' => new Zend_Db_Expr('CURDATE()'));
            try {
                $db->update('refer_friends', $udata, $where);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        else:
            throw new Exception("Argument not passed");
        endif;
    }

    /**
     * Developer    : Bhojraj Rawte
     * Date         : 03/12/2014
     * Description  :  Get all Referal details
     */
    public function getReferalDataByIDs() {
        if (func_num_args() > 0):
            $refIds = func_get_arg(0);
            $select = $this->select()
                    ->where('ref_id IN (?)', $refIds);

            $result = $this->getAdapter()->fetchAll($select);
            if ($result):
                return $result;
            endif;
        else:
            throw new Exception("Argument not passed");
        endif;
    }

    public function getrefersemails() {
        $select = $this->select()
                ->from($this, array('email'))
                ->where('acceptance = 0');
        $result = $this->getAdapter()->fetchAll($select);
        if ($result) {
            return $result;
        }
    }

    /* dev:priyanka varanasi
     * date:31/12/2014
     * decs:to cahnge the status of acceptanc on the basis of  ref ids of a particular email
     */

    public function changeacceptancetoone() {
        if (func_num_args() > 0) {
            $emailids = func_get_arg(0);
            $data['acceptance'] = 1;
            $select = $this->select()
                    ->from($this, array('ref_id'))
                    ->where('email IN (?)', $emailids);
            $result = $this->getAdapter()->fetchCol($select);
            if (!empty($result)) {
                foreach ($result as $value) {
                    $update = $this->update($data, 'ref_id =' . $value);
                }
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    public function validateReferFriendEmail() {

        if (func_num_args() > 0) {
            $userEmail = func_get_arg(0);
            try {

                $select = $this->select()
                        ->from($this)
                        ->where('email = ?', $userEmail);

                $result = $this->getAdapter()->fetchRow($select);
            } catch (Exception $e) {
                throw new Exception('Unable to access data :' . $e);
            }

            if ($result) {
                return $result;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    public function updateReferFriend() {

        if (func_num_args() > 0) {

            $email = func_get_arg(0);
            $acceptance = func_get_arg(1);
            $status = func_get_arg(2);

            try {
                $row = array(
                    'status' => $status,
                    'acceptance' => $acceptance
                );
                $where = array();
                $where[] = $this->getAdapter()->quoteInto('email = ?', $email);

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

        public function validateReferEmailByUser() {

        if (func_num_args() > 0) {
            $email = func_get_arg(0);
            $userId = func_get_arg(1);
            try {
                $select = $this->select()
                        ->from($this)
                        ->where('ref_by =?',$userId)
                        ->where('email = ?', $email);

                $result = $this->getAdapter()->fetchRow($select);
            } catch (Exception $e) {
                throw new Exception('Unable to access data :' . $e);
            }

            if ($result) {
                return $result;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }
}

?>