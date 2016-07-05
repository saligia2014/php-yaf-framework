<?php

namespace Lib\Cliresident;

use Core\Controller;


abstract class BaseCtrlr extends Controller
{

    const SIG_TERMINATE = 1;

    const SIG_CONTINUE = 2;

    protected $timeout = 60;

    protected $reloadInterval = 3600;

    protected $maxExecCount = 1000;

    public function indexAction()
    {
        $start = time();
        $count = 0;
        $this->loopStart();

        while (1) {
            $data = $this->retrieveData();
            $count++;
            if (empty($data)) {
                sleep(1);
            } else {
                $step = $this->consume($data);
                if ($step == self::SIG_TERMINATE) {
                    break;
                }
            }

            if ($this->reloadInterval && $start + $this->reloadInterval < time()) {
                break;
            }

            if ($count > $this->maxExecCount) {
                break;
            }
        }

        $this->loopEnd();
    }

    abstract protected function retrieveData();

    protected function loopStart()
    {

    }

    protected function loopEnd()
    {

    }

    abstract protected function consume($data);
}
