<?php

namespace Core;


abstract class QueueBaseCtrlr extends Controller
{

    const SIG_TERMINATE = 1;

    const SIG_CONTINUE = 2;

    protected $redisId;

    protected $queueName;

    protected $timeout = 60;

    protected $reloadInterval = 3600;

    protected $maxExecCount = 1000;

    public function indexAction()
    {
        $queue = RedisQueue::getInstance($this->redisId);
        $start = time();
        $count = 0;
        $this->loopStart();

        while (1) {
            $data = $queue->pop($this->queueName);
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

    protected function loopStart()
    {

    }

    protected function loopEnd()
    {

    }

    abstract protected function consume($data);
}
