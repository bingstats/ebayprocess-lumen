<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/21
 * Time: 10:02
 */

namespace App\EbayApi\Component;


use Mockery\CountValidator\Exception;

class EbayComponent
{
    protected static $_instance;

    static public function create($config=array(),$className=__CLASS__)
    {
        if(isset(self::$_instance[$className]))
            return self::$_instance[$className];
        else{
            self::$_instance[$className] = new $className($config);
            return self::$_instance[$className];
        }
    }

    public function __construct($config=array())
    {
        if(!empty($config)){
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }
        $this->init();
    }

    public function init()
    {

    }

    public function __get($name)
    {
        // TODO: Implement __get() method.
        $getter = 'get'.$name;
        $class = get_class($this);
        if(method_exists($this,$getter)) return $this->$getter;
        else{
            throw new Exception('Property '.$class.$name." is not defined.");
        }

    }
    public function __set($name, $value)
    {
        // TODO: Implement __set() method.
        $setter = 'set'.$name;
        $class = get_class($this);
        if(method_exists($this,$setter)) return $this->$setter($value);
        if(method_exists($this,'get'.$name)){
            throw new Exception('Property '.$class.$name." is read only.");
        }
        else{
            throw new Exception('Property '.$class.$name." is not defined.");
        }

    }
}