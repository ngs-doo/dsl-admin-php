<?php

namespace PhpDslAdmin;

use Composer\Script\Event;

class Installer
{
    private static $clcUrl = 'https://github.com/ngs-doo/dsl-compiler-client/releases/download/0.9.6/dsl-clc.jar';

    protected static function error($message)
    {
        echo 'ERROR: '.$message."\n";
        die;
    }

    public static function install(Event $event)
    {
        echo "Installing dsl-admin-php\n";

        // @todo get path from Composer API
        $root = realpath(__DIR__.'/../..');

        $clcPath = $root.'/dsl-clc.jar';

        if (!file_exists($clcPath)) {
            echo "Downloading dsl-compiler-client\n";
            if (($clcJar = file_get_contents(self::$clcUrl)) === false)
                self::error('Cannot download dls-clc.jar from '.self::$clcUrl);
            if (file_put_contents($clcPath, $clcJar) === false)
                self::error('Cannot write dsl-clc.jar to file '.$clcPath);
        }

        $appDir = realpath(__DIR__.'/app');

        $files = array(
            $appDir.'/config/dev.php'   => $root.'/config/dev.php',
            $appDir.'/config/prod.php'  => $root.'/config/prod.php',
            $appDir.'/web/index.php'    => $root.'/web/index.php',
            $appDir.'/compile-php.sh'   => $root.'/compile-php.sh',
        );

        foreach ($files as $src => $dest) {
            if (file_exists($dest))
                continue;
            $dir = dirname($dest);
            if (!is_dir($dir) && !mkdir($dir))
                self::error('Cannot create folder '.$dir);
            if (!copy($src, $dest))
                self::error('Cannot copy to ' . $dest);
        }

        echo "run ./compile-php.sh to generate PHP sources";
    }
}
