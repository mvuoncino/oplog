<?php

namespace MVuoncino\OpLog\Models;

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
        fseek($this->tmp, SEEK_END);
        $json = json_encode($record);
        fwrite($this->tmp, $json, strlen($json));
    }

    /**
     * @return \Generator
     */
    public function fetch($count = 1000)
    {
        fseek($this->tmp, 0);
        while (($count--) && !feof($this->tmp)) {
            yield fread($this->tmp, 65525);
        }
    }

    public function __destruct()
    {
        fclose($this->tmp);
    }

}