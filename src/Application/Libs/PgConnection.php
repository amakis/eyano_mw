<?php
namespace App\Application\Libs;

use Symfony\Component\Yaml\Yaml;
/**
 * Represent the Connection
 */
class PgConnection {
    /**
     * Connection
     * @var type
     */
    private static $conn;
    /**
     * Connect to the database and return an instance of \PDO object
     * @return \PDO
     * @throws \Exception
     */
    public function connect($params) { 
        // read parameters in the ini configuration file
        //$config = Yaml::parseFile(__DIR__ .'/../../../config.yaml');
        //$params = $config['PgzK'];
        if ($params === false) {
            throw new \Exception("Error reading database configuration file");
        }
        // connect to the postgresql database
        $conStr = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
                $params['host'],
                $params['port'],
                $params['database'],
                $params['user'],
                $params['password']);
        $pdo = new \PDO($conStr);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
    /**
     * return an instance of the Connection object
     * @return type
     */
    public static function get() {
        if (null === static::$conn) {
            static::$conn = new static();
        }
        return static::$conn;
    }
    protected function __construct() {
    }
    private function __clone() {
    }
    public function __wakeup() {
    }
}