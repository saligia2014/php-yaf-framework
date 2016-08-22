<?php

namespace Service;

use Yaf\Application;
use Yaf\Registry;
use Core\Service;
use Core\Logger;
use Core\Redis;
use Lib\Exception;


/**
 * 微信公众平台
 */
class Weixin extends Service
{

    /**
     *
     * @var Weixin
     */
    private static $instance;


    /**
     * @return Weixin
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }


    public $appId;

    public $secret;

    public $host;

    public $callback;

    public $share;

    public $fileHost;


    public function __construct()
    {
        $conf = Registry::get('local')->weixin;
        $this->share = $conf->share;
        $this->appId = $conf->appid;
        $this->secret = $conf->secret;
        $this->callback = $conf->callback;
        $this->host = $conf->host;
        $this->fileHost = $conf->file_host;
        $this->initToken();
    }


    private $token;

    private $expires;


    public function initToken()
    {
        $cache = Redis::getInstance('cache');
        $rs = $cache->get('weixin_mp_token_info');
        if ($rs) {
            $info = explode(' ', $rs);
            if (is_array($info)) {
                $this->token = $info[0];
                $this->expires = (int)$info[1];
            }
        }

        if (empty($this->token)) {
            $this->refreshToken();
        }
    }


    public function refreshToken()
    {
        $rs = $this->curl($this->host . '/token', array(
            'grant_type' => 'client_credential',
            'appid' => $this->appId,
            'secret' => $this->secret,
        ));

        if ($rs) {
            $rs = json_decode($rs, true);
        }

        if (is_array($rs) && empty($rs['errcode'])) {
            $this->token = $rs['access_token'];
            $this->expires = $rs['expires_in'] + time() - 60; //提前1分钟
            Redis::getInstance('cache')->set('weixin_mp_token_info',
                $this->token . ' ' . $this->expires,
                $rs['expires_in'] - 60);
        }
    }

    private $jsApiTicket;

    /**
     * @return mixed
     */
    public function jsApiTicket()
    {
        if (empty($this->jsApiTicket)) {
            $cache = Redis::getInstance('cache');
            $rs = $cache->get('weixin_mp_jsapiticket');
            if ($rs) {
                $this->jsApiTicket = $rs;
            }
        }

        if (empty($this->jsApiTicket) || $this->jsApiTicket['expires'] < time()) {
            $resp = $this->call('/ticket/getticket', ['type' => 'jsapi']);
            if (is_array($resp) && empty($resp['errcode'])) {
                $this->jsApiTicket = [
                    'ticket' => $resp['ticket'],
                    'expires' => time() + $resp['expires_in'] - 30,  //提前30秒
                ];

                Redis::getInstance('cache')->set('weixin_mp_jsapiticket', $this->jsApiTicket);
            }
        }

        return $this->jsApiTicket['ticket'];
    }

    /**
     * @param $url
     * @return array
     */
    public function createJsSig($url)
    {
        $params = [
            'noncestr' => md5(uniqid()),
            'jsapi_ticket' => $this->jsApiTicket(),
            'timestamp' => time(),
            'url' => $url,
        ];

        ksort($params);
        $paramStr = [];
        foreach ($params as $key => $val) {
            $paramStr[] = $key . '=' . $val;
        }
        $params['sig'] = sha1(implode('&', $paramStr));
        return $params;
    }

    /**
     * @param $api
     * @param array|null $query
     * @param array|null $data
     * @param array|null $headers
     * @param bool $media
     * @return mixed
     */
    public function call($api, array $query = null, array $data = null, array $headers = null, $media = false)
    {
        if ($this->expires < time()) {
            $this->refreshToken();
        }
        $query['access_token'] = $this->token;
        $rs = $this->curl($this->host . $api, $query, $data, $headers, $media);

        if ($rs) {
            return json_decode($rs, true);
        }
    }

    /**
     * @param $mediaId
     * @return array
     */
    public function downloadImg($mediaId)
    {
        if ($this->expires < time()) {
            $this->refreshToken();
        }

        $rs = $this->curl($this->fileHost . '/media/get', [
            'access_token' => $this->token,
            'media_id' => $mediaId,
        ]);

        if ($rs) {
            $name = '/tmp/' . $mediaId . '.jpg';
            file_put_contents($name, $rs);
            return [
                'tmp_name' => $name,
                'type' => 'weixin/image',
            ];
        }
    }

    /**
     * @param $url
     * @param array|null $query
     * @param array|null $data
     * @param array|null $headers
     * @param bool $media
     * @return mixed
     */
    private function curl($url, array $query = null, array $data = null, array $headers = null, $media = false)
    {
        $ch = curl_init($url . ($query ? '?' . http_build_query($query) : ''));
        if ($media)
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($data) {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($media) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }

        $rs = curl_exec($ch);
        $msg = '';
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
            $rst = json_decode($rs, true);
            if (is_array($rst) && isset($rst['errcode']) && (int)$rst['errcode'] != 0) {
                $msg = $rst['errcode'] . ' | ' . $rst['errmsg'];
                $ok = false;
            } else {
                $ok = true;
            }

        } else {
            $ok = false;
            $err = json_decode($rs, true);
            if (is_array($err)) {
                $msg = $err['error'];
            } else {
                $msg = $rs;
            }
        }

        if ($msg) {
            Logger::getInstance()->error('weixin mp api: ' . $msg);
            $this->setError(Exception::SERVER_ERROR, 'weixin mp api: ' . $msg);
        }

