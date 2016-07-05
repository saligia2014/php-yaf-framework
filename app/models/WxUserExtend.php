<?php

use Core\Model;

class WxUserExtendModel extends Model
{
    const STATE_NORMAL = 0;
    const STATE_DEL = 1;

    const SUBSCRIBE_YES = 1;
    const SUBSCRIBE_NO = 0;

    /**
     * @return WxUserExtendModel
     */
    public static function getInstance()
    {
        return parent::instance('WxUserExtend');
    }

    public function __construct()
    {
        parent::__construct('wx_user_extend', [
            'id' => Model::COLUMN_INT,
            'wx_user_id' => Model::COLUMN_INT,
            'unionid' => Model::COLUMN_STRING,
            'openid' => Model::COLUMN_STRING,
            'subscribe' => Model::COLUMN_INT,
            'appid' => Model::COLUMN_STRING,
            'state' => Model::COLUMN_INT,
            'created_at' => Model::COLUMN_TIME,
            'updated_at' => Model::COLUMN_TIME,
        ]);
    }

}


//CREATE TABLE `wx_user_extend` (
//`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
//  `wx_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '微信用户ID',
//  `unionid` varchar(100) NOT NULL DEFAULT '' COMMENT '开放平台',
//  `openid` varchar(100) NOT NULL DEFAULT '' COMMENT '公众平台',
//  `subscribe` tinyint(4) NOT NULL DEFAULT '0' COMMENT '关注公众号',
//  `appid` varchar(100) NOT NULL DEFAULT '' COMMENT 'APP 类别',
//  `state` tinyint(4) NOT NULL COMMENT '状态',
//  `created_at` datetime NOT NULL COMMENT '创建时间',
//  `updated_at` datetime NOT NULL COMMENT '修改时间',
//  PRIMARY KEY (`id`),
//  KEY `wx_user_id` (`wx_user_id`),
//  KEY `unionid` (`unionid`),
//  KEY `openid` (`openid`)
//) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;