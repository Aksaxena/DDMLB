<?php

class Application_Model_Users extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'users';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Application_Model_Users();
        return self::$_instance;
    }

  public function updateUser(){
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            $userId = func_get_arg(1);
            try {
                $update = $this->update($data, 'user_id =' . $userId);
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
            if ($update) {
                return $update;
            }
        } else {
            throw new Exception('Argument not passed');
        }
  }



    public function insertUser() {

        if (func_num_args() > 0) {
            $data = func_get_arg(0);
			
            try {
                $responseId = $this->insert($data);
				
            } catch (Exception $e) {
				
                return $e->getMessage();
            }

            if ($responseId) {
                return $responseId;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    public function validateUserName() {

        if (func_num_args() > 0) {
            $userName = func_get_arg(0);
            try {

                $select = $this->select()
                        ->from($this)
                        ->where('user_name = ?', $userName);

                $result = $this->getAdapter()->fetchAll($select);
            } catch (Exception $e) {
                throw new Exception('Unable To Insert Exception Occured :' . $e);
            }

            if ($result) {
                return $result;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    public function getUserDetailsByUserId($userId) {

        $select = $this->select()
                ->from(array('u' => 'users'), array('user_id', 'user_name', 'email','wallet_address'))
                ->setIntegrityCheck(false)
                ->joinLeft(array('ua' => 'user_account'), 'u.user_id = ua.user_id')
                ->where('u.user_id = ?', $userId);

        $result = $this->getAdapter()->fetchRow($select);

        if ($result) {
            return $result;
        }
    }

 public function validateTwitterUser() {

        if (func_num_args() > 0) {
            $userEmail = func_get_arg(0);
            try {

                $select = $this->select()
                        ->from($this)
                        ->where('t_oauth_uid = ?', $userEmail);

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
    public function validateUserEmail() {

        if (func_num_args() > 0) {
            $userEmail = func_get_arg(0);
            try {

                $select = $this->select()
                        ->from($this)
                        ->where('email = ?', $userEmail);

                $result = $this->getAdapter()->fetchRow($select);
                //   echo "<pre>"; print_r($result); echo "</pre>"; die;
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

    /*
     * Name: Chandra Sekhara Reddy
     * Date: 13/09/2014
     * Description: Getting ticket details by ticket id
     */

    public function getTicketDetailsTicketById($tickets) {
//       foreach ($tickets as $ticket_id){
        //echo"<pre>";print_r($tickets);echo"</pre>";die('test');
        $select = $this->select()
                ->from(array('t' => 'ticket_system'), array('t.code', 't.bonus_amt', 't.limitation', 't.valid_upto'))
                ->setIntegrityCheck(false)
                ->where('t.ticket_id IN (?)', $tickets);

        $result = $this->getAdapter()->fetchAll($select);
        // }
//       echo"<pre>";print_r($result);echo"</pre>";die('test');
        if ($result) {
            return $result;
        }
    }
	
	public function updateWalletAddress() {
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            $userId = func_get_arg(1);

            $data = array('wallet_address' => $data);
            try {
                $update = $this->update($data, 'user_id =' . $userId);
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
            if ($update) {
                return $update;
            }
        } else {
            throw new Exception('Argument not passed');
        }
    }
	
    public function updateActivationLink() {
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            $userId = func_get_arg(1);

            $data = array('activationLink' => $data);
            try {
                $update = $this->update($data, 'user_id =' . $userId);
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
            if ($update) {
                return $update;
            }
        } else {
            throw new Exception('Argument not passed');
        }
    }

    public function checkActivationKey() {
        if (func_num_args() > 0) {
            $userId = func_get_arg(0);
            $key = func_get_arg(1);

            try {
                $select = $this->select()
                        ->from($this)
                        ->where('user_id = ?', $userId)
                        ->where('activationLink = ?', $key);
                $result = $this->getAdapter()->fetchRow($select);
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
            if ($result) {
                return $result;
            }
        } else {
            throw new Exception('Argument not passed');
        }
    }

    public function changePassword() {
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            $userId = func_get_arg(1);

            $data = array('password' => $data);
            try {
                $update = $this->update($data, 'user_id =' . $userId);
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
            if ($update) {
                return $update;
            }
        } else {
            throw new Exception('Argument not passed');
        }
    }

    public function getUseridByName() {

        if (func_num_args() > 0) {
            $userName = func_get_arg(0);
            try {

                $select = $this->select()
                        ->from($this, array('user_id'))
                        ->where('user_name = ?', $userName);

                $result = $this->getAdapter()->fetchRow($select);
            } catch (Exception $e) {
                throw new Exception('Unable To Insert Exception Occured :' . $e);
            }

            if ($result) {
                return $result;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 06/12/2014
     * Description : Check FB user exist.
     */
    public function checkFBUserExist() {
        if (func_num_args() > 0) {
            $fbId = func_get_arg(0);
            $email = func_get_arg(1);
            try {
                $select = $this->select()
                        ->from($this)
                        ->where('fb_id = ?', $fbId)
                        ->orWhere('email = ?', $email);

                $result = $this->getAdapter()->fetchRow($select);
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
            if ($result) {
                return $result;
            }
        } else {
            throw new Exception('Argument not passed');
        }
    }

    public function updateFBID() {
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            $userId = func_get_arg(1);

            try {
                $update = $this->update($data, 'user_id =' . $userId);
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
            if ($update) {
                return $update;
            }
        } else {
            throw new Exception('Argument not passed');
        }
    }

    public function getuseridsbyemailids() {
        if (func_num_args() > 0) {
            $emailids = func_get_arg(0);
            foreach ($emailids as $value) {
                $select = $this->select()
                        ->from($this, array('user_id'))
                        ->where('email = ?', $value);
                $data[] = $this->getAdapter()->fetchRow($select);
            }
            if ($data) {
                return $data;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    public function getemailsbyuserid() {
        if (func_num_args() > 0) {
            $userds = func_get_arg(0);
            foreach ($userds as $value) {
                $select = $this->select()
                        ->from($this, array('email'))
                        ->where('user_id = ?', $value);
                $data[] = $this->getAdapter()->fetchRow($select);
            }
            if ($data) {
                return $data;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }

    public function getallemailsregisterd() {
        $select = $this->select()
                ->from($this, array('email', 'user_id'))
                ->where('role = 1');
        $res = $this->getAdapter()->fetchALL($select);
        if ($res) {
            return $res;
        }
    }

    public function getBotPlayers() {
        try {

            $select = $this->select()
                    ->from($this)
                    ->where('fb_pwd IS NOT NULL');

            $result = $this->getAdapter()->fetchAll($select);
        } catch (Exception $e) {
            throw new Exception('Unable To Insert Exception Occured :' . $e);
        }

        if ($result) {
            return $result;
        }
    }

    public function getDetailsByUserId() {
        if (func_num_args() > 0) {
            $userId = func_get_arg(0);
            try {
                $select = $this->select()
                        ->where('user_id = ?', $userId);
                $data = $this->getAdapter()->fetchRow($select);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
            if ($data) {
                return $data;
            }
        } else {
            throw new Exception('Argument Not Passed');
        }
    }
	
	public function updateEmailVerifyStatus() {
        if (func_num_args() > 0) {
            $userId = func_get_arg(0);

            $data = array('email_verify_status' => 1);
            try {
                $update = $this->update($data, 'user_id =' . $userId);
            } catch (Exception $exc) {
                throw new Exception('Unable to update, exception occured' . $exc);
            }
            if ($update) {
                return $update;
            }
        } else {
            throw new Exception('Argument not passed');
        }
    }
}