<?php
namespace libs;

use app\Config\DatabaseConfig;
use \PDO;
use \PDOException;

class Connection
{

    private static $pdo = array();

    private $config;

    private function __construct()
    {
    }

    public static function getInstance($dbOption = 'default')
    {
        if (!isset(self::$pdo[$dbOption]))
        {
            $config = DatabaseConfig::database($dbOption);
            try {
                if ($config['datasource'] == 'sqlsrv') {
                    $dsn = "sqlsrv:Server=" . $config['host'] . ";Database=" . $config['database'] . ";ConnectionPooling=0";
                } else {
                    $dsn = "mysql:host=" . $config['host'] . "; dbname=" . $config['database'] . "; charset=" . $config['charset'] . ";";
                }
                $options = array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
                    PDO::ATTR_PERSISTENT => $config['persistent'],
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                );
                self::$pdo[$dbOption] = new PDO(
                    $dsn,
                    $config['login'],
                    $config['password'],
                    $options
                );
            } catch (PDOException $e) {
                print "Erro: " . $e->getMessage();
            }
        }
        return self::$pdo[$dbOption];
    }
}