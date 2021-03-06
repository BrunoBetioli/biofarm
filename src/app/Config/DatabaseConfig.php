<?php

namespace app\Config;

class DatabaseConfig
{

    public static function database($dbOption = 'default')
    {
        $database = [
            'default' => [
                'datasource' => 'mysql',
                'persistent' => false,
                'host' => 'localhost',
                'login' => 'root',
                'password' => '',
                'charset' => 'utf8',
                'database' => 'biofarm'
            ]
        ];

        return $database[$dbOption] ?? null;
    }
}