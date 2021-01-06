<?php
namespace libs;

use libs\Router;
use libs\View;
use \Exception;

class Application
{
    public $ajax = false;
    public $controller;
    public $action;
    public $url;
    public $request = array(
        'controller'  => 'index',
        'action'  => 'index'
    );
    public $requestFields = array();
    private $Controller;

    private function loadRoute()
    {
        Router::loadRoutes();
		if (defined('USE_BASE_FOLDER') && USE_BASE_FOLDER === true) {
			Router::baseFolder();
		}
        $this->request = Router::loadUrl();
        $checkParams = Router::checkRouteParams(null, $this->request);
        if ($checkParams === false) {
            Router::redirect(null, $this->request, array('statusCode' => 302, 'persistParams' => true));
        }
    }

    public function dispatch()
    {
        $this->loadRoute();
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
            $this->request['request'] = 'ajax';
        } else {
            $this->request['request'] = strtolower($_SERVER['REQUEST_METHOD']);
        }

        $this->url = $_SERVER['REQUEST_URI'];
        $this->requestFields = $_POST + $_GET;

        //checking if the controller file exists
        $ControllerFile = ROOT.DS.APP.DS.'Controller'.DS.ucfirst($this->request['controller']).'Controller.php';
        if (file_exists($ControllerFile)) {
            require_once $ControllerFile;
        } else {
            $this->exception('File '.$ControllerFile.' not found');
        }

        //checking if the class exists
        $Class = $this->request['controller'].'Controller';
        if (class_exists($Class)) {
            $this->Controller = $Controller = new $Class;
        } else {
            $this->exception("Class '$Class' doesn't exist in the file '$ControllerFile'");
        }

        $Controller->url = $this->url;
        $Controller->request = $this->request;
        $Controller->requestFields = $this->requestFields;
        $controllerComponents = isset($Controller->components) ? $Controller->components : array();
        $Controller->initialize($Controller);
        foreach($controllerComponents as $key => $value) {
            $component = is_array($value) ? $key : $value;
            if (strpos($component, " ")) {
                list($component, $pathComponent) = explode(" ", $component);
            }
            if (method_exists($Controller->$component, 'initialize')) {
                $Controller->$component->initialize($Controller);
            }
        }
        $Controller->beforeFilter();
        foreach($controllerComponents as $key => $value) {
            $component = is_array($value) ? $key : $value;
            if (strpos($component, " ")) {
                list($component, $pathComponent) = explode(" ", $component);
            }
            if (method_exists($Controller->$component, 'startup')) {
                $Controller->$component->startup($Controller);
            }
        }

        //checking if the method exists
        $method = $this->request['action'];
        if (method_exists($Controller, $method)) {
            $Controller->$method();
        } else {
            $this->exception("Method '$method' doesn't exist in the class $Class'");
        }
    }	

    public function exception($exception_message = null)
    {
		if (defined('DEBUG') && DEBUG === true) {
			throw new Exception($exception_message);
		} else {
			$view = new View();
			$view->layout = 'default_blank';
			$view->view = '404';
			$view->setViewParams($this->request);
			$view->render();
			exit;
		}
	}

    public function run()
    {
        $this->Controller->beforeRender();
        $this->Controller->run();
        exit;
    }
}