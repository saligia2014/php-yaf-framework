<?php


use Core\Translator;
use Core\Logger;

class ErrorController extends \Core\Controller
{

    public function errorAction($exception)
    {
        $uri = $this->getRequest()->getBaseUri();
        $code = $exception->getCode();
        $msg = $exception->getMessage();
        $message = "uri: $uri, " . $code . ': ' . $exception->getMessage();
        Logger::getInstance()->error($message);
        $this->renderJson(null, $code ?: 1, $msg);
    }
}
