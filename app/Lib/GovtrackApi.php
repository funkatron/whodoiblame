<?php
namespace BlameApp\Lib;

use Resty\Resty;

/**
 *
 */
class GovtrackApi extends Resty
{
    public function __construct(\Slim\Slim $app)
    {
        $opts = [
            'silence_fopen_warning' => true,
            'raise_fopen_exception' => true,
            'supports_patch' => true,
            'json_to_array' => true,
        ];

        parent::__construct($opts);

        $this->debug($app->config('resty.debug'));
        $this->setBaseUrl($app->config('govtrack.api.base'));

        // log to App's monoglog instance
        $this->setLogger(function ($msg) use ($app) {
            if (!is_scalar($msg)) {
                $msg = json_encode($msg);
            }
            $app->log->addDebug("GOVTRACK: {$msg}");
        });

        $this->app = $app;
    }
}
