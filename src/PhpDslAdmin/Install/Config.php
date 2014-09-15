<?php
namespace PhpDslAdmin\Install;

class Config
{
    const DSL_USERNAME = 'u';
    const DSL_PASSWORD = 'p';
    const DSL_PATH = 'dsl_dir';

    const DB_DATABASE = 'db_database';
    const DB_USERNAME = 'db_username';
    const DB_PASSWORD = 'db_password';
    const DB_HOST = 'db_host';
    const DB_PORT = 'db_port';

    const REVENJ_URL = 'revenj_url';
    const REVENJ_PATH = 'revenj_dir';

    const PHP_URL = 'php_url';

    private $io;

    private $values = array();

    private static $descriptions = array(
        self::DSL_USERNAME => 'DSL Platform username',
        self::DSL_PASSWORD => 'DSL Platform password',
        self::DSL_PATH => 'DSL folder',
        self::DB_DATABASE => 'Database name',
        self::DB_USERNAME => 'Database user',
        self::DB_PASSWORD => 'Database password',
        self::DB_HOST => 'Database host',
        self::DB_PORT => 'Database port',
        self::REVENJ_URL => 'Revenj URL',
        self::REVENJ_PATH => './revenj',
        self::PHP_URL => 'PHP Admin URL',
    );

    public function __construct(IOWrapper $io, array $values = array())
    {
        $this->io = $io;
        foreach ($values as $key => $val)
            $this->set($key, $val);
    }

    public function get($key)
    {
        //if (!defined('Config::'.$key))
        //    throw new \InvalidArgumentException('Cannot get config value! Invalid config property: '.$key);
        if (!isset($this->values[$key])) {
            $value = $this->io->askRequired(self::$descriptions[$key]);
            $this->values[$key] = $value;
        }
        return $this->values[$key];
    }

    public function getUsername()
    {
        return $this->get(static::DSL_USERNAME);
    }

    public function getPassword()
    {
        if (!isset($this->values[static::DSL_PASSWORD])) {
            $value = $this->io->askRequired(self::$descriptions[static::DSL_PASSWORD], null, true);
            $this->values[static::DSL_PASSWORD] = $value;
        }
        return $this->values[static::DSL_PASSWORD];
    }
/*
    public function get($key)
    {
        if (!defined(Config::$key))
            throw new \InvalidArgumentException('Cannot get config value! Invalid config property: '.$key);
        return $this->values[$key];
    }
*/
    public function set($key, $value)
    {
        //if (!isset(static::$key))
        //    throw new \InvalidArgumentException('Cannot set config value! Invalid config property: '.$key);
        $this->values[$key] = $value;
    }

    public function getDslPath()
    {
        return getcwd().'/dsl';
    }

    protected function formatConnectionString($string)
    {
        return addslashes(sprintf($string,
            $this->get(Config::DB_HOST),
            $this->get(Config::DB_PORT),
            $this->get(Config::DB_DATABASE),
            $this->get(Config::DB_USERNAME),
            $this->get(Config::DB_PASSWORD)));
    }

    public function getCompilerConnectionString()
    {
        return $this->formatConnectionString('%s:%s/%s?user=%s&password=%s');
    }

    public function getConnectionString()
    {
        return $this->formatConnectionString('server=%s;port=%s;database=%s;user=%s;password=%s;encoding=unicode');
    }
}
