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
        $packageDir = realpath(__DIR__ . '/../..');
        $revenjConfig = $rootDir . '/revenj/Revenj.Http.exe.config';
        $connString = null;

        self::downloadClc($rootDir . '/dsl-clc.jar');
        self::copyAppFiles($packageDir, $rootDir);

        if ($io->askConfirmation('Compile PHP sources? [Y/n] ', true)) {
            $io->write('Compiling PHP sources');
            self::runClc('-target=php,php_ui');
        }

        if ($io->askConfirmation('Setup Revenj server? [Y/n] ', true)) {
            if (!file_exists($revenjConfig)) {
                $io->write('Downloading Revenj');
                self::runClc('-target=revenj -download');
            }
            $revenjAddress = self::askRequired('Revenj address', 'http://localhost:8999/');
            if ($revenjAddress[strlen($revenjAddress) - 1] !== '/')
                $revenjAddress .= '/';
        }

        if ($io->askConfirmation('Setup database? [Y/n] ', true)) {

            $io->write('Database config:');

            $content = file_get_contents($revenjConfig);
            preg_match(
                '/key="ConnectionString" value="server=(.*);port=([0-9]*);database=(.*);user=(.*);password=(.*);encoding/',
                $content,
                $matches);

            $host = 'localhost';
            $port = '5432';
            $database = 'revenj';
            $user = 'revenj';
            $pass = 'revenj';
            if (count($matches)) {
                $host = $matches[1];
                $port = $matches[2];
                $database = $matches[3];
                $user = $matches[4];
                $pass = $matches[5];
            }

            $host = self::askRequired('Host', $host);
            $port = self::askRequired('Port', $port);
            $database = self::askRequired('Database', $database);
            $user = self::askRequired('User', $user);
            $pass = self::askRequired('Pass', $pass);
            $conn = addslashes("{$host}/{$database}?user={$user}&password={$pass}");

            if ($io->askConfirmation('Perform database migration? [y/n] '))
                self::runClc('-migration -apply -force -db="' . $conn . '"');

            $connString = addslashes(sprintf("server=%s;port=%s;database=%s;user=%s;password=%s;encoding=unicode",
                $host, $port, $database, $user, $pass));
        }

        if (isset($revenjAddress))
            self::setupRevenjConfig($revenjConfig, $revenjAddress, $connString);

        // @todo everything
        if ($io->askConfirmation('Start app? [Y/n]', true)) {
            $phpUrl = self::askRequired('PHP url', 'http://localhost:8995');
            // $io->write('Starting Revenj');
            // @todo revenj background
            echo shell_exec('mono revenj/Revenj.Http.exe &');
            $io->write('Starting PHP built-in web server at '.$phpUrl);
            echo shell_exec('php -S ' . $phpUrl . ' -t web/ web/router.php &');
            $io->write('Starting browser');
            echo shell_exec('firefox ' . $phpUrl);
        }
    }

    protected static function setupRevenjConfig($path, $address = null, $connString = null)
    {
        if (!file_exists($path))
            self::error('No revenj config found in: ' . $path);

        $content = file_get_contents($path);
        if ($content === false)
            self::error('Cannot read Revenj config from: '.$path);

        $content = preg_replace(
            '/key="ServerAssembly" value="(.*)"/',
            'key="ServerAssembly" value="../GeneratedModel.dll"',
            $content);

        if ($connString !== null)
            $content = preg_replace(
                '/key="ConnectionString" value="(.*)"/',
                'key="ConnectionString" value="' . $connString . '"',
                $content);

        if ($address !== null) {
            $addressId = preg_replace("/[^A-Za-z0-9]/", '', $address);
            $content = preg_replace(
                '/key="HttpAddress_(.*)" value="(.*)"/',
                'key="HttpAddress_' . $addressId . '" value="' . $address . '"',
                $content);
        }
        if (file_put_contents($path, $content) === false)
            self::error('Cannot write Revenj config to: '.$path);
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
