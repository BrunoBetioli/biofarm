<?php
namespace libs;

use libs\Router;
use libs\View;
use \Exception;

/**
 * Class Application
 *
 * Class that will load and execute our application
 */
class Application
{
    public $controller;
    public $action;
    public $url;
    public $request = array(
        'controller'  => 'index',
        'action'  => 'index'
    );
    public $requestFields = array();
    private $Controller;

    /**
     * Function loadRoute()
     *
     * Function that will interpret our route and establish the corresponding controller and method, besides getting the variables.
     * If the route isn't exactly matched, this function will redirect to the right one
     *
     * @return void
     */
    private function loadRoute()
    {
        /* loading the routes */
        Router::loadRoutes();

        /* defining if the application will use its folder's name on the URL or not */
        if (defined('USE_BASE_FOLDER') && USE_BASE_FOLDER === true) {
            Router::baseFolder();
        }

        /* loading the current URL */
        $this->request = Router::loadUrl();

        /* checking if the current URL matches some route */
        $checkParams = Router::checkRouteParams(null, $this->request);

        /* redirecting to the right URL if the current doesn't match any route */
        if ($checkParams === false) {
            Router::redirect(null, $this->request, array('statusCode' => 302, 'persistParams' => true));
        }
    }

    /**
     * Function dispatch()
     *
     * Function that will load and set all components and variables used inside the controller
     *
     * @return void
     */
    public function dispatch()
    {
        $this->loadRoute();

        /* defining the request method */
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
            $this->request['request'] = 'ajax';
        } else {
            $this->request['request'] = strtolower($_SERVER['REQUEST_METHOD']);
        }

        /* defining the URL */
        $this->url = $_SERVER['REQUEST_URI'];

        /* defining the POST and GET variables */
        $this->requestFields = $_POST + $_GET;

        /* checking if the controller file exists */
        $ControllerFile = ROOT.DS.APP.DS.'Controller'.DS.ucfirst($this->request['controller']).'Controller.php';
        if (file_exists($ControllerFile)) {
            require_once $ControllerFile;
        } else {
            $this->exception('File '.$ControllerFile.' not found');
        }

        /* checking if the class exists */
        $Class = $this->request['controller'].'Controller';
        if (class_exists($Class)) {
            $this->Controller = $Controller = new $Class;
        } else {
            $this->exception("Class '$Class' doesn't exist in the file '$ControllerFile'");
        }

        /* setting the url, router result and post + get variables */
        $Controller->url = $this->url;
        $Controller->request = $this->request;
        $Controller->requestFields = $this->requestFields;

        /* initializing the controller */
        $Controller->initialize($Controller);

        /* establishing the components that will be loaded in controller and initializing them */
        $controllerComponents = isset($Controller->components) ? $Controller->components : array();
        foreach($controllerComponents as $key => $value) {
            $component = is_array($value) ? $key : $value;
            if (strpos($component, " ")) {
                list($component, $pathComponent) = explode(" ", $component);
            }
            if (method_exists($Controller->$component, 'initialize')) {
                $Controller->$component->initialize($Controller);
            }
        }

        /* calling the beforeFilter method, that could execute something before the method defined by the URL */
        $Controller->beforeFilter();

        /* starting the components previously loaded */
        foreach($controllerComponents as $key => $value) {
            $component = is_array($value) ? $key : $value;
            if (strpos($component, " ")) {
                list($component, $pathComponent) = explode(" ", $component);
            }
            if (method_exists($Controller->$component, 'startup')) {
                $Controller->$component->startup($Controller);
            }
        }

        /* checking if the method exists */
        $method = $this->request['action'];
        if (method_exists($Controller, $method)) {
            $Controller->$method();
        } else {
            $this->exception("Method '$method' doesn't exist in the class $Class'");
        }
    }

    /**
     * Function exception()
     *
     * Function that will throw the exception if the application is in debug mode or show 404 page if it isn't
     *
     * @param string $exception_message - Message thrown by the exception
     *
     * @return void
     */
    public function exception(string $exception_message = null)
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

    /**
     * Function run()
     *
     * Function that will execute any code inside the beforeRender method and render the output
     *
     * @return void
     */
    public function run()
    {
        /* calling the beforeRender method, that could execute something after the method defined by the URL but before rendering the output */
        $this->Controller->beforeRender();

        /* rendering the output */
        $this->Controller->run();
        exit;
    }
}