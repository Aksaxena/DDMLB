<?php

class Application_Model_UserTransactions extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'user_transactions';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_UserTransactions();
        return self::$_instance;
    }

    public function insertUseTransactions() {

        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            try {
                $responseId = $this->insert($data);
                if ($responseId) {
                    return $responseId;
                }
            } catch (Exception $e) {
                throw new Exception('Unable To Insert Exception Occured :' . $e);
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }
	
	public function getUserTransactionByconfirmationCode() {
        if (func_num_args() > 0):
            $paymentID = func_get_arg(0);
            
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('ut'=>'user_transactions'))
                    ->where('ut.confirmation_code=?', $paymentID); 
            try {
                $result = $this->getAdapter()->fetchAll($select);
                if ($result) {
                    return true;
                } else {
					return false;
				}
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }


        else:
            throw new Exception("user Id not Provided");
        endif;
    }
	
    /*
     * Developer    :   Vivek Chaudhari
     * Date         :   28/07/2014
     * Description  :   get current user transaction details
     * @params       :   param <int>= current user id
     */

    public function getTransactionDetailByUserId() {
        if (func_num_args() > 0):
            $uId = func_get_arg(0);
            $day = Date('Y-m-d H:m:s', strtotime('-3days'));
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('ut'=>'user_transactions'))
                    ->joinLeft(array('ct'=>'contest_transactions'),'ct.transaction_id=ut.transaction_id',array('ct.contest_id'))
                    ->joinLeft(array('c'=>'contests'),'ct.contest_id=c.contest_id',array('c.contest_name'))
                    ->where('ut.transaction_date>=?', $day)
                    ->where('ut.user_id=?', $uId); 
            try {
                $result = $this->getAdapter()->fetchAll($select);
                if ($result) {
                    return $result;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }


        else:
            throw new Exception("user Id not Provided");
        endif;
    }
	
	public function getTransactionDetailByOnlyUserId() {
        if (func_num_args() > 0):
            $uId = func_get_arg(0);
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('ut'=>'user_transactions'))
                    ->joinLeft(array('ct'=>'contest_transactions'),'ct.transaction_id=ut.transaction_id',array('ct.contest_id'))
                    ->joinLeft(array('c'=>'contests'),'ct.contest_id=c.contest_id',array('c.contest_name'))
					->where('ut.user_id!=4')
                    ->where('ut.user_id=?', $uId); 
            try {
                $result = $this->getAdapter()->fetchAll($select);
                if ($result) {
                    return $result;
                }
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }


        else:
            throw new Exception("user Id not Provided");
        endif;
    }

    /*
     * Developer    :   Vivek Chaudhari
     * Date         :   28/07/2014
     * Description  :   get current user transaction details
     * @params      :   param <int>= current user id, param2,3<string> = dates
     */
 public function getFilteredTransaction() {
        if (func_num_args() > 0):
            $uId = func_get_arg(0);
            $curDate = func_get_arg(1);
            $subDate = func_get_arg(2);

            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('ut'=>'user_transactions'))
                    ->joinLeft(array('ct'=>'contest_transactions'),'ct.transaction_id=ut.transaction_id',array('ct.contest_id'))
                    ->joinLeft(array('c'=>'contests'),'ct.contest_id=c.contest_id',array('c.contest_name'))
                   
                    ->where('ut.user_id=?', $uId);

            if ($subDate != -1):
                $select->where('ut.transaction_date<=?', $curDate)
                        ->where('ut.transaction_date>=?', $subDate);
            elseif ($subDate == -1):
                $select->where('ut.transaction_date<=?', $curDate);
            endif;

            $result = $this->getAdapter()->fetchAll($select);
            if ($result):
                return $result;
            endif;
        else:
            throw new Exception("Argument Not Provided");
        endif;
    }
    public function getFilteredTransaction_old() {
        if (func_num_args() > 0):
            $uId = func_get_arg(0);
            $curDate = func_get_arg(1);
            $subDate = func_get_arg(2);

            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->where('user_id=?', $uId);

            if ($subDate != -1):
                $select->where('transaction_date<=?', $curDate)
                        ->where('transaction_date>=?', $subDate);
            elseif ($subDate == -1):
                $select->where('transaction_date<=?', $curDate);
            endif;

            $result = $this->getAdapter()->fetchAll($select);
            if ($result):
                return $result;
            endif;
        else:
            throw new Exception("Argument Not Provided");
        endif;
    }

    public function winningTransactions() {
        $days = Date('Y-m-d', strtotime('-1 week'));
        try {
            $select = $this->select()
                    ->where('user_id!=4') //should not be admin
                    ->where('request_type=5')
                    ->where('transaction_date>=?', $days);
            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        } catch (Exception $e) {
            throw new Exception('Unable To Insert Exception Occured :' . $e);
        }
    }

    public function getIpnTransaction() {
        $confCode = func_get_arg(0);
        try {
            $select = $this->select()
                    ->where('confirmation_code=?', $confCode);
            $result = $this->getAdapter()->fetchRow($select);

            if ($result) {
                return $result;
            }
        } catch (Exception $e) {
            throw new Exception('Unable To Insert Exception Occured :' . $e);
        }
    }
	
	
	public function getTransactionDetailById() {
        $transaction_id = func_get_arg(0);
        try {
            $select = $this->select()
                    ->where('transaction_id=?', $transaction_id);
            $result = $this->getAdapter()->fetchRow($select);

            if ($result) {
                return $result;
            }
        } catch (Exception $e) {
            throw new Exception('Unable To Insert Exception Occured :' . $e);
        }
    }
	
	
    public function updateTransaction() {
        if (func_num_args() > 0):
            $trxId = func_get_arg(0);
            $data = func_get_arg(1);
            if ($data):
                $this->update($data, 'transaction_id = "' . $trxId . '"');
            endif;
        endif;
    }

    public function getUserDepositTransaction() {
        $userId = func_get_arg(0);
        try {
            $select = $this->select()
                    ->where('request_type=7')
                    ->where('status=1')
                    ->where('user_id=?', $userId);
            $result = $this->getAdapter()->fetchAll($select);

            if ($result) {
                return $result;
            }
        } catch (Exception $e) {
            throw new Exception('Unable To Insert Exception Occured :' . $e);
        }
    }

	/* by prince for transaction action */

	public function getWinTransactionDetailByUserId() {
		$userId = func_get_arg(0);
		try {
			$select = $this->select()
					->where('user_id!=4')
					->where('request_type=5')
					->where('user_id=?', $userId);
			$result = $this->getAdapter()->fetchAll($select);

			if ($result) {
				return $result;
			}
		} catch (Exception $e) {
			throw new Exception('Unable To Insert Exception Occured :' . $e);
		}
	}

	public function getEntryTxnByUserId() {
		$userId = func_get_arg(0);
		try {
			$select = $this->select()
					->where('user_id!=4')
					->where('request_type=6 OR request_type=4')
					->where('user_id=?', $userId);
			$result = $this->getAdapter()->fetchAll($select);

			if ($result) {
				return $result;
			}
		} catch (Exception $e) {
			throw new Exception('Unable To Insert Exception Occured :' . $e);
		}
	}

	public function getWithdrawalsTxnByUserId() {
		$userId = func_get_arg(0);
		try {
			$select = $this->select()
					->where('user_id!=4')
					->where('request_type=2')
					->where('user_id=?', $userId);
			$result = $this->getAdapter()->fetchAll($select);

			if ($result) {
				return $result;
			}
		} catch (Exception $e) {
			throw new Exception('Unable To Insert Exception Occured :' . $e);
		}
	}

	public function getDepositsTxnByUserId() {
		$userId = func_get_arg(0);
		try {
			$select = $this->select()
					->where('user_id!=4')
					->where('request_type=1')
					->where('user_id=?', $userId);
			$result = $this->getAdapter()->fetchAll($select);

			if ($result) {
				return $result;
			}
		} catch (Exception $e) {
			throw new Exception('Unable To Insert Exception Occured :' . $e);
		}
	}

	public function getBonusTxnByUserId() {
		$userId = func_get_arg(0);
		try {
			$select = $this->select()
					->where('user_id!=4')
					->where('request_type=3')
					->where('user_id=?', $userId);
			$result = $this->getAdapter()->fetchAll($select);

			if ($result) {
				return $result;
			}
		} catch (Exception $e) {
			throw new Exception('Unable To Insert Exception Occured :' . $e);
		}
	}

}
?>
