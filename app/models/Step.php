<?php

use Core\Model;

class StepModel extends Model
{
    const STATE_NORMAL = 0;
    const STATE_DEL = 1;
    
    /**
     * @return StepModel
     */
    public static function getInstance()
    {
        return parent::instance('Step');
    }

    public function __construct()
    {
        parent::__construct('step', [
            'id' => Model::COLUMN_INT,
            'tutorial_id' => Model::COLUMN_INT,
            'name' => Model::COLUMN_STRING,
            'source' => Model::COLUMN_INT,
            'type' => Model::COLUMN_INT,
            'sort' => Model::COLUMN_INT,
            'state' => Model::COLUMN_INT,
            'created_at' => Model::COLUMN_TIME,
            'updated_at' => Model::COLUMN_TIME,
        ]);
    }
}
