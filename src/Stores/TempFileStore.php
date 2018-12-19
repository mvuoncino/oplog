<?php

namespace MVuoncino\OpLog\Stores;

use MVuoncino\OpLog\Contracts\StoreInterface;

class TempFileStore implements StoreInterface
{
    /**
     * @var resource
     */
    private $tmp;

    /**
     * TempFileStore constructor.
     */
    public function __construct()
    {
        $this->tmp = tmpfile();
    }

    /**
     * @param array $record
     */
    public function store(array $record)
    {
        fseek($this->tmp, 0, SEEK_END);
        $json = json_encode($record) . PHP_EOL;
        fwrite($this->tmp, $json, strlen($json));
    }

    /**
     * @return \Generator
     */
    public function fetch($count = 1000)
    {
        fseek($this->tmp, 0);
        while (($count--) && !feof($this->tmp)) {
            $json = fgets($this->tmp, 65525);
            $record = json_decode($json, true);
            yield $record;
        }
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        fclose($this->tmp);
    }

}