<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Driver;

use Stash;
use Stash\Exception\RuntimeException;
use Stash\Exception\InvalidArgumentException;

/**
 * StashSqlite is a wrapper around one or more SQLite databases stored on the local system. While not as quick at at
 * reading as the StashFilesystem driver this class is significantly better when it comes to clearing multiple keys
 * at once.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Sqlite implements DriverInterface
{
    protected $defaultOptions = array('filePermissions' => 0660,
                                      'dirPermissions' => 0770,
                                      'busyTimeout' => 500,
                                      'nesting' => 0,
                                      'subdriver' => 'PDO'
    );

    protected $filePerms;
    protected $dirPerms;
    protected $busyTimeout;
    protected $path;
    protected $driverClass;
    protected $nesting;
    protected $subDrivers;

    protected $disabled = false;

    /**
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $options = array_merge($this->defaultOptions, $options);

        $path = isset($options['path']) ? $options['path'] : \Stash\Utilities::getBaseDirectory($this);
        $this->path = rtrim($path, '\\/') . '/';

        $this->checkFileSystemPermissions();

        $extension = isset($options['extension']) ? strtolower($options['extension']) : 'any';
        $version = isset($options['version']) ? $options['version'] : 'any';

        $subdrivers = array();
        if(Sub\SqlitePdo::isAvailable()) {
            $subdrivers['pdo'] = '\Stash\Driver\Sub\SqlitePdo';
        }
        if(Sub\Sqlite::isAvailable()) {
            $subdrivers['sqlite'] = '\Stash\Driver\Sub\Sqlite';
        }
        if(Sub\SqlitePdo2::isAvailable()) {
            $subdrivers['pdo2'] = '\Stash\Driver\Sub\SqlitePdo2';
        }

        if($extension == 'pdo' && $version != '2' && isset($subdrivers['pdo'])) {
            $driver = $subdrivers['pdo'];
        } elseif($extension == 'sqlite' && isset($subdrivers['sqlite'])) {
            $driver = $subdrivers['sqlite'];
        } elseif($extension == 'pdo' && $version != '3' && isset($subdrivers['pdo2'])) {
            $driver = $subdrivers['pdo2'];
        } elseif(count($subdrivers) > 0 && $extension == 'any') {
            $driver = reset($subdrivers);
        } else {
            throw new RuntimeException('No sqlite extension available.');
        }

        $this->driverClass = $driver;
        $this->filePerms = $options['filePermissions'];
        $this->dirPerms = $options['dirPermissions'];
        $this->busyTimeout = $options['busyTimeout'];
        $this->nesting = $options['nesting'];

        $this->checkStatus();
    }

    /**
     * @param array $key
     * @return array
     */
    public function getData($key)
    {
        if (!($sqlDriver = $this->getSqliteDriver($key))) {
            return false;
        }

        $sqlKey = $this->makeSqlKey($key);

        if (!($data = $sqlDriver->get($sqlKey))) {
            return false;
        }

        $data['data'] = \Stash\Utilities::decode($data['data'], $data['encoding']);

        return $data;
    }

    /**
     * @param array $key
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        if (!($sqlDriver = $this->getSqliteDriver($key))) {
            return false;
        }

        $storeData = array('data' => \Stash\Utilities::encode($data),
                           'expiration' => $expiration,
                           'encoding' => \Stash\Utilities::encoding($data)
        );

        return $sqlDriver->set($this->makeSqlKey($key), $storeData, $expiration);
    }

    /**
     *
     * @param null|array $key
     * @return bool
     */
    public function clear($key = null)
    {
        if (!($databases = $this->getCacheList())) {
            return true;
        }

        if (!is_null($key)) {
            $sqlKey = $this->makeSqlKey($key);
        }

        foreach ($databases as $database) {
            if (!($driver = $this->getSqliteDriver($database, true))) {
                continue;
            }

            isset($sqlKey) ? $driver->clear($sqlKey) : $driver->clear();
            $driver->__destruct();
            unset($driver);
        }
        $this->subDrivers = array();

        return true;
    }

    /**
     *
     * @return bool
     */
    public function purge()
    {
        if (!($databases = $this->getCacheList())) {
            return true;
        }

        $expiration = time();
        foreach ($databases as $database) {
            if ($driver = $this->getSqliteDriver($database, true)) {
                $driver->purge();
            }
        }
        return true;
    }

    /**
     *
     * @param null|array $key
     * @param bool $name = false
     * @return \Stash\Driver\Sub\Sqlite
     */
    protected function getSqliteDriver($key, $name = false)
    {
        if ($name) {
            if (!is_scalar($key)) {
                return false;
            }

            $file = $key;

        } else {
            if (!is_array($key)) {
                return false;
            }

            $key = \Stash\Utilities::normalizeKeys($key);

            $nestingLevel = $this->nesting;
            $fileName = 'cache_';
            for ($i = 1; $i < $nestingLevel; $i++) {
                $fileName .= $key[$i - 1] . '_';
            }

            $file = $this->path . rtrim($fileName, '_') . '.sqlite';
        }

        if (isset($this->subDrivers[$file])) {
            return $this->subDrivers[$file];
        }

        $driverClass = $this->driverClass;

        if(is_null($driverClass))
            return false;

        $driver = new $driverClass($file, $this->dirPerms, $this->filePerms, $this->busyTimeout);

        $this->subDrivers[$file] = $driver;
        return $driver;
    }

    /**
     * Destroys the sub-drivers when this driver is unset -- required for Windows compatibility.
     *
     */
    public function __destruct()
    {
        if ($this->subDrivers) {
            foreach ($this->subDrivers as &$driver) {
                $driver->__destruct();
                unset($driver);
            }
        }
    }

    /**
     *
     * @return array|false
     */
    protected function getCacheList()
    {
        $filePath = $this->path;
        $caches = array();
        $databases = glob($filePath . '*.sqlite');
        foreach ($databases as $database) {
            $caches[] = $database;
        }

        return count($caches) > 0 ? $caches : false;
    }

    /**
     * Checks to see whether the requisite permissions are available on the specified path.
     *
     */
    protected function checkFileSystemPermissions()
    {
        if(!isset($this->path)) {
            throw new RuntimeException('Cache path was not set correctly.');
        }

        if(!is_dir($this->path)) {
            throw new InvalidArgumentException('Cache path is not a directory.');
        }

        if(!is_writable($this->path)) {
            throw new InvalidArgumentException('Cache path is not writable.');
        }
    }

    /**
     * Checks availability of the specified subdriver.
     *
     * @return bool
     */
    protected function checkStatus()
    {
        if(!static::isAvailable()) {
            throw new RuntimeException('No Sqlite extension is available.');
        }

        $driver = $this->getSqliteDriver(array('_none'));

        if(!$driver) {
            throw new RuntimeException('No Sqlite driver could be loaded.');
        }

        $driver->checkFileSystemPermissions();
    }

    /**
     * Returns whether the driver is able to run in the current environment or not. Any system checks- such as making
     * sure any required extensions are missing- should be done here.
     *
     * @return bool
     */
    static public function isAvailable()
    {
        return (Sub\SqlitePdo::isAvailable()) || (Sub\Sqlite::isAvailable()) || (Sub\SqlitePdo2::isAvailable());
    }

    /**
     * This function takes an array of strings and turns it into the sqlKey. It does this by iterating through the
     * array, running the string through sqlite_escape_string() and then combining that string to the keystring with a
     * delimiter.
     *
     * @param array $key
     * @return string
     */
    static function makeSqlKey($key)
    {
        $key = \Stash\Utilities::normalizeKeys($key, 'base64_encode');
        $path = '';
        foreach ($key as $rawPathPiece) {
            $path .= $rawPathPiece . ':::';
        }

        return $path;
    }
}
