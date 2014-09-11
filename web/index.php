<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

require __DIR__.'/../config/dev.php';

$app->get('/', function() use ($app) {
    return $app['twig']->render('index.twig');
});

$app->run();
