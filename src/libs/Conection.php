<?php
namespace libs;

use app\Config\DatabaseConfig;
use \PDO;
use \PDOException;

class Conection
{

    private static $pdo;

    private $config;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if (!isset(self::$pdo))
        {
            $config = DatabaseConfig::database();
            try {
                $options = array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
                    PDO::ATTR_PERSISTENT => false
                );
                self::$pdo = new PDO(
                    "mysql:host=" . $config['host'] . "; dbname=" . $config['database'] . "; charset=" . $config['charset'] . ";",
                    $config['login'],
                    $config['password'],
                    $options
                ); 
            } catch (PDOException $e) {
                print "Erro: " . $e->getMessage();
            }
        }
        return self::$pdo;
    }
}