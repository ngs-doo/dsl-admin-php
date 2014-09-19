<?php
namespace PhpDslAdmin\Install;
use Composer\IO\IOInterface;
use Symfony\Component\Process\Process;

/**
 * Wrapper around dsl-compiler-client
 * @package PhpDslAdmin\Install
 */
class CompilerClient
{
    private $context;

    private $jarPath;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->jarPath = $context->get(Config::CLC_PATH);
    }

    protected function assertJvmVersion()
    {
        static $requiredJvmExists;
        if (!isset($requiredJvmExists)) {
            $proc = new Process('java -version');
            $proc->run();
            $output = $proc->getOutput();
            if ($output === null)
                $output = $proc->getErrorOutput();
            preg_match('/java version "1\.([0-9]+).*"/', $output, $matches);
            $requiredJvmExists = (count($matches) === 2 && (int)$matches[1] > 6);
        }

        if (!$requiredJvmExists)
            throw new \ErrorException('No required JVM version found');
    }

    protected function assertJarExists()
    {
        if ($this->jarPath === null || !file_exists($this->jarPath))
            $this->downloadClc($this->jarPath);
    }

    protected function downloadClc($path)
    {
        $this->context->write("Downloading dsl-compiler-client\n");
        $clcUrl = $this->context->get(Config::CLC_URL);
        if (($clcJar = file_get_contents($clcUrl)) === false)
            throw new \ErrorException('Cannot download dsl-clc.jar from ' . $clcUrl);
        if (file_put_contents($path, $clcJar) === false)
            throw new \ErrorException('Cannot write dsl-clc.jar to ' . $path);
    }

    protected function run($args, $login = true)
    {
        $this->assertJvmVersion();
        $this->assertJarExists();

        $username = $this->context->get(Config::DSL_USERNAME);
        $password = $this->context->get(Config::DSL_PASSWORD);
        $dslPath = $this->context->get(Config::DSL_PATH);

        $auth = $login
            ? sprintf(' -u="%s" -p="%s"', $username, $password)
            : '';
        $command = 'java -jar '.$this->jarPath.' -dsl='.$dslPath.' '. $args;
        $this->context->write('Running: ' . $command . ' -u='.$username.' -p=[hidden]');

        $process = new Process($command.$auth);
        $process->run();
        $this->context->write($process->getOutput() ?: $process->getErrorOutput());
        return true;
    }

    public function compile($targets)
    {
        if (is_array($targets))
            $targets = implode(',', $targets);
        return $this->run('-target='.$targets);
    }

    public function downloadRevenj()
    {
        // $revenjPath = $this->config->get(Config::REVENJ_PATH);
        return $this->run('-target=revenj -download');
    }

    public function applyMigration()
    {
        $db = $this->context->getDb();
        $connString = sprintf('%s:%s/%s?user=%s&password=%s', $db['server'], $db['port'], $db['database'], $db['user'], $db['password']);
        return $this->run('-migration -apply -force -db="' . $connString . '"');
    }
}
