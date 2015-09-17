<?php
/**
 * Config.php
 *
 * Author: Larry Li <larryli@qq.com>
 */

namespace larryli\ipv4\console;

use larryli\ipv4\Object;
use larryli\ipv4\Query;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Class Config
 * @package larryli\ipv4\console
 */
class Config extends Object
{
    /**
     * @var Config
     */
    static protected $instance;
    /**
     * @var string
     */
    public $filename;
    /**
     * @var /larryli/ipv4/Database
     */
    protected $database;
    /**
     * @var string
     */
    protected $home;
    /**
     * @var Query[]
     */
    protected $objects;

    /**
     *
     */
    public function __construct()
    {
        $this->initFilename();
        $this->initConfig();
    }

    /**
     *
     */
    protected function initFilename()
    {
        if (!empty(getenv('HOME'))) {
            $this->home = getenv('HOME');
        } else if (isset($_SERVER['HOME'])) {
            $this->home = $_SERVER['HOME'];
        } else {
            $this->home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'] . DIRECTORY_SEPARATOR;
        }
        $dir = $this->home . '/.ipv4/';
        if (!file_exists($dir)) {
            if (!mkdir($dir)) {
                $this->failed("create ipv4 config dir \"{$dir}\" failed!");
            }
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            $this->failed("ipv4 config dir \"{$dir}\" is not a directory or writable!");
        }
        $this->filename = $dir . 'ipv4.yaml';
        if (!file_exists($this->filename)) {
            copy(__DIR__ . '/ipv4.yaml', $this->filename);
        }
    }

    /**
     * @param $str
     */
    protected function failed($str)
    {
        echo $str . PHP_EOL;
        exit(1);
    }

    /**
     *
     */
    protected function initConfig()
    {
        $config = [];
        $yaml = new Parser();
        try {
            $config = $yaml->parse(file_get_contents($this->filename));
        } catch (ParseException $e) {
            $msg = $e->getMessage();
            $this->failed("Unable to parse the YAML string: {$msg}");
        }
        $this->initDatabase(@$config['database']);
        $this->initProviders(@$config['providers']);
    }

    /**
     * @param mixed $options
     */
    protected function initDatabase($options)
    {
        if (!isset($options) || !is_array($options)) {
            $this->failed("'database' is not an array or not exists");
        }
        if (isset($options['class'])) {
            $class = $options['class'];
            unset($options['class']);
        } else {
            $class = "\\larryli\\ipv4\\medoo\\Database";
            if (!isset($options['database_type'])) {
                $this->failed("medoo config is not a array or have not type");
            }
        }
        if (isset($options['database_file'])) {
            $options['database_file'] = $this->getFilename($options['database_file']);
        }
        $this->database = new $class($options);
    }

    /**
     * @param $filename
     * @return mixed
     */
    protected function getFilename($filename)
    {
        return str_replace('~', $this->home, $filename);
    }

    /**
     * @param $config
     */
    protected function initProviders($config)
    {
        if (!isset($config) || !is_array($config)) {
            $this->failed("'providers' is not an array or not exists");
        }
        foreach ($config as $name => $options) {
            $providers = [];
            if (is_array($options)) {
                $opt = null;
                if (isset($options['providers']) && is_array($options['providers'])) {
                    $providers = $options['providers'];
                    unset($options['providers']);
                    $opt = $this->database;
                }
                if (isset($options['filename'])) {
                    $opt = $this->getFilename($options['filename']);
                    unset($options['filename']);
                }
                if (isset($options['class']) && !empty($opt)) {
                    $options['options'] = $opt;
                } else {
                    $options = $opt;
                }
            }
            $this->createQuery($name, $options, $providers);
        }
    }

    /**
     * @param string $name
     * @param mixed $options
     * @param array $providers
     * @return Query|null
     * @throws \Exception
     */
    public function createQuery($name, $options, array $providers = [])
    {
        $query = $this->getQuery($name);
        if ($query == null) {
            $query = Query::create($name, $options);
            $query->setProviders(array_map(function ($provider) {
                return $this->getQuery($provider);
            }, $providers));
            $this->objects[$name] = $query;
        }
        return $query;
    }

    /**
     * @param $name
     * @return Query|null
     */
    public function getQuery($name)
    {
        if (isset($this->objects[$name])) {
            return $this->objects[$name];
        }
        return null;
    }

    /**
     * @return \larryli\ipv4\Query[]
     */
    public function getQueries()
    {
        return $this->objects;
    }

    /**
     * @return Config
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}