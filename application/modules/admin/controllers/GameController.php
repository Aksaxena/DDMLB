<?php
/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class Admin_GameController extends Zend_Controller_Action {



    public function init() {     
        
    }

    public function gameDetailsAction() {          
   
    }

   /**
    * Developer : Bhojraj Rawte
    * Date : 25/03/2014
    * Description : Set new sports.
    */    
    public function newSportsAction(){
        $objSportsModel = Admin_Model_Sports::getInstance();
        if ($this->getRequest()->isPost()) :
            $data = array();
            $data['display_name'] = $this->getRequest()->getPost('sports_name');
            $data['status'] = $this->getRequest()->getPost('status');
            $SportsDetails = $objSportsModel->setSportsDetails($data);
             if($SportsDetails):
                $this->view->success = $SportsDetails;
            endif;
//            if($ok):
//                $this->view->msg = "Sport added Successfully";
//            else:
//                $this->view->msg = "Error Occured, Unable to insert this Sport";
           
//            print_r($ok); die;
//             endif;
        endif;    
       
    }

   /**
    * Developer : Bhojraj Rawte
    * Date : 25/03/2014
    * Description : Set new sports.
    */
    public function sportsDetailsAction(){
        $objSportsModel = Admin_Model_Sports::getInstance();
        if ($this->getRequest()->isPost()) :
            $data = array();
            $data['display_name'] = $this->getRequest()->getPost('sports_name');
            $data['status'] = $this->getRequest()->getPost('status');
            $objSportsModel->setSportsDetails($data);
        endif;        
        
}

   /**
    * Developer : Bhojraj Rawte
    * Date : 25/03/2014
    * Description : Get new Game details.
    */ 
    public function newGameDetailsAction(){
        $objSportsModel = Admin_Model_Sports::getInstance();
        $sportsDetails = $objSportsModel->getAllSportsDetails();
        if($sportsDetails):
        $this->view->sports = $sportsDetails;
        endif;
        //echo "<pre>"; print_r($result); echo "</pre>"; die;
        
    } 
    
   /**
    * Developer : Bhojraj Rawte
    * Date : 15/05/2014
    * Description : Edit Sports details
    */       
    public function editSportDetailsAction(){    
    $objSportsModel = Admin_Model_Sports::getInstance();
    $sportsID = $this->getRequest()->getParam('sid');
    $sportseDetails = $objSportsModel->getSportsDetailsById($sportsID);
    if($sportseDetails){
        $this->view->sportseDetails = $sportseDetails;
    }
    if ($this->getRequest()->isPost()) :
            $data = array();
            $data['display_name'] = $this->getRequest()->getPost('sports_name');
            $data['status'] = $this->getRequest()->getPost('status');
            $gameDetails=$objSportsModel->updateSportsDetails($sportsID,$data);
            $sportseDetails = $objSportsModel->getSportsDetailsById($sportsID);
            if($sportseDetails){
                $this->view->sportseDetails = $sportseDetails;
            }
            if($gameDetails){
                $this->view->success=$gameDetails;
              //  $this->_redirect('/admin/new-game-details');
                
            }
    endif;
    
    }    
    

    
}