<?php
class Admin_Bootstrap extends Zend_Application_Module_Bootstrap
{
	/* (non-PHPdoc)
	 * @see Zend_Application_Bootstrap_BootstrapAbstract::__call()
	 */
// 	public function __call($method, array $args) {
// 		// TODO: Auto-generated method stub

// 	}

    function _initView() {
    	$bootstrap = $this->getApplication();
    	$layout = $bootstrap->getResource('layout');
       // $layout->setLayout('adminlayout');
       // $layout->setLayout('layout');
    	//$view = $layout->getView();

    }


}