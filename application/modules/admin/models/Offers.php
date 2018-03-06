<?php

class Admin_Model_Offers extends Zend_Db_Table_Abstract
{    
    private static $_instance = null;
    protected $_name = 'offers';    
    
    private function  __clone() { } //Prevent any copy of this object
	
    public static function getInstance(){
		if( !is_object(self::$_instance) )  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
		self::$_instance = new Admin_Model_Offers();
		return self::$_instance;
    }
    /**
     * Developer    : vivek Chaudhari
     * Date         : 17/06/2014
     * Description  : get all upcoming contest names
     */
    public function getContestsNames(){
        $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('c'=>'contests'),array('c.contest_id','c.contest_name'))
                    ->where('status = ?', 1)
                    ->where('con_status = ?', 0)
                    ->order('contest_id DESC'); 
//        echo $select; die;
        $result = $this->getAdapter()->fetchAll($select);            
            if ($result) :
                return $result;
            endif;
    }

    /**
     * Developer    : vivek Chaudhari
     * Date         : 17/06/2014
     * Description  : get offer details
     */
    public function getOfferDetails() {
            $select = $this->select()
                    ->from($this);            
            $result = $this->getAdapter()->fetchAll($select);    
            if ($result) :
                return $result;
            endif;     
   
    }
    
    /**
     * Developer    : vivek Chaudhari
     * Date         : 17/06/2014
     * Description  : insert new offer in db
     */
    public function insertOfferDetails(){
        if(func_num_args()>0):
            $data = func_get_arg(0);
            if($data):
               $result = $this->insert($data);
            return $result;
            endif;
        else:
            throw new Exception("Argument not passed");
        endif;
        }
   
    /**
     * Developer    : vivek Chaudhari
     * Date         : 17/06/2014
     * Description  : search Get all offer details
     */
    public function offerActive() {
        if (func_num_args() > 0):
            $oid = func_get_arg(0);
            try {
                $result = 0;
                $check = $this->select()->where('status=?','1');
                $count = $this->getAdapter()->fetchAll($check);
//                print_r($count);die;
               if(count($count)<3){
                    $data = array('status' => '1');
                    $result = $this->update($data, 'offer_id = "' . $oid . '"');
                }
               
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
     * Date         : 17/06/2014
     * Description  : search Get all offer details
     */
    public function offerDeactive() {
        if (func_num_args() > 0):
            $oid = func_get_arg(0);
            try {
                $data = array('status' => '0');
                $result = $this->update($data, 'offer_id = "' . $oid . '"');
            } catch (Exception $e) {
                throw new Exception($e);
            }
            if ($result) :
                return $result;
            else :
                return 0;
            endif;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
    
     /**
     * Developer    : vivek Chaudhari
     * Date         : 17/06/2014
     * Description  : delete offer
     */
   public function deleteOffer(){
      
       if(func_num_args()>0){
           $id = func_get_args(0);
           try 
           {
               $db = Zend_Db_Table::getDefaultAdapter();
               $where = (array('offer_id=?'=>$id));
               $db->delete('offers',$where);
           }  catch (Exception $e){
               throw new Exception($e);
           }
           return $id;
       }
        else{
            throw new Exception('Argument not passed');
        }
       
       
   }
   
   /**
     * Developer    : vivek Chaudhari
     * Date         : 17/06/2014
     * Description  : get image name
     */
   public function getImageName(){
       if(func_num_args()>0){
           $offerId = func_get_arg(0);
           try{
               $select = $this->select()
                       ->from(array('o'=>'offers'),array('o.image_url'))
                       ->where('offer_id=?',$offerId);
              $name = $this->fetchRow($select);
               if($name){
                   return $name;
               }
            }catch(Exception $e){
                throw new Exception ($e);
            }
       }else{
           throw new Exception("Argument not passed");
       }
   }
   
   /**
     * Developer    : vivek Chaudhari
     * Date         : 17/06/2014
     * Description  : get offer details for edits
     */
   public function getOfferDetailsById(){
       if(func_num_args()>0):
           $offerId = func_get_arg(0);
            $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('o'=>'offers'),array('o.offer_id','o.offer_name','o.offer_type','o.offer_end_date','o.description','o.image_url'))
                    ->where('offer_id=?',$offerId);            
            $result = $this->getAdapter()->fetchRow($select);       
//           echo "<pre>"; print_r($result); echo "</pre>"; die;
            if ($result) :
                return $result;
            endif;
       endif;
   }
   /**
     * Developer    : vivek Chaudhari
     * Date         : 17/06/2014
     * Description  : update offer details
     */
   public function updateOffer(){
       if(func_num_args()>0):
           $offerId = func_get_arg(0);
           $data = func_get_arg(1);
           if($data):
               $check = $this->update($data,'offer_id = "' . $offerId . '"');
           /* chandra sekhar reddy  date:02/09/2014 description: to solve bug number 431  */
           return $check;
           endif;
       endif;
   }
   
    /**
     * Developer : Bhojraj Rawte
     * Date : 29/07/2014
     * Description : count Offer
     */
     public function getTotalOffer() {

        $select = $this->select()
                ->from($this, array("Totaloffer" => "COUNT(*)"));

        $result = $this->getAdapter()->fetchRow($select);
        if ($result) {
            return $result['Totaloffer'];
        } else {
            return false;
        }
    }    
   
}

?>
