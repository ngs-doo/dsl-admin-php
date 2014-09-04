<?php

// phpdsladmin can be run in two modes:
// 1) served as standalone application
// 2) embedded inside another php application

error_reporting(-1);

// SetEnv PHPDSLADMIN_EMBEDDED
$isEmbedded = (defined('PHPDSLADMIN_EMBEDDED') && PHPDSLADMIN_EMBEDDED)
    || (isset($_SERVER['PHPDSLADMIN_EMBEDDED']) && $_SERVER['PHPDSLADMIN_EMBEDDED']);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new \NGS\Silex\Application();

require __DIR__ . '/../config/dev.php';

if (!$isEmbedded) {
    require __DIR__.'/../src/init_project.php';
    require __DIR__.'/../src/init_formtypes.php';
    require __DIR__.'/../src/init_services.php';
}

require __DIR__.'/../src/controllers/basic.php';
require __DIR__.'/../src/controllers/rest.php';

if (!$isEmbedded) {
    $app->run();
}
