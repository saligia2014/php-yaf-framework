<?php

use Core\Model;

class StepContentModel extends Model
{
    const STATE_NORMAL = 0;
    const STATE_DEL = 1;

    /**
     * @return StepContentModel
     */
    public static function getInstance()
    {
        return parent::instance('StepContent');
    }

    public function __construct()
    {
        parent::__construct('step_content', [
            'id' => Model::COLUMN_INT,
            'content' => Model::COLUMN_STRING,
            'state' => Model::COLUMN_INT,
            'created_at' => Model::COLUMN_TIME,
            'updated_at' => Model::COLUMN_TIME,
        ]);
    }
}
