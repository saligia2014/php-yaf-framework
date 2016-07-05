<?php
use Lib\Cliresident\BaseCtrlr;

class DemoController extends BaseCtrlr
{
    protected function retrieveData()
    {
        return [];
    }

    protected function consume($data)
    {
        return self::SIG_CONTINUE;
    }
}

