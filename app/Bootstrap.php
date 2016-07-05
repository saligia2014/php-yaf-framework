<?php

use Yaf\Loader;
use Yaf\Registry;
use Yaf\Application;
use Yaf\Config\Ini;
use Yaf\Dispatcher;
use Yaf\Bootstrap_Abstract;


define('ENV_DEV', 'dev');
define('ENV_PROD', 'prod');

class Bootstrap extends Bootstrap_Abstract
{

    public function _init(Dispatcher $dispatcher)
    {
        mb_internal_encoding('UTF-8');
        //ini_set('yaf.use_spl_autoload');
        ini_set('default_socket_timeout', -1);

        //因为yaf加载机制的原因，以Controller或Model结尾的类只在controllers或models目录下寻找，
        //所以这里需要提前引入某些类
        Loader::import(APP_PATH . '/app/library/Core/Controller.php');
        Loader::import(APP_PATH . '/app/library/Core/Model.php');

        $conf = Application::app()->getConfig();
        Registry::set('conf', $conf);

        $env = Application::app()->environ();

        $local = new Ini(APP_PATH . '/conf/local.ini', $env);
        Registry::set('local', $local);

        $routes = new Ini(APP_PATH . '/conf/routes.ini', $env);
        $dispatcher->getRouter()->addConfig($routes);
        Registry::set('routes', $routes);

        $db = new Ini(APP_PATH . '/conf/db.ini', $env);
        Registry::set('db', $db);

        $redis = new Ini(APP_PATH . '/conf/redis.ini', $env);
        Registry::set('redis', $redis);

        $dispatcher->registerPlugin(new MainPlugin);
    }

    protected function _initServerInfo()
    {
        if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SERVER_PROTOCOL'])) {
            $host = $_SERVER['HTTP_HOST'];
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ?
                'https://' : 'http://';

            Registry::set('host', $protocol . $host);
        } else {
            Registry::set('host', 'http://localhost');
        }
    }
}
