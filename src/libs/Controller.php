<?php

namespace libs;

use libs\Application;
use libs\Loader;
use libs\Router;
use libs\View;

class Controller extends Application  {

    protected $View;

    protected $viewParams = array();

    public $methods = array();

    public $params = array();
    
    public $url;

    public function beforeFilter()
    {

    }

    public function beforeRender()
    {

    }

    public function initialize(Controller $Controller)
    {
        $childMethods = get_class_methods($Controller);
        $parentMethods = get_class_methods('libs\Controller');

        $Controller->methods = array_diff($childMethods, $parentMethods);
        $Controller = Loader::loadModel($Controller);
        $Controller = Loader::loadComponent($Controller);
        $Controller->View = new View();
        return $Controller;
    }

    protected function loadModel(Array $models)
    {
        if (!empty($models)) {
            $this->model = $models;
            return Loader::loadModel($this);
        }
    }

    public function set($one, $two = null)
    {
        if (is_array($one)) {
            if (is_array($two)) {
                $data = array_combine($one, $two);
            } else {
                $data = $one;
            }
        } else {
            $data = array($one => $two);
        }
        $this->viewParams = $data + $this->viewParams;
    }

    public function run()
    {
        $this->View->setViewParams($this->viewParams);
        $this->View->render();
    }

    public function redirect($current = null, $url = array(), $options = array())
    {
        Router::redirect($current, $url, $options);
    }
}