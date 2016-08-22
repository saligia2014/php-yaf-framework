<?php

use Lib\WeixinBase;
use Yaf\Registry;
/**
 * Class LoginController
 * @desc 登录
 */
class LoginController extends WeixinBase
{
    protected function get()
    {
        \Core\Session::getInstance()->set('weixin_refer_url', $this->referer());

        $query = [
            'appid' => Service\Weixin::getInstance()->appId,
            'redirect_uri' => Registry::get('local')->weixin->redirect_uri,
            'response_type' => 'code',
            'scope' => 'snsapi_userinfo',
        ];

        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' .
            http_build_query($query) . '#wechat_redirect';

        $this->redirect($url);
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

