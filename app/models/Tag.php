<?php

use Core\Model;

class TagModel extends Model
{
    const STATE_NORMAL = 0;
    const STATE_DEL = 1;

    /**
     * @return TagModel
     */
    public static function getInstance()
    {
        return parent::instance('Tag');
    }

    public function __construct()
    {
        parent::__construct('tag', [
            'id' => Model::COLUMN_INT,
            'name' => Model::COLUMN_STRING,
            'tutorial_num' => Model::COLUMN_INT,
            'state' => Model::COLUMN_INT,
            'created_at' => Model::COLUMN_TIME,
            'updated_at' => Model::COLUMN_TIME,
        ]);
    }
}
