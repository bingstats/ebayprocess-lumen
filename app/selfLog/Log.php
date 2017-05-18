<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/7
 * Time: 14:06
 * default log directory is storage/api/
 * default log name format is YYYY-mm-dd.log
 */

namespace App\selfLog;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class Log
{
    //define static log instance.
    protected static $_log_instance;

    /**
     * get log instance
     *
     * @return object
     */
    public static function getLogInstance()
    {
     if(static::$_log_instance === null){
         static::$_log_instance = new Logger('ProcessLogs');
     }
        return static::$_log_instance;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method options: debug|info|notice|warming|error|critical|alert|emergency
     * @param array $args dispatch param
     * @return mixed
     *
     */
    public static function __callStatic($method, $args)
    {
        // TODO: Implement __callStatic() method.
        $instance = static::getLogInstance();
        $message = $args[0];
        $context = isset($args[1]) ? $args[1] : [];
        $path = isset($args[2]) ? $args[2] : 'runtime/'.date('Y-m-d').'.log';
        $handle = new StreamHandler(storage_path($path), Logger::toMonologLevel($method),false,0777);
        $handle->setFormatter(new LineFormatter(null, null, true, true));
        $instance->pushHandler($handle);
       $instance->$method($message, $context);

    }
}