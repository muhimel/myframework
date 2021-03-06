<?php
namespace Lib\ORM\Driver;

use Lib\Config;
/**
 * Description of Connection
 *
 * @author Himel
 */
class Connection {
    
    protected static $conn;
    const PLATFORM_DRIVER_PDO_ORACLE = "pdo_oci";
    const PLATFORM_DRIVER_PDO_MYSQL  = "pdo_mysql";


    private function __construct() {
       // ;
    }

    public static function getInstance() {
        
        if(empty(self::$conn)){
            $databaseConfig = Config::get('database');
            $databaseConfig = $databaseConfig[Config::get('default_driver')];
            $host   = (isset($databaseConfig['host']))? $databaseConfig['host']:null;
            $driver = $databaseConfig['driver'];
            $user   = $databaseConfig['user'];
            $pass   = $databaseConfig['pass'];
            $dbName = $databaseConfig['dbname']; 
          
            if(self::PLATFORM_DRIVER_PDO_MYSQL == $driver){
                if(empty($databaseConfig) || empty($driver) || empty($host) || empty($user) || empty($dbName)){
                    throw new \Exception("Check Database configuration at config.php",500);
                }
                $driver = (preg_match('/pdo_/',$driver))? str_replace("pdo_", "", $driver) : $driver;
                self::$conn = new \PDO("$driver:host=$host;dbname=$dbName",$user,$pass);
            }
            
            if(self::PLATFORM_DRIVER_PDO_ORACLE == $driver){
                if(empty($databaseConfig) || empty($driver) || empty($user) || empty($dbName)){
                    throw new \Exception("Check Database configuration at config.php",500);
                }
                $driver = (preg_match('/pdo_/',$driver))? str_replace("pdo_", "", $driver) : $driver;
                self::$conn = new \PDO("$driver:dbname=$dbName",$user,$pass);
                
            }
        }
        
        return static::$conn;
    }
    
    public static function getDriver()
    {
        if(self::isPDOMysql()){
            $driver = new Mysql;
        }
        if(self::isPDOOracle()){
            $driver = new PDOOracle;
        }
        return $driver;
    }
    
    public static function isPDOMysql(){
        $databaseConfig = Config::get('database');
        $driver = $databaseConfig[Config::get('default_driver')];
        if((!empty($driver)) && $driver['driver'] == self::PLATFORM_DRIVER_PDO_MYSQL){
            return true;
        }
        return false;
    }
    
    public static function isPDOOracle(){
        $databaseConfig = Config::get('database');
        $driver = $databaseConfig[Config::get('default_driver')];
        if((!empty($driver)) && $driver['driver'] == self::PLATFORM_DRIVER_PDO_ORACLE){
            return true;
        }
        return false;
    }
    
    public static function closeConnection()
    {
        if(static::$conn != null){
            static::$conn = null;
        }
    }
    
    
    
}
