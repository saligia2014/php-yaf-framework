<?php

namespace Service;

use Core\Redis;
use Lib\Exception;
use Core\Service;
use Core\RedisQueue;
use Yaf\Registry;

class File extends Service
{

    const TYPE_UNKNOWN = 0;
    const TYPE_IMAGE = 1;
    const TYPE_VOICE = 2;
    const TYPE_VIDEO = 3;
    const TYPE_FILE = 4;

    //attachment type for movie, book, etc.
    const TYPE_MOVIE = 5;
    const TYPE_BOOK = 6;

    const IMAGE_TYPE_JPG = 11;
    const IMAGE_TYPE_GIF = 12;
    const IMAGE_TYPE_PNG = 13;
    const FILE_TYPE_APK = 41;
    const FILE_TYPE_IPA = 42;


    /**
     * @var File
     */
    private static $instance;
    private $conf;
    private $buckets = array(
        self::TYPE_IMAGE => 'pyyx-img',
        self::TYPE_FILE => 'pyyx-file',
    );
    private $exts = array(
        self::IMAGE_TYPE_JPG => 'jpg',
        self::IMAGE_TYPE_PNG => 'png',
        self::IMAGE_TYPE_GIF => 'gif',
        self::FILE_TYPE_APK => 'apk',
        self::FILE_TYPE_IPA => 'ipa',
    );

    /**
     * @return File
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public function __construct()
    {
        $this->conf = Registry::get('conf')->file;
        if (empty($this->conf)) {
            throw new Exception(Exception::SERVER_ERROR, 'file conf not set');
        }
    }

    /**
     * check if file is downloaded and synced to cloud
     * @return string url. if synced
     * @return false
     * @param $url
     */
    public function downloaded($url)
    {
        return Redis::getInstance('main')->hGet('downloaded', md5($url)) ?: false;
    }

    /**
     * 图片等比缩放
     * 如果同时设置了宽高，则按等比缩放至最大
     * 如果仅设置了宽，按最小宽等比缩放（设置值不超过实际值）。反之亦然。
     */
    public function ImageResize($filepath, $width, $height = 0, $dest = null)
    {
        if (!file_exists($filepath))
            return false;

        list($real_w, $real_h, $ratio) = array_values($this->getImageSize($filepath));
        if (!$real_w || !$real_h)
            return false;

        $type = $this->getDetailType($filepath);
        $dest = $dest ?: $filepath;

        //获取图片类型并打开图像资源
        $imagesources = function ($filepath) use ($type) {
            //临时设置内存限制，避免遇到大图时imagecreatefromxxx超出内存
            ini_set('memory_limit', '256M');
            switch ($type) {
                case self::IMAGE_TYPE_GIF:
                    $img = imagecreatefromgif($filepath);
                    break;
                case self::IMAGE_TYPE_JPG:
                    $img = imagecreatefromjpeg($filepath);
                    break;
                case self::IMAGE_TYPE_PNG:
                    $img = imagecreatefrompng($filepath);
                    break;
                default:
                    return false;
            }
            return $img;
        };

        $output = function ($image) use ($type, $dest) {
            switch ($type) {
                case self::IMAGE_TYPE_GIF:
                    $return = imagegif($image, $dest);
                    break;
                case self::IMAGE_TYPE_JPG:
                    $return = imagejpeg($image, $dest);
                    break;
                case self::IMAGE_TYPE_PNG:
                    $return = imagepng($image, $dest);
                    break;
                default:
                    return false;
            }
            return $return;
        };


        $newimg = null;
        if ($width && $height) {
            //real_w > real_h, get height by width
            if ($ratio > 1) {
                //超过实际宽度
                if ($width > $real_w) {
                    goto process;
                }
                $height = round($width / $ratio);
            } else {
                if ($height > $real_h) {
                    goto process;
                }
                //get width by height
                $width = round($height * $ratio);
            }
        } elseif ($width) {
            if ($width > $real_w) {
                goto process;
            }
            $height = round($width / $ratio);
        } else {
            if ($height > $real_h) {
                goto process;
            }
            //get width by height
            $width = round($height * $ratio);
        }


        $image = $imagesources($filepath);
        if (!$image)
            return false;

        $newimg = imagecreatetruecolor($width, $height);

        $tran = imagecolortransparent($image);//处理透明算法
        if ($tran >= 0 && $tran < imagecolorstotal($image)) {
            $tranarr = imagecolorsforindex($image, $tran);
            $newcolor = imagecolorallocate($newimg, $tranarr['red'], $tranarr['green'], $tranarr['blue']);
            imagefill($newimg, 0, 0, $newcolor);
            imagecolortransparent($newimg, $newcolor);
        }

        imagecopyresampled($newimg, $image, 0, 0, 0, 0, $width, $height, $real_w, $real_h);

        process:
        if (is_null($newimg)) {
            if ($dest != $filepath) {
                return @rename($filepath, $dest);
            }
            return true;
        } else {
            return $output($newimg, $dest);
        }
    }

