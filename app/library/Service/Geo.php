<?php
namespace Service;

use Core\Service;
use Core\Redis;
use Core\Geohash;

class Geo extends Service
{

    //
    // Krasovsky 1940
    //
    // a = 6378245.0, 1/f = 298.3
    const M_A = 6378245.0;
    // b = a * (1 - f)
    // ee = (a^2 - b^2) / a^2;
    const M_EE = 0.00669342162296594323;

    /**
     *
     * @var Geo
     */
    private static $instance;
    private $geohash;
    private $redis;

    /**
     * @return Geo
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
        $this->geohash = new Geohash;
        $this->redis = Redis::getInstance('main');
    }

    //距离单位显示
    public function formatDistance($m)
    {
        $m = $m > 10 ? $m : 10;

        //格式 0.01km
        return round($m / 1000, 2) . 'km';
        /*
        if($m > 1000){
            return round($m/1000, 2).'km';
        }
        //距离单位不显示个位，以5米为最小单位
        return (int)(ceil($m/5)*5).'m';
        */
    }

    /**
     * 格式化gps坐标，生成hash以及gcj wgs的相互转化,和创建时间
     * 如果是来自ios，则认为是wgs坐标系，如果是来自安卓，则认为是gcj坐标系
     *
     * @param float $lat
     * @param float $lng
     * @param float $alt
     * @param string $from
     * @return array
     */
    public function GPSinfo($lat, $lng, $alt, $from)
    {
        if (empty($lat) || empty($lng)) {
            return null;
        }

        if ($from == 'ios') {
            $gcj = $this->WGStoGCJ($lat, $lng);
            $gps['lat'] = $gcj[0];
            $gps['lng'] = $gcj[1];
            $gps['alt'] = $alt;
            $gps['wgs_lat'] = $lat;
            $gps['wgs_lng'] = $lng;
            $gps['wgs_alt'] = $alt;
            $gps['from'] = 'ios';
        } else if ($from == 'android') {
            $wgs = $this->GCJtoWGSExact($lat, $lng);
            $gps['lat'] = $lat;
            $gps['lng'] = $lng;
            $gps['alt'] = $alt;
            $gps['wgs_lat'] = $wgs[0];
            $gps['wgs_lng'] = $wgs[1];
            $gps['wgs_alt'] = $alt;
            $gps['from'] = 'android';
        } else {
            return null;
        }

        $gps['hash'] = $this->geohash->encode($gps['wgs_lat'], $gps['wgs_lng']);
        $gps['created_at'] = time();
        return $gps;
    }

    public function traceUser($user, $gps)
    {
        $uid = $user['id'];
        $key = 'user_gps_trace/' . $uid;
        $last = $this->redis->lGet($key, -1);

        if (empty($gps['lat']) || empty($gps['lng'])) {
            return;
        }

        if ($last) {
            $distance = $this->getDistance(
                $last['wgs_lat'], $last['wgs_lng'],
                $gps['wgs_lat'], $gps['wgs_lng']);

            //没超过200米忽略掉
            if ($distance < 200) {
                return;
            }
        }

        //把用户最新的gps追加到他的足迹列表
        $this->redis->rPush($key, $gps);

        //把用户对应的gps hash放到总的排序列表中
        $this->redis->zAdd('uid_with_gps_hash_list', $gps['hash'], $uid);
    }

    /**
     *
     * @return array
     */
    public function currentOrLastGPS()
    {
        $gps = Device::getInstance()->currentGPS();
        if (!$gps) {
            $user = User::getInstance()->current();
            if ($user) {
                $gps = $this->userLastGPS($user['id']);
            }
        }
        return $gps;
    }

    public function hashSize()
    {
        return $this->redis->zCard('uid_with_gps_hash_list');
    }

    /**
     * 用户最新位置
     * @param $uid
     * @return array
     */
    public function userLastGPS($uid)
    {
        return $this->redis->lGet('user_gps_trace/' . $uid, -1);
    }

    public function traceDevice($device, $gps)
    {
        $key = 'device_gps_trace/' . $device['id'];
        $row = $this->redis->lGet($key, -1);

        if (empty($gps['lat']) || empty($gps['lng'])) {
            return;
        }

        if ($row) {
            $distance = $this->getDistance(
                $row['lat'], $row['lng'], $gps['lat'], $gps['lng']);

            //没超过200米忽略掉
            if ($distance < 200) {
                return;
            }
        }

        $hash = $this->geohash->encode($gps['lat'], $gps['lng']);
        $gps['hash'] = $hash;
        $gps['created_at'] = time();

        $this->redis->rPush($key, $gps);
    }

    public function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $x = ($lat2 - $lat1) * M_PI * self::M_A / 180;
        $y = ($lng2 - $lng1) * M_PI * self::M_A * cos((($lat1 + $lat2) / 2) * M_PI / 180) / 180;

        return hypot($x, $y);
    }

    public function outOfChina($lat, $lng)
    {
        if ($lng < 72.004 || $lng > 137.8347) {
            return true;
        }
        if ($lat < 0.8293 || $lat > 55.8271) {
            return true;
        }
        return false;
    }

    private function transform($x, $y)
    {
        $xy = $x * $y;
        $absX = sqrt(abs($x));
        $d = (20.0 * sin(6.0 * $x * pi()) + 20.0 * sin(2.0 * $x * pi())) * 2.0 / 3.0;
        $lat = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $xy + 0.2 * $absX;
        $lng = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $xy + 0.1 * $absX;
        $lat += $d;
        $lng += $d;
        $lat += (20.0 * sin($y * pi()) + 40.0 * sin($y / 3.0 * pi())) * 2.0 / 3.0;
        $lng += (20.0 * sin($x * pi()) + 40.0 * sin($x / 3.0 * pi())) * 2.0 / 3.0;
        $lat += (160.0 * sin($y / 12.0 * pi()) + 320 * sin($y / 30.0 * pi())) * 2.0 / 3.0;
        $lng += (150.0 * sin($x / 12.0 * pi()) + 300.0 * sin($x / 30.0 * pi())) * 2.0 / 3.0;
        return array($lat, $lng);
    }

    private function delta($lat, $lng)
    {
        list($dLat, $dLng) = self::transform($lng - 105.0, $lat - 35.0);
        $radLat = $lat / 180.0 * pi();
        $magic = sin($radLat);
        $magic = 1 - self::M_EE * $magic * $magic;
        $sqrtMagic = sqrt($magic);
        $dLat = ($dLat * 180.0) / ((self::M_A * (1 - self::M_EE)) / ($magic * $sqrtMagic) * pi());
        $dLng = ($dLng * 180.0) / (self::M_A / $sqrtMagic * cos($radLat) * pi());
        return array($dLat, $dLng);
    }

    // WGStoGCJ convert WGS-84 coordinate(wgsLat, wgsLng) to GCJ-02 coordinate(gcjLat, gcjLng).
    public function WGStoGCJ($wgsLat, $wgsLng)
    {
        if (self::outOfChina($wgsLat, $wgsLng)) {
            list($gcjLat, $gcjLng) = array($wgsLat, $wgsLng);
            return array($gcjLat, $gcjLng);
        }
        list($dLat, $dLng) = $this->delta($wgsLat, $wgsLng);
        list($gcjLat, $gcjLng) = array($wgsLat + $dLat, $wgsLng + $dLng);
        return [$gcjLat, $gcjLng];
    }

    // GCJtoWGS convert GCJ-02 coordinate(gcjLat, gcjLng) to WGS-84 coordinate(wgsLat, wgsLng).
    // The output WGS-84 coordinate's accuracy is 1m to 2m. If you want more exactly result, use GCJtoWGSExact/gcj2wgs_exact.
    public function GCJtoWGS($gcjLat, $gcjLng)
    {
        if ($this->outOfChina($gcjLat, $gcjLng)) {
            list($wgsLat, $wgsLng) = array($gcjLat, $gcjLng);
            return array($wgsLat, $wgsLng);
        }
        list($dLat, $dLng) = self::delta($gcjLat, $gcjLng);
        list($wgsLat, $wgsLng) = array($gcjLat - $dLat, $gcjLng - $dLng);
        return array($wgsLat, $wgsLng);
    }

    // GCJtoWGSExact convert GCJ-02 coordinate(gcjLat, gcjLng) to WGS-84 coordinate(wgsLat, wgsLng).
    // The output WGS-84 coordinate's accuracy is less than 0.5m, but much slower than GCJtoWGS/gcj2wgs.
    public function GCJtoWGSExact($gcjLat, $gcjLng)
    {
        /* const */
        $initDelta = 0.01;
        /* const */
        $threshold = 0.000001;
        // list($tmpLat, $tmpLng) = self::GCJtoWGS($gcjLat, $gcjLng);
        // list($tryLat, $tryLng) = self::WGStoGCJ($tmpLat, $tmpLng);
        // list($dLat, $dLng) = array(abs($tmpLat-$tryLat), abs($tmpLng-$tryLng));
        list($dLat, $dLng) = array($initDelta, $initDelta);
        list($mLat, $mLng) = array($gcjLat - $dLat, $gcjLng - $dLng);
        list($pLat, $pLng) = array($gcjLat + $dLat, $gcjLng + $dLng);
        for ($i = 0; $i < 30; $i++) {
            list($wgsLat, $wgsLng) = array(($mLat + $pLat) / 2, ($mLng + $pLng) / 2);
            list($tmpLat, $tmpLng) = $this->WGStoGCJ($wgsLat, $wgsLng);
            list($dLat, $dLng) = array($tmpLat - $gcjLat, $tmpLng - $gcjLng);
            if (abs($dLat) < $threshold && abs($dLng) < $threshold) {
                // echo("i:", $i);
                return array($wgsLat, $wgsLng);
            }
            if ($dLat > 0) {
                $pLat = $wgsLat;
            } else {
                $mLat = $wgsLat;
            }
            if ($dLng > 0) {
                $pLng = $wgsLng;
            } else {
                $mLng = $wgsLng;
            }
        }
        return array($wgsLat, $wgsLng);
    }

    public function checkGps($banner)
    {
        $banner_gps = \Core\Redis::getInstance('main')->hGet('banner_gps', $banner['id']);
        $ret = true;
        if ($banner_gps && $banner_gps['distance']) {
            //如果banner设置了位置，用户位置未获取到，则不显示
            $user_gps = \Service\Device::getInstance()->currentGPS();
            if (!$user_gps) {
                $ret = false;
            } else {
                //如果不在范围内，不显示
                $cur_distance = Geo::getInstance()->getDistance($banner_gps['lat'], $banner_gps['lng'], $user_gps['lat'], $user_gps['lng']);
                if ($cur_distance > $banner_gps['distance']) {
                    $ret = false;
                }
            }
        }

        return $ret;
    }

}
