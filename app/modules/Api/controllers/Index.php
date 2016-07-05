<?php

use Lib\Api\BaseCtrlr;
use Yaf\Application;

class IndexController extends BaseCtrlr
{
    public function indexAction()
    {
        $result = Service\Demo::getInstance()->api();

        $this->renderJson($result);
    }
}