    /**
     * 下载文件到本地 返回本地路径
     *
     * @param $url
     * @return bool|string
     */
    public function downloadFile($url)
    {
        if (!isset(parse_url($url)['host']))
            return false;

        $referer = 'http://' . parse_url($url)['host'];

        //设置Referer避免请求被拒（如 豆瓣）
        if (strpos($url, 'douban')) {
            $referer = 'http://douban.com/';
        }
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.54 Safari/536.5',
            'Cookie' => 'a=b; c=d;',
            'Referer' => $referer,
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => '*/*',
        ];
        $request = \Vendor\BosonNLP\Request::create(array(
            'url' => $url,
            'method' => 'GET',
            'headers' => $headers,
            'timeout' => 100
        ));

        $times = 3;
        $try = 0;
        $fail = true;
        $filepath = sys_get_temp_dir() . '/' . sha1($url . time());
        while ($fail && $try < $times) {
            $response = $request->send();
            if ($response->code != 200) {
                \Core\Logger::getInstance()->error('download file error: ' . $url . ' ' . $response->body());

                $fail = true;
                $return = null;
                $try++;
            } else {
                $fail = false;
                file_put_contents($filepath, $response->body());
            }
        }
        return $fail ? false : $filepath;
    }

    /**
     * 同步url到又拍云
     *
     * @param $url
     * @return array|null
     * @throws Exception
     */
    public function saveUrl($url)
    {
        $redis = \Core\Redis::getInstance('main');

        if ($filename = $this->downloaded($url)) {
            $fullpath = $this->getFullPath($filename) . '/' . $filename;

            if (file_exists($fullpath)) {
                $type = $this->getDetailType($fullpath);
                $size = $this->getImageSize($fullpath);
                return array_merge([
                    'type' => $type,
                    'name' => $filename,
                ], $size);
            } else {
                $redis->hDel('downloaded', md5($url));
            }
        }

        if (!$filepath = $this->downloadFile($url)) {
            throw new Exception(Exception::SERVER_ERROR, 'file download fail');
        }

        $type = $this->getDetailType($filepath);
        if ($type === self::TYPE_UNKNOWN) {
            $this->setError(Exception::FILE_NOT_ALLOWED, "file type is not allowed");
            return null;
        }

        $hash = sha1($url . time());
        $ext = $this->getExtName($type);
        $filename = "$type$hash.$ext";
        if ($this->moveFile($filepath, $filename)) {
            $fullpath = $this->getFullPath($filename) . '/' . $filename;
            //如果尺寸过大，尝试缩小到960px
            $this->ImageResize($fullpath, 960);

            //upload to upyun server, if failed let the job try again
            if (!$this->saveToUpyun($type, $filename)) {
                $hostname = Registry::get('hostname');
                $data = [$type, $filename, 1]; //failed count
                RedisQueue::getInstance()
                    ->push('to_upyun_failed_queue/' . $hostname, $data);
            }

            $size = $this->getImageSize($fullpath);
            $redis->set('file_info/' . $filename, $size);
            $redis->hSet('downloaded', md5($url), $filename);
            return array_merge([
                'type' => $type,
                'name' => $filename,
            ], $size);
        }
        $this->setError(Exception::SERVER_ERROR, 'cannot save upload file');
    }

    public function save(array $file)
    {
        $type = $this->getDetailType($file['tmp_name']);
        if ($type === self::TYPE_UNKNOWN) {
            $this->setError(Exception::FILE_NOT_ALLOWED, "{$file['type']} is not allowed");
            return null;
        }

        $hash = sha1_file($file['tmp_name']);
        $ext = $this->getExtName($type);
        $filename = "$type$hash.$ext";
        if ($this->moveFile($file['tmp_name'], $filename)) {
            //upload to upyun server, if failed let the job try again
            if (!$this->saveToUpyun($type, $filename)) {
                $hostname = Registry::get('hostname');
                $data = [$type, $filename, 1]; //failed count
                RedisQueue::getInstance()
                    ->push('to_upyun_failed_queue/' . $hostname, $data);
            }

            //获取图片尺寸
            $fullpath = $this->getFullPath($filename) . '/' . $filename;
            $size = $this->getImageSize($fullpath);
            $redis = \Core\Redis::getInstance('main');
            $redis->set('file_info/' . $filename, $size);
            return array_merge([
                'type' => $type,
                'name' => $filename,
            ], $size);
        }
        $this->setError(Exception::SERVER_ERROR, 'cannot save upload file');
    }

    /**
     * @param $filepath
     * @return array
     */
    public function getImageSize($filepath)
    {
        try {
            $info = getimagesize($filepath);
            return array(
                'width' => $info[0],
                'height' => $info[1],
                'whratio' => $info[0] > 0 ? ($info[0] / $info[1]) : 0
            );
        } catch (\Exception $e) {
            \Core\Logger::getInstance()->error($e->getMessage());
            return array('width' => 0, 'height' => 0, 'whratio' => 0);
        }
    }

    /**
     * move file to owner's web server
     * @param string $src
     * @param string $filename
     * @return bool
     */
    public function moveFile($src, $filename)
    {
        $path = $this->getFullPath($filename);
        if (file_exists($filename)) {
            return true;
        }
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        return rename($src, $path . '/' . $filename);
    }

    public function getFullPath($filename)
    {
        return $this->conf->upload_path . $this->getPathByName($filename);
    }

    public function getPathByName($filename)
    {
        $paths = [];
        for ($i = 0; $i < 8; $i = $i + 2) {
            $paths[] = $filename[$i] . $filename[$i + 1];
        }
        return '/' . implode('/', $paths);
    }

    /**
     * @param string $filename
     * @return int
     */
    public function getDetailType($filename)
    {
        $finfo = new \finfo();

        while (true) {
            $type = $finfo->file($filename, FILEINFO_MIME_TYPE);
            if ($type === 'application/x-gzip') {
                $this->gunzip($filename);
            } else {
                break;
            }
        }

        switch ($type) {
            case 'image/jpeg':
                return self::IMAGE_TYPE_JPG;

            case 'image/png':
                return self::IMAGE_TYPE_PNG;

            case 'image/gif':
                return self::IMAGE_TYPE_GIF;

            case 'application/java-archive':
                return self::FILE_TYPE_APK;

            case 'application/zip':
                return self::FILE_TYPE_IPA;

            default :
                return self::TYPE_UNKNOWN;
        }
    }

    public function getType($type)
    {
        if (is_numeric($type)) {
            $typeId = (int)$type;
        } else {
            $typeId = $this->getDetailType($type);
        }

        if ($typeId < 10) {
            return self::TYPE_UNKNOWN;
        }

        if ($typeId < 20) {
            return self::TYPE_IMAGE;
        }

        if ($typeId < 30) {
            return self::TYPE_VOICE;
        }

        if ($typeId < 40) {
            return self::TYPE_VIDEO;
        }

        if ($typeId < 50) {
            return self::TYPE_FILE;
        }

        return self::TYPE_UNKNOWN;
    }

    public function getExtName($type)
    {
        if (isset($this->exts[$type])) {
            return $this->exts[$type];
        }
        return '';
    }

    /**
     * 不使用php内置函数(gzdecode)的原因，是处理时会把整个文件读取到内存中
     * 如果文件过大，会占用太多内存
     * 这里必须要保证gunzip命令可用
     *
     * @param string $filename
     */
    private function gunzip($filename)
    {
        rename($filename, $filename . '.gz');
        shell_exec('gunzip ' . $filename . '.gz');
    }

    public function upyunBucketByType($type)
    {
        if ($type >= 10) {
            $type = $this->getType($type);
        }
        if (isset($this->buckets[$type])) {
            return $this->buckets[$type];
        }
    }

    /**
     * upload file to upyun server
     * @param int $type
     * @param string $filename
     * @param int $timeout
     * @return bool
     */
    private function saveToUpyun($type, $filename, $timeout = 20)
    {
        // upYun 空间名称
        $bucket = $this->upyunBucketByType($type);
        if (!$bucket) {
            return false;
        }

        $fullpath = $this->getFullPath($filename) . '/' . $filename;
        return $this->toUpyun($bucket, $fullpath, $filename, $timeout);
    }

    public function getUrl($filename)
    {
        $type = (int)($filename[0] . $filename[1]);
        $buckets = $this->upyunBucketByType($type);
        $conf = $this->conf->upyun->{$buckets};
        return $conf->host . '/' . $filename;
    }

    /**
     * @param $bucket
     * @param $filename
     * @param $upname
     * @param int $timeout
     * @return bool
     */
    public function toUpyun($bucket, $filename, $upname, $timeout = 20)
    {
        $size = filesize($filename);
        $conf = $this->conf->upyun->{$bucket};
        $uri = "/$bucket/$upname";
        $date = gmdate('D, d M Y H:i:s \G\M\T');
        $sign = md5("PUT&$uri&$date&$size&" . md5($conf->account->pass));
        $headers = array(
            'Expect:',
            "Date: $date",
            "Authorization: UpYun {$conf->account->name}:$sign"
        );

        $fp = fopen($filename, 'rb');
        $ch = curl_init($this->conf->upyun->upload_host . $uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, $size);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $rs = curl_exec($ch);
        $ok = false;
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
            $ok = true;
        } else {
            $this->setError(Exception::UPYUN_API_ERROR, 'upyun api: ' . $rs);
        }

        curl_close($ch);
        fclose($fp);
        return $ok;

    }
}
