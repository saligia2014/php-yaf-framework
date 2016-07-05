<?php

namespace Core;

use Yaf\Exception;

class Service
{

    private $errorCode = 0;

    private $errorMessage;

    protected function setError($code, $message = '')
    {
        $this->errorCode = $code;
        $this->errorMessage = $message;
    }

    public function errorCode()
    {
        return $this->errorCode;
    }

    public function errorMessage()
    {
        return $this->errorMessage;
    }

    public function takeError(Service $srv)
    {
        $this->errorCode = $srv->errorCode();
        $this->errorMessage = $srv->errorMessage();
    }

    /**
     * @param bool $force
     * @throws Exception
     */
    public function throwError($force = true)
    {
        if ($this->errorCode() > 0) {
            throw new Exception($this->errorMessage(), $this->errorCode());
        }

        if ($force) {
            throw new Exception('server error', 1);
        }
    }
}
