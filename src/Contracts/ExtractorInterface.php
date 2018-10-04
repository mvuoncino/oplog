<?php

namespace MVuoncino\OpLog\Contracts;

interface ExtractorInterface
{
    /**
     * Take a record that gets written to the log and attempt to extract as much data as possible out of it
     * @param array $record
     * @return array
     */
    public function extract(array $record = []);
}