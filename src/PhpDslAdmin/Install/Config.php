<?php
namespace PhpDslAdmin\Install;

class Config
{
    const DSL_USERNAME  = 'DSL_USERNAME';
    const DSL_PASSWORD  = 'DSL_PASSWORD';
    const DSL_PATH      = 'DSL_PATH';
    const DB_DATABASE   = 'DB_DATABASE';
    const DB_USER       = 'DB_USER';
    const DB_PASSWORD   = 'DB_PASSWORD';
    const DB_SERVER     = 'DB_SERVER';
    const DB_PORT       = 'DB_PORT';
    const REVENJ_URL    = 'REVENJ_URL';
    const REVENJ_PATH   = 'REVENJ_PATH';
    const PHP_URL       = 'PHP_URL';
    const CLC_PATH      = 'CLC_PATH';
    const CLC_URL       = 'CLC_URL';

    private static $descriptions = array(
        self::DSL_USERNAME => 'DSL Platform username',
        self::DSL_PASSWORD => 'DSL Platform password',
        self::DSL_PATH => 'Path to DSL folder',
        self::DB_DATABASE => 'Database name',
        self::DB_USER => 'Database user',
        self::DB_PASSWORD => 'Database password',
        self::DB_SERVER => 'Database server',
        self::DB_PORT => 'Database port',
        self::REVENJ_URL => 'Revenj URL',
        self::REVENJ_PATH => 'Path to Revenj folder',
        self::PHP_URL => 'PHP Admin URL',
        self::CLC_PATH => 'Path to dsl-clc.jar',
        self::CLC_URL => 'dsl-clc.jar download URL',
    );

    private $values = array();


    public function __construct(array $params)
    {
        foreach ($params as $key => $val)
            $this->set($key, $val);
    }

    public static function readFile($path)
    {
        if (!file_exists($path))
            throw new \ErrorException('File not found: '.$path);
        $content = file_get_contents($path);
        if ($content === false)
            throw new \ErrorException('Could not read from config file: '.$path);
        $params = json_decode($content, true);
        if ($params === null)
            throw new \ErrorException('Could not parse dsl_config.json file! Check if it contains valid JSON.');
        return $params;
    }

    public function writeFile($path)
    {
        $params = array();
        foreach ($this->values as $key => $val)
            $params[strtolower($key)] = $val;
        ksort($params);
        $content = json_encode($params, JSON_FORCE_OBJECT|JSON_PRETTY_PRINT);
        $content = file_put_contents($path, $content);
        if ($content === false)
            throw new \ErrorException('Could not write to config file: '.$path);
    }

    public function get($key)
    {
        if (!defined('static::'.$key))
            throw new \InvalidArgumentException('Cannot get config value! Invalid config property: '.$key);
        return isset($this->values[$key]) ? $this->values[$key] : null;
    }

    public function set($key, $value)
    {
        $key = strtoupper($key);
        if (!defined('static::'.$key))
            throw new \InvalidArgumentException('Cannot set config value! Invalid config property: '.$key);
        $this->values[$key] = $value;
    }

    public function getDescription($key)
    {
        return isset(self::$descriptions[$key]) ? self::$descriptions[$key] : null;
    }
}
