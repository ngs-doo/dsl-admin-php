<?php
namespace PhpDslAdmin\Install;
use Composer\IO\IOInterface;

/**
 * Wrapper around dsl-compiler-client
 * @package PhpDslAdmin\Install
 */
class CompilerClient
{
    private $clcUrl = 'https://github.com/ngs-doo/dsl-compiler-client/releases/download/0.9.7/dsl-clc.jar';

    private $jarPath;

    private $config;

    private $io;

    public function __construct(Config $config, IOWrapper $io, $jarPath = null)
    {
        if ($jarPath === null)
            $jarPath = getcwd() . '/dsl-clc.jar';
        $this->jarPath = $jarPath;
        $this->io = $io;
        $this->config = $config;
    }

    protected function assertJava()
    {
        static $requiredJvmExists;
        if (!isset($requiredJvmExists)) {
            $output = shell_exec('java -version 2>&1');
            preg_match('/java version "1\.([0-9]+).*"/', $output, $matches);
            $requiredJvmExists = (count($matches) === 2 && (int)$matches[1] > 6);
        }

        if (!$requiredJvmExists)
            throw new \ErrorException('No JVM version found, must have 1.6 or greater');
    }

    protected function assertJar()
    {
        if ($this->jarPath === null || !file_exists($this->jarPath))
            $this->downloadClc($this->jarPath);
    }

    protected function downloadClc($path)
    {
        $this->io->write("Downloading dsl-compiler-client\n");
        if (($clcJar = file_get_contents($this->clcUrl)) === false)
            throw new \ErrorException('Cannot download dls-clc.jar from ' . $this->clcUrl);
        if (file_put_contents($path, $clcJar) === false)
            throw new \ErrorException('Cannot write dsl-clc.jar to file ' . $path);
    }

    protected function run($args, $login = true)
    {
        $this->assertJava();
        $this->assertJar();

        $auth = $login
            ? sprintf(' -u="%s" -p="%s"', $this->config->getUsername(), $this->config->getPassword())
            : '';
        $command = 'java -jar dsl-clc.jar ' . $args;
        $this->io->write('Running: ' . $command);
        echo shell_exec($command.$auth);
    }

    public function compilePhp()
    {
        // $phpDir = ...
        return $this->run('-target=php,php_ui');
    }

    public function downloadRevenj()
    {
        // $revenjDir = $this->config->get(Config::REVENJ_PATH);
        return $this->run('-target=revenj -download');
    }

    public function applyMigration($connString)
    {
        return $this->run('-migration -apply -force -db="' . $connString . '"');
    }
} 