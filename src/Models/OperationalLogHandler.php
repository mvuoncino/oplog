<?php

namespace MVuoncino\OpLog\OperationalLogging\Models;

use Monolog\Handler\AbstractProcessingHandler;

class OperationalLogHandler extends AbstractProcessingHandler
{
    /**
     * @var OperationalLog
     */
    private $log;

    /**
     * OperationalLogHandler constructor.
     * @param OperationalLog $log
     */
    public function __construct(OperationalLog $log)
    {
        parent::__construct();
        $this->log = $log;
    }

    /**
     * @param array $record
     */
    protected function write(array $record)
    {
        $this->log->log($record);
    }
}