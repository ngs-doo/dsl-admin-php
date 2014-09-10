<?php

namespace PhpDslAdmin;

use Composer\Script\Event;

class Installer
{
    private static $clcUrl = 'https://github.com/ngs-doo/dsl-compiler-client/releases/download/0.9.7/dsl-clc.jar';

    private static $io;

    private static $username;

    private static $password;

    public static function install(Event $event)
    {
        self::$io = $io = $event->getIO();
        $io->write("Installing dsl-admin-php");

        $rootDir = getcwd();
        $packageDir = realpath(__DIR__.'/../..');

        self::downloadClc($rootDir.'/dsl-clc.jar');
        self::copyAppFiles($packageDir, $rootDir);

        if ($io->askConfirmation('Compile PHP sources? [y/n] '))
            self::runClc('-target=php,php_ui');

        if ($io->askConfirmation('Setup Revenj server? [y/n] '))
            self::runClc('-target=revenj -download');

        if ($io->askConfirmation('Setup database (required for Revenj and migration)? [y/n] ')) {
            $host = self::askRequired('Host', 'localhost');
            $port = self::askRequired('Port', '5432');
            $database = self::askRequired('Database');
            $user = self::askRequired('User', 'postgres');
            $pass = self::askRequired('Pass', 'postgres');
            $conn = "{$host}/{$database}?user={$user}&password={$pass}";

            // @todo replace conn string in Revenj.Http.exe.config

            if ($io->askConfirmation('Perform database migration? [y/n] '))
                self::runClc('-migration -apply -force -db="'.$conn.'"');
        }
    }

    protected static function error($message)
    {
        self::$io->write('ERROR: ' . $message);
        die;
    }

    protected static function downloadClc($path)
    {
        if (!file_exists($path)) {
            echo "Downloading dsl-compiler-client\n";
            if (($clcJar = file_get_contents(self::$clcUrl)) === false)
                self::error('Cannot download dls-clc.jar from ' . self::$clcUrl);
            if (file_put_contents($path, $clcJar) === false)
                self::error('Cannot write dsl-clc.jar to file ' . $clcPath);
        }
    }

    protected static function copyAppFiles($src, $dest)
    {
        $files = array(
            $src . '/config/dev.php' => $dest . '/config/dev.php',
            $src . '/config/prod.php' => $dest . '/config/prod.php',
            $src . '/web/index.php' => $dest . '/web/index.php',
            $src . '/compile-php.sh' => $dest . '/compile-php.sh',
        );
        foreach ($files as $sf => $df) {
            if (file_exists($df))
                continue;
            $dir = dirname($df);
            if (!is_dir($dir) && @!mkdir($dir))
                self::error('Cannot create folder ' . $dir);
            if (!copy($sf, $df))
                self::error('Cannot copy to ' . $df);
        }
    }

    protected static function askRequired($question, $default=null)
    {
        while (1) {
            $input = self::$io->ask($question.': ' . ($default !== null ? '('.$default.') ' : ' '));
            if (trim($input) === '' && $default !== null)
                return $default;
            else if(trim($input) !== '')
                break;
            self::$io->write($question.' cannot be empty!');
        }
        return $input;
    }

    protected static function getUsername()
    {
        static $username;
        if (!isset($username))
            $username = self::askRequired('DSL platform username');
        return $username;
    }

    protected static function getPassword()
    {
        static $username;
        if (!isset($username))
            $username = self::askRequired('DSL platform password');
        return $username;
    }

    protected static function runClc($args, $login=true)
    {
        $auth = $login
            ? sprintf(' -u="%s" -p="%s"', self::getUsername(), self::getPassword())
            : '';
        $command = 'java -jar dsl-clc.jar '.$args. $auth;
        self::$io->write('Running: '.$command);
        echo shell_exec($command);
    }
}
