<?php

namespace MVuoncino\OpLog\Models;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Monolog\Logger;
use MVuoncino\OpLog\Contracts\ExtractorInterface;
use MVuoncino\OpLog\Contracts\OperationalLogInterface;
use MVuoncino\OpLog\Contracts\ResponseParserInterface;
use Psr\Log\LogLevel;

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
     * @var string[][]
     */
    private $messages;

    /**
     * @var array
     */
    private $opData = [];

    /**
     * @var ExtractorInterface[]
     */
    private $extractors;

    /**
     * @var ResponseParserInterface
     */
    private $responseParser;

    /**
     * @var array
     */
    private $exclusions;

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
     * @param array $exclusionConfig
     */
    public function addExclusion($route, array $exclusionConfig)
    {
        $this->exclusions[$route] = $exclusionConfig;
    }

    /**
     * @param ResponseParserInterface $responseParser
     */
    public function setResponseParser(ResponseParserInterface $responseParser)
    {
        $this->responseParser = $responseParser;
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
        if (array_key_exists(OperationalLogInterface::TAG_OPLEVEL, $record['context'])) {
            $this->messages[$record['context'][OperationalLogInterface::TAG_OPLEVEL]][] = $record['message'];
        }
        if ($record['level'] >= Logger::WARNING) {
            $this->messages[$record['level']][] = $record['message'];
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

        if (
            ($response->getStatusCode() >= 400) &&
            (
                !array_key_exists($this->endpoint, $this->exclusions) ||
                !in_array($response->getStatusCode(), $this->exclusions[$this->endpoint])
            )
        ) {

            $message = $this->responseParser ? $this->responseParser->parse($response) : 'Non-200 response returned to user';

            $this->log([
                'message' => sprintf('[HTTP:%d] %s', $response->getStatusCode(), $message),
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
        if ($message = self::coalesceMessages($this->messages)) {
            if (strpos(php_sapi_name(), 'cli') !== false) {
                $this->endpoint = array_get(\Request::instance()->server(), 'argv.1');
            }
            $this->rollbarAdapter->sendToRollbar($this->method, $this->endpoint, $message, $this->opData, $this->records);
        }
    }

    /**
     * @param array $messages[][]
     * @return string|null
     */
    protected static function coalesceMessages(array $messages)
    {
        foreach ([Logger::EMERGENCY, Logger::ALERT, Logger::CRITICAL, Logger::ERROR, Logger::WARNING] as $logLevel) {
            if (count($messages[$logLevel]) == 1) {
                return $messages[$logLevel];
            } else if (count($messages[$logLevel]) > 0) {
                return implode($messages[$logLevel], ' and ');
            }
        }
        return null;
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