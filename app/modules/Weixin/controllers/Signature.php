<?php

use Lib\WeixinBase;
use Yaf\Registry;
use Service\Weixin;

/**
 * Class LoginController
 * @desc 登录
 */
class SignatureController extends WeixinBase
{
    protected function get()
    {
        // TODO: Implement get() method.
    }

    protected function post()
    {
        $url = $this->getRestfulParam('url');
        $result = Weixin::getInstance()->createJsSig($url);
        $result['appid'] = Weixin::getInstance()->appId;
        $this->renderJson($result);
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
        $this->renderJson(null, ['post']);
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

