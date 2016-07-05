<?php


use Yaf\Registry;
use Lib\Mobile\BaseCtrlr;
use Core\Session;
use Lib\Exception;
use Service\Weixin;


class WeixinController extends BaseCtrlr
{
    /**
     * @desc 登录
     */
    public function loginAction()
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

    /**
     * @desc 推送
     */
    public function receiveEventsAction()
    {
        $signature = $this->get('signature');
        $timestamp = $this->get('timestamp');
        $nonce = $this->get('nonce');
        if (!Service\Weixin::getInstance()->weixinCheck($signature, $timestamp, $nonce)) {
            return;
        }

        Core\RedisQueue::getInstance('main')->push('weixin_event_queue', file_get_contents('php://input'));
    }

    /**
     * @throws \Lib\Exception
     * @desc 回调
     */
    public function callbackAction()
    {
        $code = $this->get('code');
        //$refer = $this->get('state');
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
                $this->redirect('/h5');
            }

        }
    }

    /**
     * @return array
     * @desc 验签
     */
    public function signatureAction()
    {
        $url = $this->get('url');
        $result = Weixin::getInstance()->createJsSig($url);
        $result['appid'] = Weixin::getInstance()->appId;
        $this->renderJson($result);
    }

    /**
     * @desc 是否登录
     */
    public function isLoginAction()
    {
        $wxUser = Weixin::getInstance()->current();
        if ($wxUser) {
            $this->renderJson($wxUser);
        } else {
            $this->renderJson([], Exception::USER_NOT_LOGIN, 'weixin user not login');
        }
    }
}