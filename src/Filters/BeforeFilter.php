<?php

namespace MVuoncino\OpLog\OperationalLogging\Filters;

use MVuoncino\OpLog\OperationalLogging\Models\OperationalLog;

class BeforeFilter
{
    /**
     * @var OperationalLog
     */
    private $log;

    /**
     * BeforeFilter constructor.
     * @param OperationalLog $log
     */
    public function __construct(OperationalLog $log)
    {
        $this->log = $log;
    }

    /**
     * @param $request
     */
    public function filter($request)
    {
        $this->log->before($request);
    }
}