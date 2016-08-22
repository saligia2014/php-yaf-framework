<?php

use Lib\WeixinBase;
use Lib\Exception;
use Yaf\Registry;
use Service\Weixin;

/**
 * Class LoginController
 * @desc 登录
 */
class IsloginController extends WeixinBase
{

    protected function get()
    {
        $weixinUser = $this->getUser();
        if ($weixinUser) {
            $this->renderJson($weixinUser);
        } else {
            $this->renderJson([], Exception::USER_NOT_LOGIN, 'weixin user not login');
        }
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
        // TODO: Implement options() method.
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

