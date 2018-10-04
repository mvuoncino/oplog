<?php

namespace MVuoncino\OpLog\Filters;

use MVuoncino\OpLog\Models\OperationalLog;

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