<?php

namespace libs;

use \Exception;

class Loader
{
    
    public static function loadModel(Controller $Controller)
    {
        $models = $defaultModel = $modelsParent = array();
        
        $nameDefaultModel = str_replace('Controller', '', get_class($Controller));
        $modelPlurals = array('s', 'es', 'ies');
        
        if (file_exists(ROOT.DS.APP.DS.'Model'.DS.$nameDefaultModel.".php")) {
            $defaultModel = array($nameDefaultModel);
        } else {
            foreach($modelPlurals as $plural) {
                $tryDefaultModel = trim($nameDefaultModel, $plural);
                if (file_exists(ROOT.DS.APP.DS.'Model'.DS.$tryDefaultModel.".php")) {
                    $defaultModel = array($tryDefaultModel);
                }
            }
        }

        if (isset($Controller->model) && !empty($Controller->model)) {
            $models = $Controller->model;
        }

        if (isset($Controller->modelParent) && !empty($Controller->modelParent)) {
            $modelsParent = $Controller->modelParent;
        }
        $models = array_unique(array_merge($models, $defaultModel, $modelsParent));

        if (!empty($models)) {
            foreach ($models as $nameModel) {
                $pathModel = 'app\Model\\'.$nameModel;
                if (file_exists(ROOT.DS.APP.DS.'Model'.DS.$nameModel.".php")) {
                    $Controller->$nameModel = new $pathModel();            
                } else {
                    if (!in_array($nameModel, $defaultModel)) {
                        throw new Exception("Model '$nameModel' não existe");
                    }
                }
            }
        }
        return $Controller;
    }
    
    public static function loadComponent($Object)
    {
        if (isset($Object->components) && !empty($Object->components)) {
            $components = $Object->components;
        }
        if (!empty($components)) {
            foreach ($components as $key => $value) {
                $component = is_string($value) ? $value : $key;
                if (strpos($component, " ")) {
                    list($component, $pathComponent) = explode(" ", $component);
                } else {
                    $pathComponent = 'libs\\'.$component;
                }
                if (file_exists(ROOT.DS.LIBS.DS.$component.".php")) {
                    $Object->$component = new $pathComponent($Object);
                } elseif (file_exists(ROOT.DS.'src'.DS.str_replace('\\', DS, $pathComponent).".php")) {
                    $Object->$component = new $pathComponent($Object);
                } else {
                    try {
                        $Object->$component = new $pathComponent($Object);
                    } catch (\Exception $e) {
                        var_dump($e->getMessage());
                    }
                }
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        $Object->$component->$subKey = $subValue;
                    }
                }
            }
        }
        return $Object;
    }
}