<?php 
class Admin_Model_Store extends Zend_Db_Table_Abstract{
    
    private static $_instance = null;
    protected $_name = 'store_product';
    
    private  function __clone() {
        //avoid cloning
    }
            
    public static function getInstance(){
        if(!is_object(self::$_instance))
            self::$_instance = new Admin_Model_Store;
        return self::$_instance;
    }
    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : get store details
     * @params      : 
     */
    public function getStoreDetails(){
        $select = $this->select()
                ->setIntegrityCheck(false)
                ->from($this);
        $result = $this->getAdapter()->fetchAll($select);
        
        if($result):
            return $result;
        endif;
    }
    
    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : activate product
     * @params      : param1= product id
     */
    public function productActive() {
        if (func_num_args() > 0):
            $pid = func_get_arg(0);
            try {
                $data = array('status' => '1');
                $result = $this->update($data, 'product_id = "' . $pid . '"');
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
     * Date         : 11/07/2014
     * Description  : change status of product
     * @params      : param1= product id
     */
    public function productDeactive() {
        if (func_num_args() > 0):
            $pid = func_get_arg(0);
            try {
                $data = array('status' => '0');
                $result = $this->update($data, 'product_id = "' . $pid . '"');
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
     * Date         : 11/07/2014
     * Description  : delete product by id
     * @params      : param1= product Id
     */
     public function productDelete(){
        
        if (func_num_args() > 0):
            $pid = func_get_arg(0);
            try {
                $db = Zend_Db_Table::getDefaultAdapter();
                $where = (array('product_id = ?' => $pid));
                $db->delete('store_product', $where);
            } catch (Exception $e) {
                throw new Exception($e);
            }
            return $pid;
        else:
            throw new Exception('Argument Not Passed');
        endif;
    }
    
    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : get product details by id
     * @params      : param1= product Id
     */
    public function getProductDetailsById(){
        if(func_num_args()>0):
            $pid= func_get_arg(0);
             $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this,array('product_name','url','fpp_point','real_cash','qty'))
                    ->where('product_id=?',$pid);
             $result = $this->getAdapter()->fetchRow($select);
       
                if ($result) :
                    return $result;
                endif;
                else:
                    echo "Argument Not Passed";
        endif;
    }
    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : update edited data of product
     * @params      : param1= product Id param2 = edited data array
     */
    public function updateProductById(){
        if(func_num_args()>0):
            $productId = func_get_arg(0);
            $edit = func_get_arg(1);
            $where = array('product_id=',$productId);
            
            $check = $this->update($edit,'product_id="'.$productId.'"' );
            return  $check;
        else:
            throw new Exception("Argument not passed");
        endif;
    }
    
    /**
     * Developer    : vivek Chaudhari
     * Date         : 11/07/2014
     * Description  : add new product
     * @params      : 
     */
    public function insertNewProduct(){
        if(func_num_args()>0):
            $data = func_get_arg(0);
            $this->insert($data);
            else:
            throw new Exception('No argument Passed');
        endif;
    }
}
