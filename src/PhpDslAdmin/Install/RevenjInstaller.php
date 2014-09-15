<?php
namespace PhpDslAdmin\Install;

use Composer\IO\IOInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class RevenjInstaller
{
    protected $config;

    protected $io;

    protected $compiler;

    public function __construct(IOWrapper $io, Config $config, CompilerClient $compiler)
    {
        $this->io = $io;
        $this->config = $config;
        $this->compiler = $compiler;
    }

    public function getConfigPath()
    {
        return $this->config->get(Config::REVENJ_PATH).'/Revenj.Http.exe.config';
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
            $this->io->write('Downloading Revenj to folder: '.$this->config->get(Config::REVENJ_PATH));
            $this->compiler->downloadRevenj();
        }
        $address = $this->config->get(Config::REVENJ_URL);
        if (substr($address, strlen($address)-1) !== '/')
            $address .= '/';
        $this->address = $address;
    }

    public function setupConfig()
    {
        $connString = $this->config->getConnectionString();
        $url = $this->config->get(Config::REVENJ_URL);

        $content = $this->getConfigContents();
        $content = preg_replace(
            '/key="ServerAssembly" value="(.*)"/',
            'key="ServerAssembly" value="../GeneratedModel.dll"',
            $content);

        if ($connString !== null)
            $content = preg_replace(
                '/key="ConnectionString" value="(.*)"/',
                'key="ConnectionString" value="' . $connString . '"',
                $content);

        if ($url !== null) {
            $addressId = preg_replace("/[^A-Za-z0-9]/", '', $url);
            $content = preg_replace(
                '/key="HttpAddress_(.*)" value="(.*)"/',
                'key="HttpAddress_' . $addressId . '" value="' . $url . '"',
                $content);
        }
        return $this->saveConfig($content);
    }

    public function run()
    {

    }
}
