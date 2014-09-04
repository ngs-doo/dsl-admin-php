<?php

use Silex\Provider\MonologServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;

require __DIR__.'/prod.php';

$app['debug'] = true;

$client = new \PayUp\RestHttp(
    'http://localhost:9012/',
    'Kupac',
    '70cdfd06-123e-11e4-b568-df40ce187b99');
\NGS\Client\RestHttp::instance($client);
$app['dsl.client'] = $app->share(function() use ($client) {
    return $client;
});
$app['dsl'] = $app->share(function() { return new \PhpDslAdmin\DomainService(); });

$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../var/logs/silex_dev.log',
));

$app->register($p = new WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => __DIR__.'/../var/cache/profiler',
));
$app->mount('/_profiler', $p);
