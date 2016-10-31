<?php

use Core\Model;

class TutorialHasTagModel extends Model
{
    const STATE_NORMAL = 0;
    const STATE_DEL = 1;

    /**
     * @return TutorialHasTagModel
     */
    public static function getInstance()
    {
        return parent::instance('TutorialHasTag');
    }

    public function __construct()
    {
        parent::__construct('tutorial_has_tag', [
            'id' => Model::COLUMN_INT,
            'tutorial_id' => Model::COLUMN_INT,
            'tag_id' => Model::COLUMN_INT,
            'state' => Model::COLUMN_INT,
            'created_at' => Model::COLUMN_TIME,
            'updated_at' => Model::COLUMN_TIME,
        ]);
    }
}
