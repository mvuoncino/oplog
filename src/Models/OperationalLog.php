<?php

namespace MVuoncino\OpLog\Models;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Monolog\Logger;
use MVuoncino\OpLog\Contracts\ExtractorInterface;

class OperationalLog
{
    /**
     * @var RollbarAdapter
     */
    private $rollbarAdapter;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array[]
     */
    private $records;

    /**
     * @var string
     */
    private $method = 'CLI'; // for cronserver, etc.

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string[]
     */
    private $messages;

    /**
     * @var bool
     */
    private $hasError = false;

    /**
     * @var array
     */
    private $opData = [];

    /**
     * @var ExtractorInterface[]
     */
    private $extractors;

    /**
     * OperationalLog constructor.
     * @param $name
     */
    public function __construct(RollbarAdapter $rollbarAdapter, $name)
    {
        $this->rollbarAdapter = $rollbarAdapter;
        $this->name = $name;
    }

    /**
     * @param ExtractorInterface $extractor
     */
    public function pushExtractor(ExtractorInterface $extractor)
    {
        $this->extractors[] = $extractor;
    }

    /**
     * @param array $record
     */
    public function log(array $record)
    {
        $this->records[] = $record;
        $this->mergeOperationalData(
            $this->extractOperationalData($record)
        );
        // the goal of this is to encourage us to fix warnings since, by definition, they mean something
        // wasn't quite right.  If they happen all the time, it's an issue.
        if ($record['level'] >= Logger::WARNING) {
            $this->hasError = true;
        }
        if ($record['level'] >= Logger::ERROR) {
            // note all bona-fide errors in the message text for rollbar
            $this->messages[] = $record['message'];
        }
    }

    /**
     * @param Request $request
     */
    public function before(Request $request)
    {
        // nothing to do here
    }

    /**
     * @param Request $request
     * @param JsonResponse $response
     */
    public function after($request, $response)
    {
        $endpoint = \Route::getCurrentRoute()->getUri();
        $info = parse_url($endpoint);
        $this->endpoint = $info['path'];
        $this->method = $request->getMethod();
        parse_str($info['query'], $queryParts);
        $this->mergeOperationalData(
            $this->extractOperationalData($queryParts)
        );

        if ($response->getStatusCode() >= 400) {
            $this->log([
                'message' => 'Non-200 response returned to user',
                'context' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->path(),
                    'input' => $request->all(),
                    'request_content' => $request->getContent(),
                    'response_content' => $response->getContent(),
                ],
                'level' => Logger::WARNING,
                'level_name' => 'WARNING',
                'channel' => $this->name
            ]);
        }
    }

    /**
     * @return void
     */
    public function finalize()
    {
        if ($this->hasError) {
            if (strpos(php_sapi_name(), 'cli') !== false) {
                $this->endpoint = array_get(\Request::instance()->server(), 'argv.1');
            }
            if (!$this->messages) {
                $this->messages = ['This endpoint had one or more warnings or worse'];
            }
            $this->rollbarAdapter->sendToRollbar($this->method, $this->endpoint, $this->messages, $this->opData, $this->records);
        }
    }

    /**
     * @param array $opData
     */
    protected function mergeOperationalData(array $opData = [])
    {
        foreach ($opData as $opKey => $v) {
            if (array_key_exists($opKey, $this->opData)) {
                $this->opData[$opKey][] = $v;
                $this->opData[$opKey] = array_unique($this->opData[$opKey]);
            } else {
                $this->opData[$opKey] = [$v];
            }
        }
    }

    /**
     * @param array $record
     * @return array
     */
    protected function extractOperationalData(array $record = [])
    {
        $data = [];
        foreach ($this->extractors as $extractor)
        {
            $data = array_merge($data, $extractor->extract($record));
        }
        return $data;
    }
}