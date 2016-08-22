<?php

namespace Lib;

class Exception extends \Yaf\Exception
{

    public function __construct($code, $message = '', $previous = null)
    {
        if ($code < 10000) {
            //未知错误
            $code = 9999;
        }
        parent::__construct($message, $code, $previous);
    }


    /**
     * base
     */
    const PERMISSION_DENIED = 10000;

    const SERVER_ERROR = 10001;

    const MISS_PARAM = 10002;

    /**
     * weixin
     */
    const USER_NOT_LOGIN = 11000;
}
