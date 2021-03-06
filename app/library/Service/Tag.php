<?php

namespace Service;

use Yaf\Application;
use Yaf\Registry;
use Core\Service;
use Core\Logger;
use Core\Redis;
use Lib\Exception;


/**
 * 标签
 */
class Tag extends Service
{

    /**
     *
     * @var Tag
     */
    private static $instance;


    /**
     * @return Tag
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public function create($tagName, $tutorialId)
    {
        $tag = \TagModel::getInstance()->find(['name' => $tagName]);
        if (!$tag) {
            $data['tag_name'] = $tagName;
            $data['tutorial_num'] = 0;
            $data['state'] = \TagModel::STATE_NORMAL;
            $tagId = \TagModel::getInstance()->insert($data);
        } else {
            $tagId = $tag['id'];
        }
        return $tagId;
    }

    public function del($tagId)
    {
        \TagModel::getInstance()->update(['state' => \TagModel::STATE_DEL], ['id' => $tagId]);
    }

    public function tutorialNum($tagId)
    {
        if ($tagId) {
            $num = \TagModel::getInstance()->update(['tutorial_num' => ['++' => 1]], ['id' => $tagId]);
        } else {
            $num = 0;
        }

        return $num;
    }

    public function search($tagName)
    {
        $tags = \TagModel::getInstance()->findAll(['name' => ['like' => $tagName], 'state' => \TagModel::STATE_NORMAL]);

        return $tags;
    }
}
