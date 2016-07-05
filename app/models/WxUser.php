<?php

use Core\Model;

class WxUserModel extends Model
{
    const STATE_NORMAL = 0;
    const STATE_DEL = 1;

    const CHANNEL_ACCESS = 0; //授权登录创建

    /**
     * @return WxUserModel
     */
    public static function getInstance()
    {
        return parent::instance('WxUser');
    }

    public function __construct()
    {
        parent::__construct('wx_user', [
            'id' => Model::COLUMN_INT,
            'group_id' => Model::COLUMN_INT,
            'channel_id' => Model::COLUMN_INT,
            'unionid' => Model::COLUMN_STRING,
            'nickname' => Model::COLUMN_STRING,
            'headimg' => Model::COLUMN_STRING,
            'sex' => Model::COLUMN_INT,
            'city' => Model::COLUMN_STRING,
            'province' => Model::COLUMN_STRING,
            'country' => Model::COLUMN_STRING,
            'user_id' => Model::COLUMN_INT,
            'is_bind' => Model::COLUMN_INT,
            'access_token' => Model::COLUMN_STRING,
            'refresh_token' => Model::COLUMN_STRING,
            'expires_in' => Model::COLUMN_INT,
            'state' => Model::COLUMN_INT,
            'created_at' => Model::COLUMN_TIME,
            'updated_at' => Model::COLUMN_TIME,
        ]);
    }

}


//CREATE TABLE `wx_user` (
//`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
//  `group_id` int(11) NOT NULL COMMENT '分组ID',
//  `unionid` varchar(100) NOT NULL COMMENT 'unionid',
//  `channel_id` int(11) NOT NULL DEFAULT '1' COMMENT '渠道编号',
//  `nickname` varchar(200) NOT NULL DEFAULT '' COMMENT '昵称',
//  `headimg` varchar(200) NOT NULL COMMENT '头像',
//  `sex` tinyint(4) NOT NULL COMMENT '性别',
//  `city` varchar(50) NOT NULL DEFAULT '' COMMENT '城市',
//  `province` varchar(50) NOT NULL DEFAULT '' COMMENT '省份',
//  `country` varchar(50) NOT NULL DEFAULT '' COMMENT '国家',
//  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
//  `is_bind` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否绑定用户',
//  `access_token` varchar(200) NOT NULL,
//  `refresh_token` varchar(200) NOT NULL,
//  `expires_in` int(11) NOT NULL,
//  `state` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态',
//  `created_at` datetime NOT NULL COMMENT '创建时间',
//  `updated_at` datetime NOT NULL COMMENT '修改时间',
//  PRIMARY KEY (`id`),
//  UNIQUE KEY `unionid` (`unionid`)
//) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;