        curl_close($ch);
        if ($ok) {
            return $rs;
        }
    }

    public function current()
    {
        $result = [];
        $id = \Core\Session::getInstance()->get('wxuser_id');
        if (Application::app()->environ() == ENV_DEV) {
            $id = 5;
        }

        if ($id) {
            $this->getUser($id);
        }

        return $result;
    }

    /**
     * @param $signature
     * @param $timestamp
     * @param $nonce
     * @return bool
     */
    public function weixinCheck($signature, $timestamp, $nonce)
    {
        $token = $this->callback->token;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $headimgurl
     * @param int $timeout
     * @return string
     */
    public function headimg2upyun($headimgurl, $timeout = 3)
    {
        $ch = curl_init();
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        curl_setopt($ch, CURLOPT_URL, $headimgurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($info['http_code'] == 200 && $content) {
            $filename = sha1($content) . '.jpg';
            $name = '/tmp/weixinheadimg/';
            if (!file_exists($name)) {
                mkdir($name, 0755, true);
            }
            $name .= $filename;
            file_put_contents($name, $content);
            File::getInstance()->toUpyun('pyyx-img', $name, $filename, $timeout);
            unlink($name);
            return $filename;
        }
        return '';
    }

    public function query($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $rs = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
            $ok = true;
        } else {
            $ok = false;
            Logger::getInstance()->error('weixin api: ' . $rs);
        }

        curl_close($ch);
        if ($ok) {
            $info = json_decode($rs, true);
            if (empty($info['access_token'])) {
                $info['access_token'] = '';
            }
            return $info;
        }
    }

    public function createUser($params)
    {
        $userInfo = WeixinApi\User::getInstance()->snsUserinfo($params['openid'], $params['access_token']);
        $wxUser = [];
        if (!empty($userInfo) && isset($userInfo['nickname'])) {
            $wxUser['group_id'] = 0;
            $wxUser['unionid'] = $userInfo['unionid'];
            $wxUser['channel_id'] = \WxUserModel::CHANNEL_ACCESS;
            $wxUser['nickname'] = $userInfo['nickname'];
            $wxUser['headimg'] = $userInfo['headimgurl'];
            $wxUser['sex'] = $userInfo['sex'];
            $wxUser['city'] = $userInfo['city'];
            $wxUser['province'] = $userInfo['province'];
            $wxUser['country'] = $userInfo['country'];
            $wxUser['user_id'] = 0;
            $wxUser['is_bind'] = 0;
            $wxUser['access_token'] = empty($params['access_token']) ? '' : $params['access_token'];
            $wxUser['refresh_token'] = empty($params['refresh_token']) ? '' : $params['refresh_token'];
            $wxUser['expires_in'] = empty($params['expires_in']) ? 0 : $params['expires_in'];
            $wxUser['state'] = \WxUserModel::STATE_NORMAL;
            \WxUserModel::getInstance()->save($wxUser);
        }
        return $wxUser;
    }

    public function createUserExtend($params)
    {
        $wxUserExtend = [];
        $wxUserExtend['wx_user_id'] = $params['wx_user_id'];
        $wxUserExtend['unionid'] = $params['unionid'];
        $wxUserExtend['openid'] = $params['openid'];
        $wxUserExtend['subscribe'] = \WxUserExtendModel::SUBSCRIBE_NO;
        $wxUserExtend['appid'] = $params['appid'];
        $wxUserExtend['state'] = \WxUserExtendModel::STATE_NORMAL;

        \WxUserExtendModel::getInstance()->save($wxUserExtend);
    }

    public function updateUser($params, $wxUserId)
    {
        $userInfo = WeixinApi\User::getInstance()->snsUserinfo($params['openid'], $params['access_token']);
        $wxUser = [];
        if (!empty($userInfo) && isset($userInfo['nickname'])) {
            $wxUser['nickname'] = $userInfo['nickname'];
            $wxUser['headimg'] = $userInfo['headimgurl'];
            $wxUser['sex'] = $userInfo['sex'];
            $wxUser['city'] = $userInfo['city'];
            $wxUser['province'] = $userInfo['province'];
            $wxUser['country'] = $userInfo['country'];
            $wxUser['access_token'] = empty($params['access_token']) ? '' : $params['access_token'];
            $wxUser['refresh_token'] = empty($params['refresh_token']) ? '' : $params['refresh_token'];
            $wxUser['expires_in'] = empty($params['expires_in']) ? 0 : $params['expires_in'];
            \WxUserModel::getInstance()->update($wxUser, ['id' => $wxUserId]);
        }
        return $wxUser;
    }

    public function updateUserExtend($params, $where)
    {
        $wxUserExtend = [];
        $wxUserExtend['unionid'] = $params['unionid'];
        $wxUserExtend['openid'] = $params['openid'];
        $wxUserExtend['subscribe'] = \WxUserExtendModel::SUBSCRIBE_NO;
        $wxUserExtend['appid'] = $params['appid'];
        $wxUserExtend['state'] = \WxUserExtendModel::STATE_NORMAL;

        \WxUserExtendModel::getInstance()->update($wxUserExtend, $where);
    }

    public function getUser($id)
    {
        $user = \WxUserModel::getInstance()->findById($id);
        if (!empty($user)) {
            $extend = \WxUserExtendModel::getInstance()->find(['wx_user_id' => $user['id']]);
            $user['extend'] = $extend;
            $user['src'] = 'wx';
        }
        return $user;
    }
}
