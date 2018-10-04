<?php

namespace MVuoncino\OpLog\OperationalLogging\Contracts;

interface OperationalLogInterface
{
    /**
     * The psr-compatible error log level for the message
     */
    const TAG_OPLEVEL = 'op:level';

    /**
     * The generic name for the area of the code where the message has occurred
     */
    const TAG_AREA = 'op:area';
}