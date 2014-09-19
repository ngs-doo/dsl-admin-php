<?php

namespace PhpDslAdmin;

use Composer\Script\Event;
use PhpDslAdmin\Install\CompilerClient;
use PhpDslAdmin\Install\Config;
use PhpDslAdmin\Install\Context;
use PhpDslAdmin\Install\IOWrapper;
use PhpDslAdmin\Install\RevenjInstaller;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class Installer
{
    private static function resolveContext($context)
    {
        if ($context instanceof Context)
            return $context;
        if ($context instanceof Event)
            return new Context($context);
        throw new \ErrorException('Cannot resolve context!');
    }

    public static function migrate($context)
    {
        $context = self::resolveContext($context);
        $compiler = new CompilerClient($context);
        $compiler->applyMigration();
    }

    public static function compile($context)
    {
        $context = self::resolveContext($context);
        $context->write('Compiling PHP and PHP_UI targets');
        $compiler = new CompilerClient($context);
        $compiler->compile(array('php', 'php_ui'));
    }

    public static function compilePhp($context)
    {
        $context = self::resolveContext($context);
        $context->write('Compiling PHP target');
        $compiler = new CompilerClient($context);
        $compiler->compile('php');
    }

    public static function compilePhpUI($context)
    {
        $context = self::resolveContext($context);
        $context->write('Compiling PHP_UI target');
        $compiler = new CompilerClient($context);
        $compiler->compile('php_ui');
    }

    public static function installRevenj($context)
    {
        $context = self::resolveContext($context);
        $context->write('Installing Revenj');
        $revenj = new RevenjInstaller($context);
        $revenj->setup();
    }

    public static function startRevenj($context)
    {
        $context = self::resolveContext($context);
        $revenjUrl = $context->get(Config::REVENJ_URL);
        $context->write('Starting Revenj HTTP server at ' . $revenjUrl);
        $proc = new Process('mono revenj/Revenj.Http.exe');
        $proc->start();
        $maxDelay = 5;
        for ($i=0; $i<$maxDelay; $i++) {
            sleep(1);
            if ($err = $proc->getErrorOutput()) {
                if (stripos($err, 'Address already in use')) {
                    $context->write('Could not start Revenj! Address '.$revenjUrl.' already in use.');
                    return $proc;
                }
                throw new \ErrorException('Cold not start Revenj server: ' . $err);
            }
            $out = $proc->getOutput();
            if (stripos($out, 'server running') !== false)
                return $proc;
        }
    }

    public static function startPhp($context)
    {
        $context = self::resolveContext($context);
        $phpUrl = $context->get(Config::PHP_URL);
        $context->write('Starting PHP built-in web server at '.$phpUrl);
        $proc = new Process('php -S ' . $phpUrl . ' -t web/ web/router.php');
        $proc->start();
        sleep(1);
        if ($err = $proc->getErrorOutput()) {
            if (stripos($err, 'Address already in use')) {
                $context->write('Could not start PHP server. Address '.$phpUrl.' already in use.');
                return $proc;
            }
            throw new \ErrorException('Cold not start PHP built-in server: '.$err);
        }
        return $proc;
    }

    public static function openFirefox($context)
    {
        $context = self::resolveContext($context);
        $context->write('Opening php-admin in Firefox');
        $firefox = new Process('firefox '.$context->get(Config::PHP_URL));
        $firefox->run();
    }

    public static function start($context)
    {
        self::startRevenj($context);
        self::startPhp($context);
        self::openFirefox($context);
    }

    public static function install(Event $event)
    {
        $context = new Context($event);
        while ($answer = strtolower($context->ask('Setup DSL platform? [y/n/D] (d=use defaults) ', 'd'))) {
            if ($answer === 'y')
                break;
            if ($answer === 'n')
                return;
            if ($answer === 'd') {
                $context->useDefaults(true);
                break;
            }
        }
        if ($context->askConfirmation('Install Revenj server?', true))
            self::installRevenj($context);
        if ($context->askConfirmation('Compile PHP sources?', true))
            self::compileAllPhp($context);
        if ($context->askConfirmation('Perform database migration?', true))
            self::migrate($context);
        if ($context->askConfirmation('Start Revenj and PHP server?', true))
            self::start($context);
    }

}
