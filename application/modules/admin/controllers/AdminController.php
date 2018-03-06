<?php
/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class Admin_AdminController extends Zend_Controller_Action {

    public function init() {
        
    }

   /**
    * Developer : Bhojraj Rawte
    * Date : 10/03/2014
    * Description : Authenticate the admin.
    */      
    public function indexAction() {
        if (isset($this->view->session->storage->role)):
            if ($this->view->session->storage->role == '2'):
                $this->_redirect('admin/dashboard');
            endif;
        endif;

        $objSecurity = Engine_Vault_Security::getInstance();

        if ($this->_request->isPost()):
            $username = $this->getRequest()->getPost('username');
            $password = md5($this->getRequest()->getPost('password'));
            if (isset($username) && isset($password)):
                $authStatus = $objSecurity->authenticate($username, $password);
                if ($authStatus->code == 200):
                    if ($this->view->session->storage->role == '2'):
                        $this->_redirect('admin/dashboard');
                    endif;
                elseif ($authStatus->code == 198):
                    $this->view->error = "Invalid credentials";
                endif;
            endif;
        endif;
    }

    public function dashboardAction() {
        
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 09/07/2014
     * Description : Logout admin.
     * @Todo :  
     */
    public function logoutAction() {
        $this->_helper->layout->disableLayout();
        if ($this->view->auth->hasIdentity()) {

            $this->view->auth->clearIdentity();

            Zend_Session::destroy(true);

            $this->_redirect('/admin');
        }
    }

}
