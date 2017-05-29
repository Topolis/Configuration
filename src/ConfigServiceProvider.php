<?php

namespace Topolis\Configuration;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;

class ConfigServiceProvider implements ServiceProviderInterface
{
    protected $config;

    public function __construct($config){
        $this->config = $config;
    }

    public function register(Container $app){

        $config = $this->config;

        $app['config'] = function ($app) use ($config) {
            return new Configuration($config);
        };
    }

    public function boot(Application $app){
    }
}