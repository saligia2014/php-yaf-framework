<?php


class ErrorController extends Core\Controller
{

    public function errorAction($exception)
    {
        echo 'Cli Error: ' . $exception->getCode() . ', Msg: ' . $exception->getMessage();
    }
}
