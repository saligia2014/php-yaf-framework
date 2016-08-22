<?php

use Lib\WeixinBase;
use Yaf\Registry;

/**
 * Class LoginController
 * @desc 推送
 */
class ReceiveController extends WeixinBase
{
    protected function get()
    {
        $signature = $this->getHttpGetParam('signature');
        $timestamp = $this->getHttpGetParam('timestamp');
        $nonce = $this->getHttpGetParam('nonce');
        if (!Service\Weixin::getInstance()->weixinCheck($signature, $timestamp, $nonce)) {
            return;
        }

        Core\RedisQueue::getInstance('main')->push('weixin_event_queue', file_get_contents('php://input'));
    }

    protected function post()
    {
        // TODO: Implement post() method.
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

