<?php

$app->register(new \Silex\Provider\ServiceControllerServiceProvider());
$app->register(new \Silex\Provider\TwigServiceProvider());
$app->register(new \Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new \Silex\Provider\FormServiceProvider());
$app->register(new \Silex\Provider\ValidatorServiceProvider());
$app->register(new \Silex\Provider\TranslationServiceProvider());
$app->register(new \Silex\Provider\SessionServiceProvider());
$app['message'] = $app->share(function() use ($app) {
    return new \NGS\Service\MessageService($app['session']);
});

$app['dsl.source.forms'] = function() { return require __DIR__.'/../Generated-PHP-UI/ModulesForms.php'; };
$app['ngs.form.typemap'] = $app->share(function() use ($app) {
    return array_merge(
        $app['dsl.source.forms'],
        array(
            'ngs_bytestream'    => 'NGS\Symfony\Form\Type\BytestreamType',
            'ngs_checkbox'      => 'NGS\Symfony\Form\Type\CheckboxType',
            'ngs_collection'    => 'NGS\Symfony\Form\Type\CollectionType',
            'ngs_decimal'       => 'NGS\Symfony\Form\Type\DecimalType',
            'ngs_uuid'          => 'NGS\Symfony\Form\Type\UUIDType',
            'ngs_integer'       => 'NGS\Symfony\Form\Type\IntegerType',
            'ngs_localdate'     => 'NGS\Symfony\Form\Type\LocalDateType',
            'ngs_lookup'        => 'NGS\Symfony\Form\Type\LookupType',
            'ngs_money'         => 'NGS\Symfony\Form\Type\MoneyType',
            'ngs_reference'     => 'NGS\Symfony\Form\Type\ReferenceType',
            'ngs_text'          => 'NGS\Symfony\Form\Type\TextType',
            'ngs_timestamp'     => 'NGS\Symfony\Form\Type\TimestampType',
        ));
});
$app['form.extensions'] = $app->share($app->extend('form.extensions', function ($extensions) use ($app) {
    $extensions[] = new \NGS\Symfony\Form\FormExtension($app['ngs.form.typemap']);
    return $extensions;
}));

$app['twig.path'] = array(
    __DIR__.'/../templates',
    __DIR__.'/../Generated-PHP-UI',
    __DIR__.'/../vendor/dsl-platform/dsl-admin-php/templates',
);

$app['twig.loader.filesystem'] = $app->share(function ($app) {
    $fs = new \Twig_Loader_Filesystem($app['twig.path']);
    // @todo namespaced twig paths
    // $fs->addPath(__DIR__.'/../vendor/dsl-platform/dsl-admin-php/templates', 'dsl_admin');
    return $fs;
});
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

\Symfony\Component\HttpFoundation\Request::enableHttpMethodParameterOverride();

$app['crud.controller'] = $app->share(function() use ($app) {
    $controller = new \PhpDslAdmin\CrudController($app);
    $controller->setTwigNamespace('');
    return $controller;
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
