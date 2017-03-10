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
use Stash\Utilities;
use Stash\Exception\RuntimeException;

/**
 * StashSqlite is a wrapper around one or more SQLite databases stored on the local system. While not as quick at at
 * reading as the StashFilesystem driver this class is significantly better when it comes to clearing multiple keys
 * at once.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Sqlite extends AbstractDriver
{
    protected $filePermissions;
    protected $dirPermissions;
    protected $busyTimeout;
    protected $cachePath;
    protected $driverClass;
    protected $nesting;
    protected $subDrivers;

    protected $disabled = false;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array(
            'filePermissions' => 0660,
            'dirPermissions' => 0770,
            'busyTimeout' => 500,
            'nesting' => 0,
            'subdriver' => 'PDO',
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Stash\Exception\RuntimeException
     */
    protected function setOptions(array $options = array())
    {
        $options += $this->getDefaultOptions();

        if (!isset($options['path'])) {
            $options['path'] = Utilities::getBaseDirectory($this);
        }

        $this->cachePath = rtrim($options['path'], '\\/') . DIRECTORY_SEPARATOR;
        $this->filePermissions = $options['filePermissions'];
        $this->dirPermissions = $options['dirPermissions'];
        $this->busyTimeout = $options['busyTimeout'];
        $this->nesting = max((int) $options['nesting'], 0);

        Utilities::checkFileSystemPermissions($this->cachePath, $this->dirPermissions);

        if (static::isAvailable() && Sub\SqlitePdo::isAvailable()) {
            $this->driverClass = '\Stash\Driver\Sub\SqlitePdo';
        } else {
            throw new RuntimeException('No sqlite extension available.');
        }

        $driver = $this->getSqliteDriver(array('_none'));
        if (!$driver) {
            throw new RuntimeException('No Sqlite driver could be loaded.');
        }
    }

    /**
     * {@inheritdoc}
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

        $data['data'] = Utilities::decode($data['data'], $data['encoding']);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        if (!($sqlDriver = $this->getSqliteDriver($key))) {
            return false;
        }

        $storeData = array('data' => Utilities::encode($data),
                           'expiration' => $expiration,
                           'encoding' => Utilities::encoding($data)
        );

        return $sqlDriver->set($this->makeSqlKey($key), $storeData, $expiration);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function purge()
    {
        if (!($databases = $this->getCacheList())) {
            return true;
        }

        foreach ($databases as $database) {
            if ($driver = $this->getSqliteDriver($database, true)) {
                $driver->purge();
            }
        }

        return true;
    }

    /**
     *
     * @param  null|array               $key
     * @param  bool                     $name = false
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

            $key = Utilities::normalizeKeys($key);

            $nestingLevel = $this->nesting;
            $fileName = 'cache_';
            for ($i = 1; $i < $nestingLevel; $i++) {
                $fileName .= $key[$i - 1] . '_';
            }

            $file = $this->cachePath . rtrim($fileName, '_') . '.sqlite';
        }

        if (isset($this->subDrivers[$file])) {
            return $this->subDrivers[$file];
        }

        $driverClass = $this->driverClass;

        if (is_null($driverClass)) {
            return false;
        }

        $driver = new $driverClass($file, $this->dirPermissions, $this->filePermissions, $this->busyTimeout);

        $this->subDrivers[$file] = $driver;

        return $driver;
    }

    /**
     * Destroys the sub-drivers when this driver is unset -- required for Windows compatibility.
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
        $filePath = $this->cachePath;
        $caches = array();
        $databases = glob($filePath . '*.sqlite');
        foreach ($databases as $database) {
            $caches[] = $database;
        }

        return count($caches) > 0 ? $caches : false;
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable()
    {
        return Sub\SqlitePdo::isAvailable();
    }

    /**
     * This function takes an array of strings and turns it into the sqlKey. It does this by iterating through the
     * array, running the string through sqlite_escape_string() and then combining that string to the keystring with a
     * delimiter.
     *
     * @param  array  $key
     * @return string
     */
    public static function makeSqlKey($key)
    {
        $key = Utilities::normalizeKeys($key, 'base64_encode');
        $path = '';
        foreach ($key as $rawPathPiece) {
            $path .= $rawPathPiece . ':::';
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent()
    {
        return true;
    }
}
