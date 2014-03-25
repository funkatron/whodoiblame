<?php

namespace BlameApp\Lib;

use \Memcached;

class Cache
{

    public function __construct(\Slim\Slim $app)
    {
        $this->app = $app;
        $this->m = new Memcached();
        $this->m->addServer(
                            $this->app->config('memcached.server'),
                            $this->app->config('memcached.port')
                            );
    }

    public function get($key)
    {
        $val = $this->m->get($key);
        $this->app->log->addDebug("GETTING {$key}:" . json_encode($val));
        if (!$val && $this->m->getResultCode() == Memcached::RES_NOTFOUND) {
            return false;
        }
        return $val;
    }

    public function set($key, $val, $expire = null)
    {
        if (empty($expire)) {
            $expire = strtotime($this->app->config('memcached.default.expriation'));
        }
        $this->app->log->addDebug("SETTING {$key}:" . json_encode($val) . " Expires in " . ($expire - time()) . " seconds");
        $rs = $this->m->set($key, $val, $expire);

        if ($rs === false) {
            $this->app->log->addError("Saving to memcached FAILED");
        }
        return $rs;
    }
}