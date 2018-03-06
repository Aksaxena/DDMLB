<?php
/**
 * NewsController
 *
 * @author
 * @version
 */
require_once 'Zend/Controller/Action.php';

class News_NewsController extends Zend_Controller_Action {



    public function init() {

    }
        /**
         * Developer   : Vivek Chaudhari
         * date        : 01/07/2014 
         * Description : parse rss feeds and show in news
         */
    public function newsFeedAction(){
      $url = 'http://mlb.mlb.com/partnerxml/gen/news/rss/mlb.xml';
      $client = new Zend_Http_Client($url);
                    $response = $client->request();
                    $data = simplexml_load_string($response->getBody()); //echo "<pre>"; print_r($data); echo "</pre>"; die;
                    $parseData = array();
                    $eachData = array();
                    foreach($data->channel->item as $value):
                        $eachData['title'] = (string)$value->title;
                        $eachData['link'] = (string)$value->link;
                        $eachData['pubDate'] = (string)$value->pubDate;
                        $eachData['guid'] = (string)$value->guid;
                        $eachData['description'] = (string)$value->description;
                        array_push($parseData, $eachData);
                    endforeach;
                    
                    if($parseData):
                        $this->view->parsedata = $parseData;
                       
                    endif;
                  
    }
   

    
}
