<?php

namespace MVuoncino\OpLog\Contracts;

interface StoreInterface
{
    /**
     * Store a record
     * @param array $record
     * @return void
     */
    public function store(array $record);

    /**
     * Get all of the records
     * @return \Generator
     */
    public function fetch($count);
}