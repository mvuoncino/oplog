<?php

namespace MVuoncino\OpLog\Support;

use App;
use Config;
use Illuminate\Support\ServiceProvider;
use Log;
use MVuoncino\OpLog\Filters\AfterFilter;
use MVuoncino\OpLog\Filters\BeforeFilter;
use MVuoncino\OpLog\Models\OperationalLog;
use MVuoncino\OpLog\Models\OperationalLogHandler;
use MVuoncino\OpLog\Models\RollbarAdapter;
use RollbarNotifier;

class OperationalLoggingServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot()
    {
        $this->package('mvuoncino/oplog');
        if (\Config::get('oplog::enabled', false)) {
            $opLog = self::getOperationalLogHandler();
            Log::getMonolog()->pushHandler($opLog);

            App::before(BeforeFilter::class);
            App::after(AfterFilter::class);
        }
    }

    public function register()
    {
        $this->app->singleton(
            OperationalLog::class,
            function ($app, $params) {
                $opLog = new OperationalLog(
                    $app->make(RollbarAdapter::class),
                    $this->app['env']
                );
                $extractors = \Config::get('oplog::extractors');
                foreach ($extractors as $extractor) {
                    $obj = $app->make($extractor);
                    $opLog->pushExtractor($obj);
                }
                $exclusions = \Config::get('oplog::exclude_from_filter');
                foreach ($exclusions as $route => $exclusion) {
                    $opLog->addExclusion($route, $exclusion);
                }
                $parser = \Config::get('oplog::response_parser', null);
                if ($parser) {
                    $opLog->setResponseParser(\App::make($parser));
                }
                App::shutdown(
                    function() use ($opLog) {
                        $opLog->finalize();
                    }
                );
                return $opLog;
            }
        );

        $this->app->singleton(
            RollbarAdapter::class,
            function ($app, $params) {
                if (Config::get('oplog::rollbar.enabled', false)) {
                    /**
                     * @var App $app
                     */
                    // Default configuration.
                    $defaults = [
                        'environment' => App::environment(),
                        'root' => base_path(),
                    ];

                    $config = array_merge($defaults, Config::get('oplog::rollbar.access_token', []));
                    $config['access_token'] = getenv('OPERATIONS_ROLLBAR_TOKEN') ?: Config::get('operations.rollbar.access_token');

                    if (empty($config['access_token'])) {
                        throw new \InvalidArgumentException('Rollbar access token not configured');
                    }

                    $rollbar = new RollbarNotifier($config);
                } else {
                    $rollbar = null;
                }


                return new RollbarAdapter($rollbar);
            }
        );
    }

    /**
     * @return OperationalLogHandler
     */
    public static function getOperationalLogHandler()
    {
        return App::make(OperationalLogHandler::class);
    }

}
