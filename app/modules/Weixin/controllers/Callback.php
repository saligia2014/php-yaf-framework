<?php

use Lib\WeixinBase;
use Yaf\Registry;
use Service\Weixin;
use Core\Session;

/**
 * Class LoginController
 * @desc 回调
 */
class CallbackController extends WeixinBase
{
    protected function get()
    {
        $code = $this->getHttpGetParam('code');
        //$refer = $this->getParam('state');
        $refer = Core\Session::getInstance()->get('weixin_refer_url');
        $query = [
            'appid' => Service\Weixin::getInstance()->appId,
            'secret' => Service\Weixin::getInstance()->secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];

        /**
         * @desc 微信授权
         */
        $info = Service\WeixinApi\User::getInstance()->oauth2($query);
        $wxUser = [];
        if ($info && isset($info['openid'])) {
            $wxUser = WxUserModel::getInstance()->find(['unionid' => $info['unionid']]);
            $paramsByUserExtend = [
                'wx_user_id' => empty($wxUser['id']) ? 0 : $wxUser['id'],
                'unionid' => $info['unionid'],
                'openid' => $info['openid'],
                'appid' => Service\Weixin::getInstance()->appId
            ];
            if (empty($wxUser)) {
                $wxUser = Weixin::getInstance()->createUser($info);
                $paramsByUserExtend['wx_user_id'] = $wxUser['id'];
                if (!empty($wxUser)) {
                    Weixin::getInstance()->createUserExtend($paramsByUserExtend);
                }
            } else {
                $wxUser = array_merge($wxUser, Weixin::getInstance()->updateUser($info, $wxUser['id']));
                $where = ['openid' => $info['openid'], 'wx_user_id' => $wxUser['id']];
                $wxUserExtend = WxUserExtendModel::getInstance()->find($where);
                if (empty($wxUserExtend)) {
                    Weixin::getInstance()->createUserExtend($paramsByUserExtend);
                } else {
                    Weixin::getInstance()->updateUserExtend($paramsByUserExtend, $where);
                }
            }
        }

        if ($wxUser) {
            Session::getInstance()->set('wxuser_id', $wxUser['id']);
            if (preg_match('/http/i', $refer)) {
                $this->redirect($refer);
            } else {
                $this->redirect('/');
            }

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

