<?php
/**
 * Config.php
 *
 * Author: Larry Li <larryli@qq.com>
 */

namespace larryli\ipv4\console;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Class Config
 * @package larryli\ipv4\console
 */
class Config
{
    /**
     * object names
     *
     * @var string[]
     */
    static public $classNames = [
        'monipdb' => 'MonIPDBQuery',
        'qqwry' => 'QQWryQuery',
        'full' => 'FullQuery',
        'mini' => 'MiniQuery',
        'china' => 'ChinaQuery',
        'world' => 'WorldQuery',
        'freeipip' => 'FreeIPIPQuery',
        'taobao' => 'TaobaoQuery',
        'sina' => 'SinaQuery',
        'baidumap' => 'BaiduMapQuery',
    ];
    /**
     * @var
     */
    static $instance;
    /**
     * @var string
     */
    public $filename;
    /**
     * @var /larryli/ipv4/Database
     */
    protected $db;
    /**
     * @var array
     */
    public $providers;
    /**
     * @var string
     */
    protected $home;
    /**
     * @var /larryli/ipv4/Query[]
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
     * @param $config
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
        $this->db = new $class($options);
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
            $this->providers[$name] = null;
            $class = '';
            if (is_array($options)) {
                if (isset($options['class'])) {
                    $class = $options['class'];
                    unset($options['class']);
                }
                if (isset($options['providers']) && is_array($options['providers'])) {
                    $this->providers[$name] = $options['providers'];
                    $options = $this->db;
                } else if (isset($options['filename'])) {
                    $options = $this->getFilename($options['filename']);
                }
            }
            if (empty($class)) {
                if (isset(self::$classNames[$name])) {
                    $class = "\\larryli\\ipv4\\" . self::$classNames[$name];
                } else {
                    $this->failed("'providers:{$name}' is unknown");
                }
            }
            $this->objects[$name] = new $class($options);
        }
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

    /**
     * @param string $name
     * @return null|\larryli\ipv4\Query
     */
    public function getQuery($name)
    {
        if (array_key_exists($name, $this->objects)) {
            return $this->objects[$name];
        }
        return null;
    }
}