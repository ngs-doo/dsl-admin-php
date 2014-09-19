<?php
namespace PhpDslAdmin\Install;

use Composer\IO\IOInterface;
use Composer\Script\Event;

/**
 * Class Context
 * @package PhpDslAdmin\Install
 */
class Context
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Config
     */
    private $defaults;

    /**
     * @var string
     */
    private $configPath;

    /**
     * @var bool
     */
    private $useDefaults = false;

    /**
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->io = $event->getIO();
        // $args = ($event->getArguments());

        $this->configPath = getcwd().'/dsl_config.json';
        $params = file_exists($this->configPath)
            ? Config::readFile($this->configPath) : array();
        $this->config = new Config($params);

        $defaultsPath = getcwd().'/dsl_config.dist';
        if (file_exists($defaultsPath))
            $this->defaults = new Config(Config::readFile($defaultsPath));
    }

    public function __destruct()
    {
        $this->config->writeFile($this->configPath);
    }

    public function useDefaults($useDefaults=true)
    {
        $this->useDefaults = $useDefaults;
    }

    public function get($key)
    {
        $value = $this->config->get($key);
        if ($value === null) {
            $inputHidden = $key === Config::DSL_PASSWORD;
            $default = isset($this->defaults) ? $this->defaults->get($key) : null;
            $value = $this->ask($this->config->getDescription($key), $default, $inputHidden);
            $this->config->set($key, $value);
        }
        return $value;
    }

    public function getDb()
    {
        return array(
            'server'    => $this->get(Config::DB_SERVER),
            'port'      => $this->get(Config::DB_PORT),
            'database'  => $this->get(Config::DB_DATABASE),
            'user'      => $this->get(Config::DB_USER),
            'password'  => $this->get(Config::DB_PASSWORD),
        );
    }

    public function askConfirmation($question, $default = null)
    {
        if ($this->useDefaults === true && $default !== null)
            return $default;
        $default === true
            ? $question .= ' [Y/n] '
            : ($default === false
            ? $question .= ' [y/N] '
            : $question .= ' [y/n] ');
        while (1) {
            $input = strtolower($this->io->ask($question . ': '));
            if (trim($input) === '' && $default !== null)
                return $default;
            else if (trim($input) !== '')
                return $input;
            $this->io->write($question . ' cannot be empty!');
        }
    }

    public function ask($question, $default = null, $hidden = false)
    {
        if ($this->useDefaults === true && $default !== null)
            return $default;
        while (1) {
            if (!$hidden)
                $input = $this->io->ask($question . ': ' . ($default !== null ? '(' . $default . ') ' : ' '));
            else
                $input = $this->io->askAndHideAnswer($question . ': ');
            if (trim($input) === '' && $default !== null)
                return $default;
            else if (trim($input) !== '')
                return $input;
            $this->io->write($question . ' cannot be empty!');
        }
    }

    public function write($message, $newline=true)
    {
        $this->io->write($message, $newline);
    }
} 