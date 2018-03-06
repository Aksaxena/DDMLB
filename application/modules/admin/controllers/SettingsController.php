<?php

/**
 * AdminController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';
require_once '../vendor/autoload.php';

class Admin_SettingsController extends Zend_Controller_Action {

    public function init() {
        
    }

    
	 /**
     * Developer : Alok Saxena
     * Date : 29/07/2017
     * Description : It will handle btc address action
     * Action : btcAddress
     */
	 
	public function btcAddressAction() {
		$objSettingsModel = Admin_Model_Settings::getInstance();
		
		if ($this->getRequest()->isPost()) {
			//echo "<pre>"; print_r($this->getRequest()->getPost());
			$sid = $this->getRequest()->getPost('setting_id');
			$data['Btc_Address'] = $this->getRequest()->getPost('Btc_Address');
			$result = $objSettingsModel->updateSettingsDeatils($data, $sid);
			$this->view->success = 1;
        }
		
		$settings = $objSettingsModel->getSettingsDeatils();
        $this->view->Btc_Address = $settings['Btc_Address'];
        $this->view->setting_id = $settings['setting_id'];
	}
	
	/**
     * Developer : Bhojraj Rawte
     * Date : 10/03/2014
     * Description : Show dashboard
     */
    public function dashboardAction() {
        $objUserModel = Admin_Model_Users::getInstance();
        $objContestModel = Admin_Model_Contests::getInstance();
        $objPromotionModel = Admin_Model_Promotions::getInstance();
        $objTicketModel = Admin_Model_Tickets::getInstance();
        $objSportsModel = Admin_Model_Sports::getInstance();
        $objOfferModel = Admin_Model_Offers::getInstance();
        $objProfitModel = Admin_Model_Profit::getInstance();

        $totalUser = $objUserModel->getTotalUser();
        $totalContest = $objContestModel->getTotalContest();
        $totalPromotion = $objPromotionModel->getTotalPromotion();
        $totalTicket = $objTicketModel->getTotalTickets();
        $totalSports = $objSportsModel->getTotalSports();
        $totalOffer = $objOfferModel->getTotalOffer();

        $currentYear = date("Y");
        $currentProfitYear = date("Y");
        if ($this->getRequest()->getParam('year')) {
            $currentYear = $this->getRequest()->getParam('year');
        }
        if ($this->getRequest()->getParam('profit')) {
            $currentProfitYear = $this->getRequest()->getParam('profit');
        }
        $userStatics = $objUserModel->userStatics($currentYear);

        foreach ($userStatics as $data) {
			switch ($data['month']){
				case "DEC" :
					$staticData[11]=$data['total'];
					break;
				case "NOV" :
					$staticData[10]=$data['total'];
					break;
				case "OCT" :
					$staticData[9]=$data['total'];
					break;
				case "SEP" :
					$staticData[8]=$data['total'];
					break;
				case "AUG" :
					$staticData[7]=$data['total'];
					break;
				case "JUL" :
					$staticData[6]=$data['total'];
					break;
				case "JUN" :
					$staticData[5]=$data['total'];
					break;
				case "MAY" :
					$staticData[4]=$data['total'];
					break;
				case "APR" :
					$staticData[3]=$data['total'];
					break;
				case "MAR" :
					$staticData[2]=$data['total'];
					break;
				case "FEB" :
					$staticData[1]=$data['total'];
					break;
				case "JAN" :
					$staticData[0]=$data['total'];
					break;
			} 
        }
		ksort($staticData);
        $profitStatics = $objProfitModel->adminProfitStatics($currentProfitYear);
		foreach ($profitStatics as $rec) {
			switch ($rec['month']){
				case "DEC" :
					$profitStat[11]['total']=$rec['total'];
					break;
				case "NOV" :
					$profitStat[10]['total']=$rec['total'];
					break;
				case "OCT" :
					$profitStat[9]['total']=$rec['total'];
					break;
				case "SEP" :
					$profitStat[8]['total']=$rec['total'];
					break;
				case "AUG" :
					$profitStat[7]['total']=$rec['total'];
					break;
				case "JUL" :
					$profitStat[6]['total']=$rec['total'];
					break;
				case "JUN" :
					$profitStat[5]['total']=$rec['total'];
					break;
				case "MAY" :
					$profitStat[4]['total']=$rec['total'];
					break;
				case "APR" :
					$profitStat[3]['total']=$rec['total'];
					break;
				case "MAR" :
					$profitStat[2]['total']=$rec['total'];
					break;
				case "FEB" :
					$profitStat[1]['total']=$rec['total'];
					break;
				case "JAN" :
					$profitStat[0]['total']=$rec['total'];
					break;
			} 
        }
		$profitStatics = $profitStat;
		ksort($profitStatics);
       // echo "<pre>"; print_r($profitStatics); echo "</pre>"; die;
        $this->view->year = $currentYear;
        $this->view->profityear = $currentProfitYear;
        $this->view->userstatic = $staticData;
        $this->view->profitstatic = $profitStatics;

        if ($totalUser) {
            $this->view->totalusers = $totalUser;
        } else {
            $this->view->totalusers = 0;
        }

        if ($totalContest) {
            $this->view->totalcontets = $totalContest;
        } else {
            $this->view->totalcontets = 0;
        }


        if ($totalPromotion) {
            $this->view->totalpromotion = $totalPromotion;
        } else {
            $this->view->totalpromotion = 0;
        }


        if ($totalTicket) {
            $this->view->totalticket = $totalTicket;
        } else {
            $this->view->totalticket = 0;
        }


        if ($totalSports) {
            $this->view->totalsports = $totalSports;
        } else {
            $this->view->totalsports = 0;
        }

        if ($totalOffer) {
            $this->view->totaloffer = $totalOffer;
        } else {
            $this->view->totaloffer = 0;
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 08/03/2014
     * Description : Show all themes details
     */
    public function themesAction() {
        $objthemesModel = Admin_Model_Themes::getInstance();
        $themesDetails = $objthemesModel->getThemesDeatils();
//        if ($themesDetails):
//            $this->view->success = $themesDetails;
//        endif;
        //echo "<pre>"; print_r($themesDetails); echo "</pre>";die;
        if ($themesDetails) :
            $this->view->themes = $themesDetails;
        endif;


//------------------------ New Themes Upload ---------------------------------//
        if ($_FILES):
            $upload = new Zend_File_Transfer();
            $upload->addValidator('Extension', false, 'zip');
            $files = $upload->getFileInfo();
            $errorNotify = 0;
//echo "<pre>"; print_r($upload); echo "</pre>";
//echo "<pre>"; print_r($files); echo "</pre>";

            foreach ($files as $file => $info) :
                if (!$upload->isUploaded($file)) :

                    $errmsg = "Please select file to Upload!";
                    $errorNotify = 1;
                    continue;
                endif;

                if (!$upload->isValid($file)) :

                    $errmsg = "Invalid File extension. Please upload only *.zip file";
                    $errorNotify = 1;
                    continue;
                endif;
            endforeach;
            if ($errorNotify == 0) :
                $destination = getcwd() . '/Temp/' . $files['themezipped']['name'];
                $upload->addFilter('Rename', $destination);
                $upload->receive();

                $d = getcwd() . '/themes/';
                $zip = new ZipArchive;
                $zip->open($destination);

                for ($i = 0; $i < $zip->numFiles; $i++) :
                    $entry = $zip->getNameIndex($i);
                    $dir = str_replace('\\', '/', $entry);
                    $dir = array_filter(explode('/', $dir));
                    // check app folder existence 
                    if (in_array('app', $dir)): $app = 1;
                    endif;
                    // check skin folder existence 
                    if (in_array('skin', $dir)):$skin = 1;
                    endif;

                endfor;

                $dirpath = getcwd() . '/themes/' . $dir[0];

                if (!is_dir($dirpath)) :

                    if (isset($app) && isset($skin)) :


                        if (!$objThemesModel->checkThemeExist($dir[0])):

                            $zip->extractTo($d);
                            $theme = array();

                            if (is_file($dirpath . '/' . 'Resource.php')) :
                                $content = include $dirpath . '/' . 'Resource.php';
                                $content = $content['package'];
                                $thumbnailpath = "";
                                $title = "";
                                $description = "";

                                if (isset($content['thumbnailpath'])) :
                                    $thumbnailpath = $content['thumbnailpath'];
                                endif;
                                if (isset($content['title'])) :
                                    $title = $content['title'];
                                endif;
                                if (isset($content['description'])) :
                                    $description = $content['description'];
                                endif;
                                $theme['thumbnailpath'] = $thumbnailpath;
                                $theme['title'] = $title;
                                $theme['description'] = $description;
                            endif;

                            $theme['name'] = $dir[0];

                            $objThemesModel->insertTheme($theme);

                            $this->view->SuccessMsg = 'Theme Installed';
                            $this->view->success = '1';

                        else: $this->view->ErrorMsg = "Theme Already Exist!";
                        endif;

                    else : $this->view->ErrorMsg = 'Theme is not compatible. Invalid or Missing Files!';
                    endif;

                else: $this->view->ErrorMsg = "Theme Already Exist!";
                endif;

                $zip->close();
                unlink($destination);

            else: $this->view->ErrorMsg = $errmsg;
            endif;

        endif;
//        var_dump($errorNotify);
//        echo $errmsg;
//---------------------------------End Themes Upload -------------------------//            
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 08/03/2014
     * Description : Handling all Js and Ajex request
     */
    public function jshandlerAction() {
        $this->_helper->_layout->disableLayout();
        $objThemesModel = Admin_Model_Themes::getInstance();
        $objUserModel = Admin_Model_Users::getInstance();
        $objCountryModel = Admin_Model_Countries::getInstance();
        $objWithdrawalModel = Admin_Model_WithdrawalRequest::getInstance();
        $objContests = Admin_Model_Contests::getInstance();
        $objContestType = Admin_Model_ContestsType::getInstance();
        $objSportsModel = Admin_Model_Sports::getInstance();
        $objPromotions = Admin_Model_Promotions::getInstance();
        $objOffersModel = Admin_Model_Offers::getInstance();
        $objTicketModel = Admin_Model_Tickets::getInstance();
        $objStoreModel = Admin_Model_Store::getInstance();
        $objUserAccModel = Admin_Model_UserAccount::getInstance();
        $method = $this->_request->getParam('method');
        switch ($method) :

            /**
             * Developer : Bhojraj Rawte
             * Date : 08/03/2014 
             * Description : Themes Active
             */
            case 'themesactive':
                $themeid = $this->_request->getParam('themesid');
                $ok = $objThemesModel->themeActive($themeid);
                $responce = new stdClass();
                if ($ok):
                    echo $themeid;
                    return $themeid;
                else :
                    echo "Error";
                endif;
                break;

            /**
             * Developer : Bhojraj Rawte
             * Date : 10/03/2014 
             * Description : User Active and Deactive
             * @Todo :  
             */
            case 'useractive':
                $userid = $this->_request->getParam('userid');
                $ok = $objUserModel->userActiveDeactive($userid);
                if ($ok):
                    echo $userid;
                    return $userid;
                else :
                    echo "Error";
                endif;
                break;

            /**
             * Developer : Ramanjineyulu G
             * Date : 02/07/2014 
             * Description : Approved the status
             * @Todo :  
             */
            case 'approvalactive':
                $wid = $this->_request->getParam('withdrawid');
                $ok = $objWithdrawalModel->getApprovalDeatils($wid);
                if ($ok):
                    echo $wid;
                    return $wid;
                else :
                    echo "Error";
                endif;
                break;

            /**
             * Developer : Bhojraj Rawte
             * Date : 10/03/2014 
             * Description : User Delete
             * @Todo :  
             */
            case 'userdelete':
                $userid = $this->_request->getParam('userid');
                $ok = $objUserModel->userDelete($userid);
                $objUserAccModel->deleteuserAccnt($userid);
                if ($ok) :
                    echo $userid;
                    return $userid;
                else:
                    echo "Error ";
                endif;
                break;

            /**
             * Developer : Bhojraj Rawte
             * Date : 10/03/2014 
             * Description : User Active and Deactive
             * @Todo :  
             */
            case 'countryactive':
                $country_id = $this->_request->getParam('country_id');
                $ok = $objCountryModel->countryActiveDeactive($country_id);
                if ($ok):
                    echo $country_id;
                    return $country_id;
                else :
                    echo "Error";
                endif;
                break;
            /**
             * Developer : Bhojraj Rawte
             * Date : 11/03/2014 
             * Description : Country Delete
             * @Todo :  
             */
            case 'countrydelete':
                $country_id = $this->_request->getParam('country_id');
                $ok = $objCountryModel->countryDelete($country_id);
                if ($ok) :
                    echo $country_id;
                    return $country_id;
                else:
                    echo "Error ";
            endif;
            /**
             * Developer : Bhojraj Rawte
             * Date : 12/03/2014 
             * Description : Withdrawal Approval
             * @Todo :  
             */
            case 'withdrawalApproval':
                $withdrawal_id = $this->_request->getParam('withdrawal_id');
                $ok = $objWithdrawalModel->getWithdrawalDeatilsById($withdrawal_id);
                echo "<pre>";
                print_r($ok);
                echo "</pre>";
                die;
                if ($ok) :
                    echo $withdrawal_id;
                    return $withdrawal_id;
                else:
                    echo "Error ";
                endif;

                break;

            /**
             * Developer : Bhojraj Rawte
             * Date : 14/05/2014 
             * Description : Contest Delete
             * @Todo :  
             */
            case 'contestdelete':
                $contestid = $this->_request->getParam('contestid');
                $ok = $objContests->contestsDelete($contestid);
                if ($ok) :
                    echo $contestid;
                    return $contestid;
                else:
                    echo "Error ";
                endif;
                break;

            /* Developer : Vini Dubey
             * Date : 06/08/2014
             * Description : Contest Update
             */

            case 'contestsupdate':
                $contestid = $this->_request->getParam('contestid');

                $ok = $objContests->contestsUpdate($contestid);
                if ($ok) :
                    echo $contestid;
                    return $contestid;
                else:
                    echo "Error ";
                endif;
                break;

            /**
             * Developer : Bhojraj Rawte
             * Date : 15/05/2014 
             * Description : Delete Contest Type
             * @Todo :  
             */
            case 'contestypedelete':
                $contestid = $this->_request->getParam('contestid');
                $ok = $objContestType->contestsTypeDelete($contestid);
                if ($ok) :
                    echo $contestid;
                    return $contestid;
                else:
                    echo "Error ";
                endif;
                break;

            /**
             * Developer : Bhojraj Rawte
             * Date : 15/05/2014 
             * Description : Delete Game
             * @Todo :  
             */
            case 'gamedelete':
                $gameid = $this->_request->getParam('gameid');
                $ok = $objSportsModel->sportsDelete($gameid);
                if ($ok) :
                    echo $contestid;
                    return $contestid;
                else:
                    echo "Error ";
                endif;
                break;

            /**
             * Developer : Bhojraj Rawte
             * Date : 15/05/2014 
             * Description : Game Active and Deactive
             * @Todo :  
             */
            case 'gameactive':
                $gameID = $this->_request->getParam('game_id');
                $ok = $objSportsModel->sportsActiveDeactive($gameID);
                if ($ok):
                    echo $gameID;
                    return $gameID;
                else :
                    echo "Error";
                endif;
                break;

            /**
             * Developer : Bhojraj Rawte
             * Date : 15/05/2014 
             * Description : Contest Active and Deactive
             * @Todo :  
             */
            case 'contestactive':
                $contestID = $this->_request->getParam('contest_id');
                $ok = $objContestType->contestActiveDeactive($contestID);
                if ($ok):
                    echo $contestID;
                    return $contestID;
                else :
                    echo "Error";
                endif;
                break;

            /**
             * Developer : Vivek Chaudhari
             * Date : 18/06/2014 
             * Description : Offer Active
             */
            case 'offeractive':
                $offerid = $this->_request->getParam('offersid');

                $ok = $objOffersModel->offerActive($offerid);
                if ($ok):
                    echo $offerid;
                    return $offerid;
                else :
                    echo 'Maximum limit for offer activation is three only';
                endif;
                break;
            /**
             * Developer : Vivek Chaudhari
             * Date : 18/06/2014 
             * Description : Offer Deactive
             */
            case 'offerdeactive':
                $offerid = $this->_request->getParam('offersid');

                $ok = $objOffersModel->offerDeactive($offerid);
                if ($ok):
                    echo $offerid;
                    return $offerid;
                else :
                    echo "Error";
                endif;
                break;
            /**
             * Developer : Vivek Chaudhari
             * Date : 24/06/2014 
             * Description : Offer delete
             */
            case 'deleteoffer':
                $offerId = $this->_request->getParam('offerId');
                $name = $objOffersModel->getImageName($offerId);
                $ok = $objOffersModel->deleteOffer($offerId);
                if ($ok) {

                    try {
                        $destination = getcwd() . '/assets/images/' . $name['image_url'];
                        $destination = str_replace('/', '\\', $destination);
                        unlink($destination);
                    } catch (Exception $e) {
                        throw new Exception($e);
                    }


                    echo $offerId;
                    return $offerId;
                } else {
                    echo "error";
                }
                break;

            case 'ticketactive':
                $ticketid = $this->_request->getParam('ticketid');

                $ok = $objTicketModel->ticketActive($ticketid);
                if ($ok):
                    echo $ticketid;
                    return $ticketid;
                else :
                    echo "Error";
                endif;
                break;

            case 'ticketdeactive':
                $ticketid = $this->_request->getParam('ticketid');

                $ok = $objTicketModel->ticketDeactive($ticketid);
                if ($ok):
                    echo $ticketid;
                    return $ticketid;
                else :
                    echo "Error";
                endif;
                break;

            case 'deleteticket':
                $ticketid = $this->_request->getParam('ticketid');
                $ok = $objTicketModel->ticketDelete($ticketid);
                if ($ok) {
                    echo $ticketid;
                    return $ticketid;
                } else {
                    echo "error";
                }
                break;

            case 'productactive':
                $productid = $this->_request->getParam('productid');
                $ok = $objStoreModel->productActive($productid);
                if ($ok):
                    echo $productid;
                    return $productid;
                else :
                    echo "Error";
                endif;
                break;

            case 'productdeactive':
                $productid = $this->_request->getParam('productid');
                $ok = $objStoreModel->productDeactive($productid);
                if ($ok):
                    echo $productid;
                    return $productid;
                else :
                    echo "Error";
                endif;
                break;

            case 'deleteproduct':
                $productid = $this->_request->getParam('productid');
                $ok = $objStoreModel->productDelete($productid);
                if ($ok) {
                    echo $productid;
                    return $productid;
                } else {
                    echo "error";
                }
                break;

            /*
             * Name: Abhinish Kumar Singh
             * Date: 16/07/2014
             * Description: This is used to activate/deactivate contest promotions
             */
            case 'promotionactive':
                $promoteid = $this->_request->getParam('promote_id');
                $ok = $objPromotions->promotionActiveDeactive($promoteid);
                if ($ok):
                    $data['resp'] = $promoteid;
                    echo json_encode($data);
                    return;
                else :
                    echo "Error";
                endif;
                break;

            /*
             * Name: Abhinish Kumar Singh
             * Date: 16/07/2014
             * Description: This is used to delete any contest promotion
             */
            case 'deletepromotion':
                $promoteid = $this->_request->getParam('promote_id');
                $ok = $objPromotions->promotionsDelete($promoteid);
                if ($ok) {
                    $data['resp'] = $promoteid;
                    echo json_encode($data);
                    return;
                } else {
                    echo "error";
                }
                break;

            /*
             * Name: Abhinish Kumar Singh
             * Date: 16/07/2014
             * Description: This is used to check uniqueness of contest
             *              promotion display name
             */
            case 'checkdispname':
                $promotename = $this->_request->getParam('promote_name');
                $ok = $objPromotions->getPromotionsDetailsByDisplayName($promotename);
                if ($ok) {
                    $data['resp'] = 0;
                } else {
                    $data['resp'] = 1;
                }
                echo json_encode($data);
                return;
                break;

            case 'detetefeatured':
                $contestid = $this->_request->getParam('contestid');
                $ok = $objContests->contestsUpdate($contestid);
                if ($ok) :
                    echo $contestid;
                    return $contestid;
                else:
                    echo "Error ";
                endif;
                break;

        endswitch;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 19/03/2014
     * Description : Get all country details
     */
    public function countriesAction() {
        $objCountriesModel = Admin_Model_Countries::getInstance();
        $countriesDetails = $objCountriesModel->getCountries();
        if ($countriesDetails) :
            $this->view->countries = $countriesDetails;

        endif;
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 19/03/2014
     * Description : edit country details
     */
    public function editCountryAction() {

        $objCountryModel = Admin_Model_Countries::getInstance();
        $country_id = $this->getRequest()->getParam('cid');


        if ($this->getRequest()->isPost()) :
            $country_data = array();
            $country_data['country_code'] = $this->getRequest()->getPost('country_code');
            $country_data['country_name'] = $this->getRequest()->getPost('country_name');
            $country_data['status'] = $this->getRequest()->getPost('status');

            $countryDet = $objCountryModel->updateCountryDetails($country_id, $country_data);
            $country_id = $this->getRequest()->getParam('cid');
            $this->view->success = 1;
        endif;

        $country = $objCountryModel->getCountryDeatilsByID($country_id);

        $this->view->country = $country;
        if ($countryDet) {
            $this->view->successnew = $countryDet;
            // $this->_redirect('/admin/countries');
        }
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 17/06/2014
     * Description  : search Get all offer details
     */
    public function offersAction() {
        $objOffersModel = Admin_Model_Offers::getInstance();


//------------------------ Offer image Upload ---------------------------------//
        if ($_FILES):
            $upload = new Zend_File_Transfer();
            $upload->addValidator('Extension', false, array('png', 'jpg', 'jpeg'));
            $files = $upload->getFileInfo();
            $errorNotify = 0;

            foreach ($files as $file => $info) :

                if (!$upload->isUploaded($file)) :

                    $errmsg = "Please select image file to Upload!";
                    $errorNotify = 1;
                    continue;
                endif;

                if (!$upload->isValid($file)) :

                    $errmsg = "Invalid File extension. Please upload only *.png or *.jpg file";
                    $errorNotify = 1;
                    continue;
                endif;
            endforeach;
//            print_r($errmsg); die;
            if ($errorNotify == 0) :
                $destination = getcwd() . '/assets/images/offers/';
                $destination = str_replace('\\', "/", $destination);
                $upload->setDestination($destination);
                $image_name = $files['imagefile']['name'];
                $upload->receive();
                $image_name = 'offers/' . $image_name;


            endif;

        endif;
//-----------------------end image upload---------------------------------------------------------------
//               Retrive the data after submit
        if (($this->getRequest()->isPost()) && ($errorNotify == 0)):
            $data['image_url'] = $image_name;
            $data['offer_name'] = $this->getRequest()->getParam('offer_name');
            $data['contest_id'] = $this->getRequest()->getParam('contest');
            $data['offer_end_date'] = date('y-m-d', strtotime($this->getRequest()->getParam('end_date')));
            $data['description'] = $this->getRequest()->getParam('description');
//echo "<pre>"; print_r($data); die;
            if ($data):
                $result = $objOffersModel->insertOfferDetails($data);
                if ($result) {
                    $this->view->success = $result;
                }
            endif;
        endif;
//               


        $offers = $objOffersModel->getOfferDetails();
//         if($offers){
//           $this->view->success = $offers;
//       }
        if ($offers) {
            $this->view->offer_details = $offers;
        }

        $contests = $objOffersModel->getContestsNames();
        if ($contests) {
            $this->view->contests = $contests;
        }
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 1/07/2014
     * Description  : offer
     */
    public function editOfferAction() {

        $offerId = $this->getRequest()->getParam('offerId');
        $objOffersModel = Admin_Model_Offers::getInstance();
        $edits = $objOffersModel->getOfferDetailsById($offerId);
        if ($edits):
            $this->view->edits = $edits;
        endif;
        if ($this->getRequest()->isPost()):
            $data = array();
            $data['offer_name'] = $this->getRequest()->getPost('offer_name');
            $data['offer_type'] = $this->getRequest()->getPost('offer_type');
            $data['description'] = $this->getRequest()->getPost('description');
            $data['image_url'] = $edits['image_url'];

//            print_r($data); die;
//------------------------ Offer image Upload ---------------------------------//
            if ($_FILES):
                $upload = new Zend_File_Transfer();
                $upload->addValidator('Extension', false, array('png', 'jpg'));
                $files = $upload->getFileInfo();



                $destination = getcwd() . '/assets/images/offers/';
                $destination = str_replace('\\', "/", $destination);
                $upload->setDestination($destination);
                $image_name = $files['imagefile']['name'];
                $image_name = 'offers/' . $image_name;
                $data['image_url'] = $image_name;

//                    Delete previous file
                if ($upload->receive()):
                    try {
                        $delete = getcwd() . '/assets/images/' . $edits['image_url'];
                        $delete = str_replace('\\', "/", $delete);
                        unlink($delete);
                    } catch (Exception $e) {
                        throw new Exception($e);
                    }

                endif;
//                    End delete
            endif;
//-----------------------end image upload---------------------------------------------------------------

            if ($data):
                $setdata = $objOffersModel->updateOffer($offerId, $data);
                // print_r($setdata);
                /* chandra sekhar reddy  date:02/09/2014 description: to solve bug number 431  */
                if ($setdata) {
                    $this->_redirect('/admin/offers');
                }
                $edits = $objOffersModel->getOfferDetailsById($offerId);
                if ($edits):
                    header('Location: /admin/offers');
                endif;
            endif;
        endif;
    }

    public function settingsAction() {
        $objSettingsModel = Admin_Model_Settings::getInstance();
		
        if ($this->getRequest()->isPost()):

            if ($_FILES) {

                $upload = new Zend_File_Transfer();
                $upload->addValidator('Extension', false, 'png', 'jpg', 'jpeg');
                $files = $upload->getFileInfo();
                $errorNotify = 0;
                foreach ($files as $file => $info) :
                    if (!$upload->isUploaded($file)) :
                        $errmsg = "Please select file to Upload!";
                        $errorNotify = 1;
                        continue;
                    endif;

                    if (!$upload->isValid($file)) :
                        $errmsg = "Invalid File extension. Please upload only *.zip file";
                        $errorNotify = 1;
                        continue;
                    endif;
                endforeach;
                if ($errorNotify == 0) {
                    $destination = getcwd() . '/assets/images/fb_post';
                    $destination = str_replace('\\', "/", $destination);
                    $upload->setDestination($destination);
                    $image_name = $files['fbimage']['name'];
                    $image_name = 'images/fb_post/' . $image_name;
                    if ($upload->receive()) {
                        $data['fb_img'] = $image_name;
                    }
                } else {
                    $this->view->errorimg = $errmsg;
                }
            }
            $sid = $this->getRequest()->getPost('setting_id');
            $data['payment_processor'] = $this->getRequest()->getPost('payment_processor');
            $data['salary_cap_amt'] = $this->getRequest()->getPost('salary_amount');
            $data['bonus_amt_limit'] = $this->getRequest()->getPost('bonus_amount');
            $data['currency_code'] = $this->getRequest()->getPost('currency_code');
            $data['min_deposit'] = $this->getRequest()->getPost('min_deposit');
            $data['contact_number'] = $this->getRequest()->getPost('contact_number');
            $data['email'] = $this->getRequest()->getPost('email');
            $data['mail_address'] = $this->getRequest()->getPost('mailing_address');

            $data['affilate_commission'] = $this->getRequest()->getPost('aff_commission');
            $data['bonus_status'] = $this->getRequest()->getPost('bonus_stats');
            $data['fb_desc'] = $this->getRequest()->getPost('fb_desc');
            $data['win_msg'] = $this->getRequest()->getPost('win_msg');
            $settings = $objSettingsModel->updateSettingsDeatils($data, $sid);
            if ($settings):
                $this->view->success = $settings;
            endif;
        endif;
		
		try {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, 'https://bitpay.com/api/rates/usd');

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$contents = curl_exec($ch);
			$Bit_data = json_decode($contents, TRUE);
			$this->view->BitToUsd = $Bit_data['rate'];
		} catch (Exception $e){
			
		}
		
        $settings = $objSettingsModel->getSettingsDeatils();
        $this->view->settings = $settings;
        
    }

    public function imageuploadAction() {
        $this->_helper->_layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
     * name:Sarika Nayak
     * date:1/08/2014
     * description:to allow bonus to selected countries
     */
    public function bonusCountryAction() {
        $objCountriesModel = Admin_Model_Countries::getInstance();
        if ($this->getRequest()->isPost()) {
            $list = $this->getRequest()->getPost('list');
            $action = $this->getRequest()->getPost('action');
            $result = $objCountriesModel->updateBonus($list, $action);

            if ($result) {
                $this->view->success = $result;
                //   $this->_redirect('admin/bonus-country');
            }
        }            
        $countriesDetails = $objCountriesModel->getCountries();
		//        echo "<pre>"; print_r($countriesDetails); echo "</pre>"; die;
        if ($countriesDetails) :
            $this->view->countries = $countriesDetails;
        endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 15/12/2014
     * Description  : distribute offer from admin panel
     */
    public function distributeOffersAction() {
		
        $objUserModelAdmin =  Admin_Model_Users::getInstance();
		$objEmaillog = Application_Model_Emaillog::getInstance();
        $objOffersMoidel = Application_Model_Offers::getInstance();
        $mailer = Engine_Mailer_Mailer::getInstance();
        $objUserModel = Application_Model_Users::getInstance();
        $objParser = Engine_Utilities_GameXmlParser::getInstance();
        $objContestModel = Application_Model_Contests::getInstance();
        $objCore = Engine_Core_Core::getInstance();
        $offers = $objOffersMoidel->getActiveOffer();
        $this->_appsettings = $objCore->getAppSetting();
		
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$postmark_config = $config->getOption('postmark');
		//echo "<pre>"; print_r($postmark_config); die;
		$client = new Postmark\PostmarkClient($postmark_config['key']);
		
        if ($this->getRequest()->isPost()) {
			
			// sendto = 1 for all users 2 for only for selected emails.
            $sentto = $this->getRequest()->getPost('sendto');
            $emails = $this->getRequest()->getPost('email');
			
            $offer = $this->getRequest()->getPost('offer');
            $offerDetails = array_values($objParser->filterArray($offer, $offers, 'offer_id'));
            
			$contestId = $offerDetails[0]['contest_id'];
            $contest = $objContestModel->getContestsDetailsById($contestId);
            
			$offeredUsers = array();
            $offeredUsers = json_decode($contest['offers_to'], true);
           
			if(!empty($sentto) && $sentto=="1"){
				$userDetails = $objUserModelAdmin->getUsersEmailsDeatils();
				foreach($userDetails as $user){
					$emailArr[] = $user['email'];
				}
			} else {
				$emailArr = explode(",", $emails);
			}
			//echo "<pre>"; print_r($emailArr); die;  
           	$template_name = 'distribute-offers';
            $username = $this->_appsettings->title . " Support";
            $subject = 'Offer Mail From Draftdaily';
            $message = "Support Provided an offer to play free in " . $contest['contest_name'] . ". This offer Valid upto " . $offerDetails[0]['offer_end_date'];
            
			$mergers = [
				"friendmessage" => $message,
				"companywebsitelink" => $this->_appsettings->hostLink . '/draftteam/' . $contest['contest_id'],
				"subject"=>$subject,
				"username"=>$username
			];
			
            if (isset($emailArr) && !empty($emailArr)) {
                $messages = array();
                foreach ($emailArr as $eval) {
                    $response = $objUserModel->validateUserEmail($eval);
                    if (isset($response) && !empty($response)) {
                        $userId = $response['user_id'];
                        $check = true;
                        if (isset($offeredUsers) && !empty($offeredUsers)) {
                            if ((array_search($userId, $offeredUsers) !== false)) {
                                $messages[] = "Offer Already sent to " . $eval;
                                $check = false;
                            }
                        }

                        if ($check) {
							
							$result = $client->sendEmailWithTemplate($postmark_config['email'],$eval,$postmark_config['offer_distribute'],$mergers);
							
                            //$result = $mailer->sendtemplate($template_name, $eval, $username, $subject, $mergers);

                            $insertdataemaillog = array(
                                'sent_email' => $eval,
                                'sent_time' => date('Y-m-d H:i:s'),
                                'sent_template' => $template_name,
                                'message' => $subject
                            );
                            $insertdataemaillog_result = $objEmaillog->insertEmailLog($insertdataemaillog);
                            if ($result->message=="OK" && isset($response['user_id'])) {
                                $offeredUserIds[] = $response['user_id'];
                                $messages[] = "Offer sent successfully to " . $eval;
                            }
                        }
                    } else {
                        $messages[] = "Offer cannot be sent to this Email !  " . $eval . ". Email is Not Registered. ";
                    }
                }
                if (isset($offeredUserIds) && !empty($offeredUserIds)) {
                    $contestUsers = array(); //array for contest offer_to containing userids
                    if ((isset($contest['offers_to'])) && ($contest['offers_to'] != "" || $contest['offers_to'] != null)) {
                        $decodeUsers = json_decode($contest['offers_to'], true);
                        array_push($contestUsers, array_values($decodeUsers), array_values($offeredUserIds));

                        $contestUsers = array_reduce($contestUsers, 'array_merge', array());
                    } else {
                        $contestUsers = $offeredUserIds;
                    }
                    $encOfferdUsers = json_encode($contestUsers, true);
                    $uData = array('offers_to' => $encOfferdUsers);
                    if (isset($encOfferdUsers)) {
                        $objContestModel->updateContestById($contestId, $uData);
                    }
                }
            }
            $this->view->messages = $messages;
        }
        if ($offers) {
            $this->view->offer = $offers;
        }
    }

}
