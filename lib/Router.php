<?php

namespace Lib;

/**
 * Description of Route
 *
 * @author Himel
 */
class Router {
    
    protected static $requestUri;
    protected static $serverName;
    protected static $routes;
    
    public function __construct() {
        
        self::$serverName = $_SERVER['SERVER_NAME'];
        
        if(isset($_SERVER['PATH_INFO'])){
            self::$requestUri =  explode("/",$_SERVER['PATH_INFO']);
            self::$requestUri = array_slice(self::$requestUri, 1);
        }else{
            self::$requestUri = null;
        }
        
        self::$routes = require 'config/routes.php';
    }
    
    
    public function getRequest()
    {
        if(!empty(self::$requestUri)){
            
            $special_chars = Config::get('special_chars');
            
            switch(count(self::$requestUri)){
                case 1:
                   
                    if(empty(self::$requestUri[0])){
                        return new Request(self::$requestUri,"",self::$routes['default_route'], self::$routes['default_method'], null);
                    }
                    if(!array_key_exists('/'.self::$requestUri[0],self::$routes['routes'])){
                        throw new \Exception("Route not matched",500);
                    }
                    $routePattern = '/'.self::$requestUri[0]; 
                    $route = self::$routes['routes'][$routePattern];
                    
                    return new Request(self::$requestUri,$routePattern,$route['default_route'], $route['default_method'], null);
                    
                case 2:
                    $routePattern = '/'.self::$requestUri[0].'/'.self::$requestUri[1];
                    if(!array_key_exists($routePattern,self::$routes['routes'])){
                        throw new \Exception("Route not matched",500);
                    }
                    
                    
                    $route = self::$routes['routes'][$routePattern];
                    
                    return new Request(self::$requestUri,$routePattern,$route['default_route'],$route['default_method'], null);
                    
                case 3:
                    
                    $param = self::$requestUri[2];
                    
                    
                    if(preg_match("/$special_chars/",$param)){
                        throw new \Exception("Url should not contain character other than [a-zA-Z0-9-_]");
                    }
                    
                    if(is_numeric($param)){
                        $key = '/'.self::$requestUri[0].'/'.self::$requestUri[1].'/:num';
                        if(!array_key_exists($key,self::$routes['routes'])){
                           throw new \Exception("Route not matched",500);
                        }
                        
                        $route = self::$routes['routes'][$key];
                        return new Request(self::$requestUri,$key,$route['default_route'],$route['default_method'], $param);
                        
                    }else if(is_string($param)){
                        $key = '/'.self::$requestUri[0].'/'.self::$requestUri[1].'/:any';
                        
                        if(!array_key_exists($key,self::$routes['routes'])){
                            throw new \Exception("Route not matched",500);
                        }
                        
                        $route = self::$routes['routes'][$key];
                        return new Request(self::$requestUri,$key,$route['default_route'],$route['default_method'], $param);
                        
                    }else{
                        throw new \Exception("Route not matched",500);
                    }
                    break;
                default:
                        $params = [];
                        $key  = '/'.self::$requestUri[0].'/'.self::$requestUri[1];
                        for($i=2;$i<count(self::$requestUri);$i++){
                            $param = self::$requestUri[$i];
                            
                            if(preg_match("/$special_chars/",$param)){
                                throw new \Exception("Url should not contain character other than [a-zA-Z0-9-_]");
                            }
                            
                            $params[] = $param;
                            if(is_numeric($param)){
                                $key .= '/:num';
                            }else if(is_string($param)){
                                $key .= '/:any';
                            }
                        }
                        
                        if(!array_key_exists($key,self::$routes['routes'])){
                           throw new \Exception("Route not matched",500);
                        }
                        
                        $route = self::$routes['routes'][$key];
                        return new Request(self::$requestUri,$key,$route['default_route'],$route['default_method'], $params);
                        
                    break;
            }
            
        }else{
            return new Request(self::$requestUri,"",self::$routes['default_route'], self::$routes['default_method'], null);
        }
    }
    
}
