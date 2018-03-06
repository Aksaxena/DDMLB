<?php

/**
 * StoreController
 *
 * @author  : Vivek Chaudhari
 * @version
 */
require_once 'Zend/Controller/Action.php';

class Admin_StoreController extends Zend_Controller_Action {

    public function init() {
        
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : get product details and create new product action
     * @params      : <int>form data to create new product
     */
    public function storeAction() {
        $objStoreModel = Admin_Model_Store::getInstance();
        if ($this->getRequest()->isPost()):
            $data = array();
            $data['product_name'] = $this->getRequest()->getParam('product_name');
            $data['url'] = $this->getRequest()->getParam('url');
            $data['fpp_point'] = $this->getRequest()->getParam('fpp_point');
            $data['real_cash'] = $this->getRequest()->getParam('real_cash');
            $data['qty'] = $this->getRequest()->getParam('qty');
            $ok = $objStoreModel->insertNewProduct($data);
        endif;
        $store = $objStoreModel->getStoreDetails();
        if ($store):
            $this->view->success = $store;
        endif;
        if ($store):
            $this->view->store = $store;

        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : edit product details, and get product details by id for editing
     * @params      : <int>form data of editing product
     */
    public function editProductAction() {
        $productId = $this->getRequest()->getParam('productId');
        $objStoreModel = Admin_Model_Store::getInstance();

        if ($this->getRequest()->isPost()):
            $edit = array();
            $edit['product_name'] = $this->getRequest()->getParam('product_name');
            $edit['url'] = $this->getRequest()->getParam('url');
            $edit['fpp_point'] = $this->getRequest()->getParam('fpp_point');
            $edit['real_cash'] = $this->getRequest()->getParam('real_cash');
            $edit['qty'] = $this->getRequest()->getParam('qty');
            $check = $objStoreModel->updateProductById($productId, $edit);
            if ($check):

                $this->_redirect('/admin/store');

                $this->view->error = "Product details edited successfully";
            else:
                $this->view->error = "Unable to edit product, some error occurred";
            endif;
        endif;

        $product = $objStoreModel->getProductDetailsById($productId);
        if ($product):
            $this->view->data = $product;
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : get ticket details and generate new tickets
     * @params      : <int>&<string>form data to generate new tickets
     */
    public function validTicketsAction() {
        $objTicketModel = Admin_Model_Tickets::getInstance();
        $tickets = $objTicketModel->getActiveTickets();
//            echo "<pre>"; print_r($tickets); echo "</pre>"; die;
        if ($tickets):
            $this->view->tickets = $tickets;
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : edit ticket details
     * @params      : <int><string>form data to edit tickets
     */
    public function editTicketAction() {
        $ticketId = $this->getRequest()->getParam('ticketId');
        $objTicketModel = Admin_Model_Tickets::getInstance();
        if ($this->getRequest()->isPost()) {
            $edit = array();
            $edit['valid_from'] = date('y-m-d', strtotime($this->getRequest()->getParam('valid_from')));
            $edit['valid_upto'] = date('y-m-d', strtotime($this->getRequest()->getParam('valid_upto')));

            $edit['status'] = $this->getRequest()->getParam('status');
            $edit['selling_status'] = $this->getRequest()->getParam('selling');
            $edit['fpp'] = $this->getRequest()->getParam('fpp');

            //  echo "<pre>"; print_r($ticketId); echo "</pre>"; die;

            $ok = $objTicketModel->updateTicketById($ticketId, $edit);
            if ($ok)
                $this->view->success = $ok;
            //   header('Location: /admin/ticket');
        }



        $data = $objTicketModel->getTicketDetailsById($ticketId);
        if ($data) {
            $this->view->data = $data;
        }
    }

    public function ticketHandlerAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $objTicketModel = Admin_Model_Tickets::getInstance();
        $ajaxMethod = $this->getRequest()->getParam('ajaxMethod');
//        echo $ajaxMethod;die('test');
        if ($ajaxMethod) {

            switch ($ajaxMethod) {
                case 'ticketverification':
                    $ticketcode = trim($this->getRequest()->getParam('ticket_code'));
                    $response = $objTicketModel->checkticketcode($ticketcode);
                    if ($response) {
                        $arr = array(" ticket code already exists,Generate other code ");
                        echo json_encode($arr);
                    } else {
                        echo json_encode(true);
                    }

                    break;
            }
        }
    }

    public function newTicketAction() {
        $objContestModel = Admin_Model_Contests::getInstance();
        $objTicketModel = Admin_Model_Tickets::getInstance();

//      echo "<pre>"; print_r($contestData); echo "</pre>"; die;
        if ($this->getRequest()->isPost()) {
            $data = array();
            // echo "<pre>"; print_r($this->getRequest()->getParams()); echo "</pre>"; die;
            $contestData = $this->getRequest()->getParam('contest');
            $con = explode("@", $contestData);
            $contestID = $con[0];
            $contestName = $con[1];
            $data['contest_id'] = $contestID;
            $data['description'] = "Ticket valid for " . $contestName . " Contest!";
            $data['code'] = $this->getRequest()->getParam('ticket_code');
            $data['valid_from'] = date('Y-m-d', strtotime($this->getRequest()->getParam('valid_from')));
            $data['valid_upto'] = date('Y-m-d', strtotime($this->getRequest()->getParam('valid_upto')));
            $data['fpp'] = $this->getRequest()->getParam('fpp');
            $data['user_limit'] = $this->getRequest()->getParam('userlimit');
            $data['ticket_use_limit'] = $this->getRequest()->getParam('ticketlimit');
            $data['selling_status'] = $this->getRequest()->getParam('selling');

//        
            $ticketID = $objTicketModel->uploadNewTicket($data);

            if ($ticketID) {
                $contestUpdate['ticket_id'] = $ticketID;
                $objContestModel->updateContestDetails($contestID, $contestUpdate);
                $this->view->success = $objContestModel;
            }
        }
        $contestData = $objContestModel->getContestForTicket();
        if ($contestData) {
            $this->view->contests = $contestData;
        }
    }

}
