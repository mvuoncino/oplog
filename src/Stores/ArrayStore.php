<?php

namespace MVuoncino\OpLog\Stores;

use MVuoncino\OpLog\Contracts\StoreInterface;

class ArrayStore implements StoreInterface
{
    /**
     * @var resource
     */
    private $array = [];

    /**
     * @param array $record
     */
    public function store(array $record)
    {
        $this->array[] = $record;
    }

    /**
     * @return \Generator
     */
    public function fetch($count = 1000)
    {
        for ($i = 0; $i < min($count, count($this->array)); ++$i) {
            yield $this->array[$i];
        }
    }
}