<?php

namespace MVuoncino\OpLog\Filters;

use MVuoncino\OpLog\Models\OperationalLog;

class AfterFilter
{
    /**
     * @var OperationalLog
     */
    private $log;

    /**
     * AfterFilter constructor.
     * @param OperationalLog $log
     */
    public function __construct(OperationalLog $log)
    {
        $this->log = $log;
    }

    /**
     * @param $first
     * @param $second
     */
    public function filter($first, $second)
    {
        $this->log->after($first, $second);
    }
}