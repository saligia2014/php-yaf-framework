<?php
define('APP_PATH', __DIR__);
ini_set('display_errors', 1);
(new Yaf\Application(APP_PATH . '/conf/app.ini'))
    ->bootstrap()
    ->getDispatcher()
    ->dispatch(new Yaf\Request\Simple());
