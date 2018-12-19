<?php

namespace MVuoncino\OpLog\Tests;

use Illuminate\Http\Request;
use Monolog\Logger;
use MVuoncino\OpLog\Contracts\ExtractorInterface;
use MVuoncino\OpLog\Contracts\OperationalLogInterface;
use MVuoncino\OpLog\Contracts\StoreInterface;
use MVuoncino\OpLog\Models\OperationalLog;
use MVuoncino\OpLog\Models\RollbarAdapter;
use PHPUnit\Framework\TestCase;
use Mockery as M;

class OperationalLogTest extends TestCase
{
    public function testModel()
    {
        $g = function() {
            yield 'test1';
            yield 'test2';
            yield 'test3';
        };

        $mockReq = M::mock(Request::class);
        $mockReq->shouldReceive('server')->andReturn([]);

        $mockStore = M::mock(StoreInterface::class);
        $mockStore->shouldReceive('store')->times(5)->andReturnUndefined();
        $mockStore->shouldReceive('fetch')->andReturn($g);

        $mockRollbar = M::mock(RollbarAdapter::class);
        $mockRollbar->shouldReceive('sendToRollbar')->with('CLI', null, 'mike1 and mike2 and mike3', M::any(), [])->once()->andReturnUndefined();

        $mockExtractor = M::mock(ExtractorInterface::class);
        $mockExtractor->shouldReceive('extract')->andReturn([OperationalLogInterface::TAG_AREA => 'test']);

        $opLog = new OperationalLog(
            $mockReq,
            $mockStore,
            $mockRollbar,
            'test'
        );
        $opLog->pushExtractor($mockExtractor);

        $opLog->log(['message' => 'mike1', 'context' => ['mike' => 'mike'], 'level' => Logger::WARNING]);
        $opLog->log(['message' => 'mike1', 'context' => ['mike' => 'mike'], 'level' => Logger::WARNING]);
        $opLog->log(['message' => 'mike1', 'context' => ['mike' => 'mike'], 'level' => Logger::WARNING]);
        $opLog->log(['message' => 'mike2', 'context' => ['mike' => 'mike'], 'level' => Logger::WARNING]);
        $opLog->log(['message' => 'mike3', 'context' => ['mike' => 'mike'], 'level' => Logger::WARNING]);

        $opLog->finalize();

        M::close();
    }
}