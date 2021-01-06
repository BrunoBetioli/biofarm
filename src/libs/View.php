<?php

namespace libs;

use \Exception;
use libs\Router;

class View {

    public $layout = 'default';

    public $element;

    public $view;

    private $currentController;

    private $currentAction;

    private $currentIDParam;

    private $pathFolderView;

    private $viewParams = array();

    function __construct()
    {
        global $application;
        $this->currentController = ucfirst($application->request["controller"]);
        $this->pathFolderView = ROOT.DS.APP.DS.'View'.DS;
        $this->viewParams = $application->request;
        $this->view = $application->request['action'];
        if (isset($this->viewParams['prefix']) && !empty($this->viewParams['prefix'])) {
            $this->layout = $this->viewParams['prefix'].'_'.$this->layout;
        }
        if (isset($this->viewParams['request']) && $this->viewParams['request'] == 'ajax') {
            $this->layout = 'ajax';
        }
    }

    public function setViewParams ($viewParams = array())
    {
        $this->viewParams = $this->viewParams + $viewParams;
    }

    private function setFileView ($file, $type)
    {
        $type = $folder = ucfirst($type);
        if (!in_array($type, array('Element', 'Layout', 'View'))) {
            throw new Exception("View type '$type' not allowed.");
        } else {
            if ($type == 'View') {
                $folder = ucfirst($this->viewParams['controller']);
            }
            $fileView = $this->pathFolderView.$folder.DS.$file.'.phtml';
            if (!file_exists($fileView)) {
                $fileView = $this->pathFolderView.'Element'.DS.$file.'.phtml';
                if (!file_exists($fileView)) {
                    throw new Exception("The html file '$file' doesn't exist.");
                } else {
                    $this->{strtolower($type)} = $file;
                }
            } else {                
                $this->{strtolower($type)} = $file;
            }
        }
        return $fileView;
    }

    public function view($file = null, $dataArray = array())
    {
        if (!empty($file)) {
            $viewFile = (string) $this->setFileView($file, 'View');
        }
        
        return $this->loadView($viewFile, $dataArray);
    }

    public function layout($file = null, $dataArray = array())
    {
        if (!empty($file)) {
            $layoutFile = (string) $this->setFileView($file, 'Layout');
        }

        return $this->loadView($layoutFile, $dataArray);
    }

    public function element($file, $dataArray = array())
    {
        if (!empty($file)) {
            $elementFile = (string) $this->setFileView($file, 'Element');
        }

        return $this->loadView($elementFile, $dataArray);
    }

    public function getView()
    {
        return $this->view;
    }

    public function getParams()
    {
        return $this->viewParams;
    }

    private function loadView($file, Array $dataArray)
    {
        ob_start();
        extract($dataArray, EXTR_SKIP);
        if (file_exists($file)) {
            include $file;
        } else {
            throw new Exception("The html file '$file' doesn't exist.");
        }
        return ob_get_clean();
    }

    public function link($url = null, $fullBaseUrl = false, $full = true)
    {
        return Router::url($url, $fullBaseUrl, $full);
    }

    public function render()
    {
        $this->viewParams['viewContent'] = $this->view($this->view, $this->viewParams);
        echo $this->layout($this->layout, $this->viewParams);
    }
}