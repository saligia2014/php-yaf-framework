<?php

use Core\Controller;
use Yaf\Application;

class IndexController extends Controller
{
    public function indexAction()
    {
        $result = Service\Demo::getInstance()->demo();

        $this->render('index/demo', ['demo' => $result]);
    }
}

