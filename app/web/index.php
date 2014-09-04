<?php

use Symfony\Component\Debug\Debug;

require_once __DIR__.'/../vendor/autoload.php';

Debug::enable();

$app = new Silex\Application();

require __DIR__.'/../config/dev.php';

$app['crud.controller'] = $app->share(function() use ($app) {
    return new \PhpDslAdmin\CrudController($app);
});
$crudProvider = new \PhpDslAdmin\CrudControllerProvider();
$routes = $crudProvider->connect($app);
$app->mount('/crud', $routes);

$app->get('/', function() use ($app) {
    return $app['twig']->render('index.twig');
});
$app->get('/crud', function() use ($app) {
    return $app['twig']->render('index.twig');
});

\Symfony\Component\HttpFoundation\Request::enableHttpMethodParameterOverride();

$app->run();
