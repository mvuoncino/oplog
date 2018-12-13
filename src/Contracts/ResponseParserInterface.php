<?php

namespace MVuoncino\OpLog\Contracts;

use Illuminate\Http\JsonResponse;

interface ResponseParserInterface
{
    /**
     * Takes a JSON response and parses out a single, one-line error message appropriate to show in the log
     * @param JsonResponse $response
     * @return mixed
     */
    public function parse(JsonResponse $response);
}