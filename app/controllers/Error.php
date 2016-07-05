<?php

use Yaf\Registry;


class ErrorController extends Core\Controller
{

    public function errorAction($exception)
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->renderJson(null, $exception->getCode(), $exception->getMessage());
        } else {
            echo "还没有定制错误页面, Code: " . $exception->getCode() . '   Msg: ' . $exception->getMessage();
        }
    }
}
