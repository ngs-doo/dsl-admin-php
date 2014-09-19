<?php
namespace PhpDslAdmin\Install;

use Composer\Compiler;
use Composer\IO\IOInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class RevenjInstaller
{
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getConfigPath()
    {
        return $this->context->get(Config::REVENJ_PATH).'/Revenj.Http.exe.config';
    }

    public function getConfigContents()
    {
        $path = $this->getConfigPath();
        $content = file_get_contents($path);
        if ($content === false)
            throw new \ErrorException('Cannot read Revenj config from: '.$path);
        return $content;
    }

    protected function saveConfig($content)
    {
        $path = $this->getConfigPath();
        if (file_put_contents($path, $content) === false)
            throw new \ErrorException('Cannot write Revenj config to: '.$path);
        return true;
    }

    public function setup()
    {
        if (!file_exists($this->getConfigPath())) {
            $this->context->write('Downloading Revenj to folder: '.$this->context->get(Config::REVENJ_PATH));
            $compiler = new CompilerClient($this->context);
            $compiler->downloadRevenj();
        }
        $url = $this->context->get(Config::REVENJ_URL);
        if (substr($url, strlen($url)-1) !== '/')
            $url .= '/';
        $db = $this->context->getDb();
        $connString = addslashes(sprintf('server=%s;port=%s;database=%s;user=%s;password=%s;encoding=unicode',
            $db['server'], $db['port'], $db['database'], $db['user'], $db['password']));
        $content = $this->getConfigContents();

        $addressId = preg_replace("/[^A-Za-z0-9]/", '', $url);
        $content = preg_replace(
            '/key="HttpAddress_(.*)" value="(.*)"/',
            'key="HttpAddress_' . $addressId . '" value="' . $url . '"',
            $content);
        $content = preg_replace(
            '/key="ServerAssembly" value="(.*)"/',
            'key="ServerAssembly" value="../GeneratedModel.dll"',
            $content);
        $content = preg_replace(
            '/key="ConnectionString" value="(.*)"/',
            'key="ConnectionString" value="' . $connString . '"',
            $content);
        $this->saveConfig($content);
    }
}
