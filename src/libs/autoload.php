<?php
// autoload classes based on a 1:1 mapping from namespace to directory structure.
spl_autoload_register(function ($className) {

    // substitui o separador do namespace pelo directory separator
    $namespace = str_replace('\\', DS, __NAMESPACE__);

    // substitui o separador do nome da classe pelo directory separator
    $className = str_replace('\\', DS, $className);

    // pega nome inteiro do arquivo que contém a classe
    $file = ROOT.DS.(!empty($namespace) ? $namespace.DS : '')."{$className}.php";

    // pega o arquivo se ele é legível
    if (is_readable($file)) {
        require_once $file;
    }
});