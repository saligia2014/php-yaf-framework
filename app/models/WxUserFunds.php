<?php

use Core\Model;

class WxUserFundsModel extends Model
{
    const STATE_NORMAL = 0;
    const STATE_DEL = 1;

    const SUBSCRIBE_YES = 1;
    const SUBSCRIBE_NO = 0;

    /**
     * @return WxUserFundsModel
     */
    public static function getInstance()
    {
        return parent::instance('WxUserFunds');
    }

    public function __construct()
    {
        parent::__construct('wx_user_funds', [
            'id' => Model::COLUMN_INT,
            'wx_user_id' => Model::COLUMN_INT,
            'price' => Model::COLUMN_INT,
            'state' => Model::COLUMN_INT,
            'created_at' => Model::COLUMN_TIME,
            'updated_at' => Model::COLUMN_TIME,
        ]);
    }

}

