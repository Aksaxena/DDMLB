<?php

class Application_Plugin_Script extends Zend_Controller_Plugin_Abstract {

    /**
     * This function is called once after router shutdown. It automatically
     * sets the layout for the default MVC or a module-specific layout.
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request) {

    }

}

?>
