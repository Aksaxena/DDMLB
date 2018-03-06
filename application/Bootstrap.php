<?php
require_once 'Zend/Controller/Action.php';
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    private $_acl = null;
    private $_config = null;
    private $_logger = null;
   
	public function _construct(){		
	// add session value;
		
		$objUserAccModel = Application_Model_UserAccount::getInstance();
		$objTicketModel = Application_Model_TicketSystem::getInstance();
		$userId = $this->view->session->storage->user_id;
		$accData = $objUserAccModel->getAccountByUserId($userId);
		
		$tcount = 0;
		$allTktDetails = array();
		if (isset($accData['available_tickets']) && $accData['available_tickets'] != null) {
			$ticketIds = json_decode($accData['available_tickets'], true);
			 
			if (is_array($ticketIds)) {
				foreach ($ticketIds as $tkey => $tval) {
					$tData = $objTicketModel->getTicketDetailsById($tval);
					$allTktDetails[] = $tData;
					$tcount++;
				}
			}
		}
		
		$this->view->TotaluserTickets = $tcount;
		$this->view->TktDetails = $allTktDetails;
	}
    
	
	
	protected function _initLogs() {
		$this->_logger = new Zend_Log();
		$fireBugWriter = new Zend_Log_Writer_Firebug();
		$this->_logger->addWriter($fireBugWriter);
		Zend_Registry::set('logger', $this->_logger);
	}
    
   	protected function _initAppDefaults() {
		$this->_config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/appsettings.ini','appConfig');
		Zend_Registry::set('appconfig', $this->_config);
	    date_default_timezone_set('US/Eastern');

	}
        
        protected function _initMainRouters() {
	
		$this->bootstrap('frontController');
		$router = $this->getResource('frontController')->getRouter();

		$route_config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/routes.ini', 'routes');
		$router->addConfig($route_config, 'routes');

                $frontController = Zend_Controller_Front::getInstance();
                $router = $frontController->getRouter();
       }
	

       protected function _initViewHelpers() {

                $this->bootstrap('layout');
                $layout = $this->getResource('layout');
                $layout->setLayout('layout');
                $view = $layout->getView();
                $view->doctype('HTML5');
                $view->headMeta()->appendHttpEquiv('Content-type', 'text/html;charset=utf-8')
                                 ->appendName('viewport', 'width=device-width, initial-scale=1')
                                 ->appendHttpEquiv('X-UA-Compatible', 'IE=edge')
                                 ->appendHttpEquiv('Content-Language', 'en-US')
                                 ->appendName('robots', 'noydir, noodp');
                $view->headTitle($this->_config->appSettings->title)->setSeparator(' | ');
                $view->headLink(array('rel' => 'icon',  'href' => $view->baseUrl('/images/favicon.ico'), 'type' => 'image/x-icon'));

       }

       protected function _initSession() {
                Zend_Session::start();
                $sessionNamespace = new Zend_Session_Namespace($this->_config->appSettings->appName);
                Zend_Registry::set('sessionNamespace', $sessionNamespace);
               
                //Zend_Session::rememberMe();
       }
       
       protected function _initLoadPlugins(){
           

              $objPluginLoader = Engine_Plugins_PluginLoader::getInstance();
              $objPluginLoader->registerPlugin();
             
       }
       
       protected function _initLoadEnv(){
           
        if($this->_config->appSettings->env){
             define(APPLICATION_ENV, $this->_config->appSettings->env);
        }
          
             
             
       }
       
       	protected function _initZFDebug(){
		if(getenv('APPLICATION_ENV') != 'production' && $this->_config->appSettings->debugMode == 1  ):
			$autoloader = Zend_Loader_Autoloader::getInstance();
			$autoloader->registerNamespace('ZFDebug');
			
			$options = array(
				'plugins' => array('Variables', 
								   'File' => array('base_path' => '/path/to/project'),
								   'Memory', 
								   'Time', 
								   'Registry', 
								   'Exception')
			);
			
			# Instantiate the database adapter and setup the plugin.
			# Alternatively just add the plugin like above and rely on the autodiscovery feature.
			if ($this->hasPluginResource('db')) {
				$this->bootstrap('db');
				$db = $this->getPluginResource('db')->getDbAdapter();
				$options['plugins']['Database']['adapter'] = $db;
			}

			# Setup the cache plugin
			if ($this->hasPluginResource('cache')) {
				$this->bootstrap('cache');
				$cache = $this-getPluginResource('cache')->getDbAdapter();
				$options['plugins']['Cache']['backend'] = $cache->getBackend();
			}

			$debug = new ZFDebug_Controller_Plugin_Debug($options);
			$this->bootstrap('frontController');
			$frontController = $this->getResource('frontController');
			$frontController->registerPlugin($debug);
		endif;
	}
        
        protected function _initTmpDirectory(){
        # check tmp directory is writable
      
            if (!is_writable($this->_config->appSettings->logs->tmpDir)) {
                throw new Exception('Error: tmp dir is not writable ( ' . $this->_config->appSettings->logs->tmpDir . '), check folder/file permissions');
            }
			
        }
		
		protected function _initRoutes()
		{
			$router = Zend_Controller_Front::getInstance()->getRouter();
			include APPLICATION_PATH . "/configs/routes.php";
			
			//Zend_Controller_Front::getInstance()->setRouter(new My_Router());
			
		}

		public function _initDynamicRoutes()
		{
			$router = Zend_Controller_Front::getInstance()->getRouter();
			$dbAdapter = $this->getPluginResource('db')->getDbAdapter();
			$statement = $dbAdapter->query("SELECT * from promotions");
			$results = $statement->fetchAll();
			foreach($results as $result)
			 {
				$router->addRoute($result['promotion_url'],
				new Zend_Controller_Router_Route($result['promotion_url'].'/:controller/:action/:promoid', array(
				  'module' => 'home', // module name
				  'controller' => 'home', // controller name
				  'action' => 'promo', // action name
				  'promoid' => $result['promotion_id']))); // promotion id
			  }
			  
				return $router;
			
		}

        protected function _initForceSSL() {
            if($_SERVER['SERVER_PORT'] != '443') {
                header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                exit();
            }
        }


}

