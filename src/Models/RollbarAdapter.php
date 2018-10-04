<?php

namespace MVuoncino\OpLog\OperationalLogging\Models;

use MVuoncino\OpLog\OperationalLogging\Contracts\OperationalLogInterface as OL;
use Monolog\Logger;
use RollbarNotifier;

class RollbarAdapter
{
    /**
     * @var RollbarNotifier $rollbar
     */
    protected $rollbar;

    /**
     * RollbarAdapter constructor.
     * @param RollbarNotifier|null $rollbar
     */
    public function __construct(RollbarNotifier $rollbar = null)
    {
        $this->rollbar = $rollbar;
    }

    /**
     * @param $method
     * @param $endpoint
     * @param array $messages
     * @param array $opContext
     * @param array $logEntries
     */
    public function sendToRollbar($method, $endpoint, array $messages, array $opContext, array $logEntries)
    {
        $level = max($opContext[OL::TAG_OPLEVEL]);
        $areas = implode('][', $opContext[OL::TAG_AREA]);
        $messageString = implode(' and ', $messages);

        $message = sprintf("%s [%s][%s][%s]",
            $messageString, strtoupper($method), $endpoint, $areas
        );

        $context = array_merge(['logs' => $logEntries], $opContext);

        if ($this->rollbar) {
            //$this->rollbar->report_message($message, $level, $context);
            //$this->rollbar->flush();
        } else {
            // this is just for testing so we don't want to send this log message back through
            // the operational log handler at this point.

            /** @var $monolog Logger */
            $monolog = \Log::getMonolog();
            $handlers = $monolog->getHandlers();
            $handlers = array_filter($handlers, function($item) {
                return (!($item instanceof OperationalLogHandler));
            });
            $monolog->setHandlers($handlers);
            \Log::getMonolog()->log($level, '[OPERATIONS]' . $message, $context);
        }
    }

}
