<?php
/**
 * Created by PhpStorm.
 * User: RoyDong
 * Date: 4/1/16
 * Time: 12:02 PM
 */


namespace Service;

require_once APP_PATH . 'vendor/autoload.php';

use Core\Redis;
use Core\Service;
use Yaf\Config\Ini;
use Yaf\Registry;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Qiniu extends Service
{

    const IMG_UPTOKEN_CACHE_KEY = 'qiniu_img_upload_token';

    const VIDEO_UPTOKEN_CACHE_KEY = 'qiniu_video_upload_token';

    /**
     * @var Qiniu
     */
    private static $instance;

    /**
     * @return Qiniu
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new static;
        }
        return self::$instance;
    }

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var UploadManager
     */
    private $uploadManager;

    /**
     * @var Ini
     */
    private $conf;

    public function __construct()
    {
        $this->conf = Registry::get('conf')->qiniu;
        $this->auth = new Auth($this->conf->access_key, $this->conf->secret_key);
        $this->uploadManager = new UploadManager();
    }

    public function imgUploadToken()
    {
        $redis = Redis::getInstance('cache');
        $token = $redis->get(self::IMG_UPTOKEN_CACHE_KEY);
        if (empty($token)) {
            $token = $this->auth->uploadToken($this->conf->bucket->img);
            $redis->set(self::IMG_UPTOKEN_CACHE_KEY, $token, 3500);
        }
        return $token;
    }

    public function videoUploadToken()
    {
        $redis = Redis::getInstance('cache');
        $token = $redis->get(self::VIDEO_UPTOKEN_CACHE_KEY);
        if (empty($token)) {
            $token = $this->auth->uploadToken($this->conf->bucket->video);
            $redis->set(self::VIDEO_UPTOKEN_CACHE_KEY, $token, 3500);
        }
        return $token;
    }

    public function imgUrl($name)
    {
        return $this->conf->host->img . '/' . $name;
    }
    
    public function videoUrl($name)
    {
        return $this->conf->host->video . '/' . $name;
    }

    public function videoCoverImgUrl($name, $second = 5)
    {
        return $this->conf->host->video . '/' . $name . '?vframe/jpg/offset/' . $second;
    }

    public function getUploadManager()
    {
        return $this->uploadManager;
    }
}

