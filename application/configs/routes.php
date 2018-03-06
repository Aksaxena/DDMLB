<?php
/*
class My_Router extends Zend_Controller_Router_Rewrite
{
    public function route(Zend_Controller_Request_Abstract $request)
    {
        $pathBits = explode('/', $request->getPathInfo());
		print_r($pathBits);
        if (!empty($pathBits[0])) {
            // check whether $pathBits[0] is a username here
            // with a database lookup, if yes, set params and return
        }

        // fallback on the standard ZF router
        return parent::route($request);
    }
}
/* 
$route = new Zend_Controller_Router_Route(
    'author',
    array(
        'controller' => 'user',
        'action'     => 'index'
    ) 
);

$router->addRoute('author', $route);
 */
