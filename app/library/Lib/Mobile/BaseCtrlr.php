<?php

namespace Lib\Mobile;

use Core\Controller;
use Service\Weixin;
use Lib\Exception;


class BaseCtrlr extends Controller
{


    /**
     * get current login user, if not throw an Exception
     *
     * @return array Wxuser;
     * @throws Exception User not login
     */
    protected function getWxuser()
    {
        $user = Weixin::getInstance()->current();
        if (!$user) {
            throw new Exception(Exception::USER_NOT_LOGIN, 'user not login');
        }
        $user['src'] = 'wx';
        return $user;
    }
}
