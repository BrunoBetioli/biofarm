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
                'host' => '',
                'login' => '',
                'password' => '',
                'charset' => 'utf8',
                'database' => ''
            ]
        ];

        return $database[$dbOption] ?? null;
    }
}