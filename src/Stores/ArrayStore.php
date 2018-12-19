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
        $limiter = min($count, count($this->array));
        for ($i = 0; $i < $limiter; ++$i) {
            yield $this->array[$i];
        }
    }
}