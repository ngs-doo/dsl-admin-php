<?php

namespace PhpDslAdmin;

use Composer\Script\Event;
use PhpDslAdmin\Install\CompilerClient;
use PhpDslAdmin\Install\Config;
use PhpDslAdmin\Install\IOWrapper;
use PhpDslAdmin\Install\RevenjInstaller;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class Installer
{
    public static function getDefaults()
    {
        return array(
            Config::DSL_PASSWORD => null,
            Config::DSL_PATH => 'dsl',
            Config::DB_DATABASE => 'skeleton',
            Config::DB_USERNAME => 'revenj',
            Config::DB_PASSWORD => 'revenj',
            Config::DB_HOST => 'localhost',
            Config::DB_PORT => '5432',
            Config::REVENJ_URL => 'http://localhost:8999/',
            Config::REVENJ_PATH => './revenj',
            Config::PHP_URL => 'localhost:9012',
        );
    }

    public static function install(Event $event)
    {
        $io = new IOWrapper($event->getIO());
        $io->write("Installing dsl-admin-php");
        $config = new Config($io, self::getDefaults());

        while ($answer = $io->askRequired('Setup DSL platform? [y/n/D] (d=use defaults) ', 'd')) {
            $answer = strtolower($answer);
            if ($answer === 'y')
                break;
            if ($answer === 'n')
                return;
            if ($answer === 'd') {
                $io->useDefaults(true);
                break;
            }
        }

        $compiler = new CompilerClient($config, $io);
        $revenj = new RevenjInstaller($io, $config, $compiler);

        if ($io->askConfirmation('Setup Revenj server?', true))
            $revenj->setup();

        if ($io->askConfirmation('Compile PHP sources?', true)) {
            $io->write('Compiling PHP sources');
            $compiler->compilePhp();
        }

        if ($io->askConfirmation('Perform database migration?', true))
            $compiler->applyMigration($config->getCompilerConnectionString());

        $revenj->setupConfig($config->get(Config::REVENJ_URL, $config->getConnectionString()));

        if ($io->askConfirmation('Start app?', true)) {

            $io->write('Starting Revenj HTTP server at '.$config->get(Config::REVENJ_URL));
            $revenjHttp = new Process('mono revenj/Revenj.Http.exe');
            $revenjHttp->start();

            $phpUrl = $config->get(Config::PHP_URL);
            $io->write('Starting PHP built-in web server at '.$phpUrl);
            $phpAdmin = new Process('php -S ' . $phpUrl . ' -t web/ web/router.php');
            $phpAdmin->start();

            $io->write('Starting Firefox');
            $firefox = new Process('firefox '.$phpUrl);
            $firefox->run();
        }
    }

}
