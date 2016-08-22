<?php

use Lib\WeixinBase;

class TestController extends WeixinBase
{
    protected function get()
    {
        echo 'get';
    }

    protected function post()
    {
        $this->renderJson($this->getRestfulParam());
    }

    protected function put()
    {
        // TODO: Implement put() method.
    }

    protected function delete()
    {
        // TODO: Implement delete() method.
    }

    protected function options()
    {
        $this->renderJson(null, ['get']);
    }

    protected function head()
    {
        // TODO: Implement head() method.
    }

    protected function trace()
    {
        // TODO: Implement trace() method.
    }

    protected function connect()
    {
        // TODO: Implement connect() method.
    }
}

