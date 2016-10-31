<?php

use Core\Model;

class TutorialModel extends Model
{
    const STATE_NORMAL = 0;
    const STATE_DEL = 1;

    /**
     * @return TutorialModel
     */
    public static function getInstance()
    {
        return parent::instance('Tutorial');
    }

    public function __construct()
    {
        parent::__construct('tutorial', [
            'id' => Model::COLUMN_INT,
            'wx_user_id' => Model::COLUMN_INT,
            'name' => Model::COLUMN_STRING,
            'cover_img' => Model::COLUMN_STRING,
            'pv' => Model::COLUMN_INT,
            'uv' => Model::COLUMN_INT,
            'like' => Model::COLUMN_INT,
            'tag_num' => Model::COLUMN_INT,
            'type' => Model::COLUMN_INT,
            'state' => Model::COLUMN_INT,
            'created_at' => Model::COLUMN_TIME,
            'updated_at' => Model::COLUMN_TIME,
        ]);
    }
}
