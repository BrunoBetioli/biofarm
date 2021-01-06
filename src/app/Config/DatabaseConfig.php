<?php

namespace app\Config;

class DatabaseConfig
{

    public static function database()
    {
        return array(
            'datasource' => 'Mysql',
            'persistent' => true,
            'host' => 'localhost',
            'login' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'database' => 'biofarm'
        );
    }
}