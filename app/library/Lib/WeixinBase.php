<?php
namespace Lib;

use Core\Restful;
use Service\Weixin;

/**
 * Created by PhpStorm.
 * User: saligia
 * Date: 16/8/22
 * Time: 10:33
 */
abstract class WeixinBase extends Restful
{
    public function getUser()
    {
        $weixinUser = Weixin::getInstance()->current();
        return $weixinUser;
    }
}