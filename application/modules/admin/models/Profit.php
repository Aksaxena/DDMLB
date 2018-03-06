<?php

//vivek chaudhari(01/11/2014)
class Admin_Model_Profit extends Zend_Db_Table_Abstract {

    private static $_instance = null;
    protected $_name = 'profit';

    private function __clone() {
        
    }

//Prevent any copy of this object

    public static function getInstance() {
        if (!is_object(self::$_instance))  //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new Admin_Model_Profit();
        return self::$_instance;
    }

    public function isertProfit() {
        if (func_num_args() > 0) {
            $data = func_get_arg(0);
            try {
                $this->insert($data);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        } else {
            throw new Exception("Argument not passed");
        }
    }

    /**
     * Developer : Bhojraj Rawte
     * Date : 11/01/2014
     * Description : Show admin profit statics     
     */
    public function adminProfitStatics() {
        if (func_num_args() > 0) {
            $year = func_get_arg(0);
            $select = "SELECT SUM(p.amount) AS total, m.month
                FROM (
                      SELECT 'JAN' AS MONTH
                      UNION SELECT 'FEB' AS MONTH
                      UNION SELECT 'MAR' AS MONTH
                      UNION SELECT 'APR' AS MONTH
                      UNION SELECT 'MAY' AS MONTH
                      UNION SELECT 'JUN' AS MONTH
                      UNION SELECT 'JUL' AS MONTH
                      UNION SELECT 'AUG' AS MONTH
                      UNION SELECT 'SEP' AS MONTH
                      UNION SELECT 'OCT' AS MONTH
                      UNION SELECT 'NOV' AS MONTH
                      UNION SELECT 'DEC' AS MONTH
                     ) AS m
               LEFT JOIN profit p ON MONTH(STR_TO_DATE(CONCAT(m.month, '$year'),'%M %Y')) = MONTH(p.date) AND YEAR(p.date) = '$year'
               GROUP BY m.month
               ORDER BY 1+1";

            $result = $this->getAdapter()->fetchAll($select);
            return $result;
        }
    }
    
    /**
     * Developer : Bhojraj Rawte
     * Date : 11/01/2014
     * Description : Show admin profit statics     
     */
    public function getProfitStaticsByMonth() {
        if (func_num_args() > 0) {
            $year = func_get_arg(0);
            $month = func_get_arg(1);
            
            $select = "SELECT amount,DATE_FORMAT(date, '%d-%m-%Y') as date  , SUM(amount) AS total
                        FROM profit
                        WHERE MONTH(date) = $month AND YEAR(date) = $year
                        GROUP BY date ORDER BY date";

           
            $result = $this->getAdapter()->fetchAll($select);           
            
            return $result;
        }
    }    

}