<?php

use Core\Model;

class TutorialLikeModel extends Model
{
    const STATE_NORMAL = 0;
    const STATE_DEL = 1;

    /**
     * @return TutorialLikeModel
     */
    public static function getInstance()
    {
        return parent::instance('TutorialLike');
    }

    public function __construct()
    {
        parent::__construct('tutorial_like', [
            'id' => Model::COLUMN_INT,
            'wx_user_id' => Model::COLUMN_INT,
            'like' => Model::COLUMN_INT,
            'reward' => Model::COLUMN_INT,
            'state' => Model::COLUMN_INT,
            'created_at' => Model::COLUMN_TIME,
            'updated_at' => Model::COLUMN_TIME,
        ]);
    }
}
