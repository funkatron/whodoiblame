<?php
namespace BlameApp\Lib;

use Resty\Resty;

/**
 *
 */
class GeocodioApi extends Resty
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
        $this->setBaseUrl($app->config('geocodio.api.base'));

        // log to App's monoglog instance
        $this->setLogger(function ($msg) use ($app) {
            if (!is_scalar($msg)) {
                $msg = json_encode($msg);
            }
            $app->log->addDebug("GEOCODIO: {$msg}");
        });

        $this->app = $app;
    }


    public function sendRequest($url, $method = 'GET', $querydata = null, $headers = null, $options = null)
    {
        // always put the API key in the query data array
        $querydata['api_key'] = $this->app->config('geocodio.api.key');
        return parent::sendRequest($url, $method, $querydata, $headers, $options);
    }
}
