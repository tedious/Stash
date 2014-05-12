<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Driver\Sub;

use Stash\Exception\RuntimeException;
use Stash\Exception\InvalidArgumentException;

/**
 * Class Sqlite
 *
 * This SQLite subdriver is used by the main Sqlite driver for interacting with the SQLite extension.
 *
 * @internal
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Sqlite
{
    /**
     * Directory where the SQLite databases are stored.
     *
     * @var string
     */
    protected $path;

    /**
     * Output of buildDriver, used to interact with the relevant SQLite extension.
     *
     * @var \SQLiteDatabase
     */
    protected $driver;

    /**
     * The SQLite query used to generate the database.
     *
     * @var string
     */
    protected $creationSql = 'CREATE TABLE cacheStore (
                              key TEXT UNIQUE ON CONFLICT REPLACE,
                              expiration INTEGER,
                              encoding TEXT,
                              data BLOB
                              );
                              CREATE INDEX keyIndex ON cacheStore (key);';

    /**
     * File permissions of new SQLite databases.
     *
     * @var string
     */
    protected $filePermissions;

    /**
     * File permissions of new directories leading to SQLite databases.
     *
     * @var string
     */
    protected $dirPermissions;

    /**
     * Amounts of time to wait for the SQLite engine before timing out.
     *
     * @var int milliseconds
     */
    protected $busyTimeout;

    /**
     * The appropriate response code to use when retrieving data.
     *
     * @var int
     */
    protected $responseCode;

    /**
     * @param string $path
     * @param string $directoryPermission
     * @param string $filePermission
     * @param int    $busyTimeout
     */
    public function __construct($path, $directoryPermission, $filePermission, $busyTimeout)
    {
        $this->path = $path;
        $this->filePermissions = $filePermission;
        $this->dirPermissions = $directoryPermission;
        $this->busyTimeout = $busyTimeout;
        $this->responseCode = 1; // SQLITE_ASSOC
    }

    /**
     * Clear out driver, closing file sockets.
     */
    public function __destruct()
    {
        $this->driver = null;
    }

    /**
     * Retrieves data from cache store.
     *
     * @param  string     $key
     * @return bool|mixed
     */
    public function get($key)
    {
        if (!($driver = $this->getDriver())) {
            return false;
        }

        $query = $driver->query("SELECT * FROM cacheStore WHERE key LIKE '{$key}'");
        if ($query === false) {
            return false;
        }

        if ($resultArray = $query->fetch($this->responseCode)) {
            return unserialize(base64_decode($resultArray['data']));
        }

        return false;
    }

    /**
     * Stores data in sqlite database.
     *
     * @param  string $key
     * @param  mixed  $value
     * @param  int    $expiration
     * @return bool
     */
    public function set($key, $value, $expiration)
    {
        if (!($driver = $this->getDriver())) {
            return false;
        }

        $data = base64_encode(serialize($value));

        $contentLength = strlen($data);
        if ($contentLength > 100000) {
            $this->setTimeout($this->busyTimeout * (ceil($contentLength / 100000))); // .5s per 100k
        }

        $driver->query("INSERT INTO cacheStore (key, expiration, data)
                                  VALUES ('{$key}', '{$expiration}', '{$data}')");

        return true;
    }

    /**
     * Clears data from database. If a key is defined only it and it's children are removed. If everything is set to be
     * cleared then the database itself is deleted off disk.
     *
     * @param  null|string $key
     * @return bool
     */
    public function clear($key = null)
    {
        // return true if the cache is already empty
        if (!($driver = $this->getDriver())) {
            return true;
        }

        if (!isset($key)) {
            unset($driver);
            $this->driver = null;
            $this->driver = false;
            \Stash\Utilities::deleteRecursive($this->path);
        } else {
            $driver->query("DELETE FROM cacheStore WHERE key LIKE '{$key}%'");
        }

        return true;
    }

    /**
     * Old data is removed and the "vacuum" operation is run.
     *
     * @return bool
     */
    public function purge()
    {
        if (!($driver = $this->getDriver())) {
            return false;
        }

        $driver->query('DELETE FROM cacheStore WHERE expiration < ' . time());
        $driver->query('VACUUM');

        return true;
    }

    /**
     * Filesystem integrity and permissions are checked and exceptions thrown for relevant issues.
     *
     * @throws \Stash\Exception\InvalidArgumentException
     * @throws \Stash\Exception\RuntimeException
     */
    public function checkFileSystemPermissions()
    {
        if (!isset($this->path)) {
            throw new RuntimeException('No cache path is set.');
        }

        if (!is_writable($this->path) && !is_writable(dirname($this->path))) {
            throw new InvalidArgumentException('The cache sqlite file is not writable.');
        }
    }

    /**
     * Returns true if the SQLiteDatabase class exists.
     *
     * @return bool
     */
    public static function isAvailable()
    {
        return class_exists('SQLiteDatabase', false);
    }

    /**
     * Tells the SQLite driver how long to wait for data to be written.
     *
     * @param  int  $milliseconds
     * @return bool
     */
    protected function setTimeout($milliseconds)
    {
        if (!($driver = $this->getDriver())) {
            return false;
        }
        $driver->busyTimeout($milliseconds);
    }

    /**
     * Retrieves the relevant SQLite driver, creating the database file if necessary.
     *
     * @return \SQLiteDatabase
     * @throws \Stash\Exception\RuntimeException
     */
    protected function getDriver()
    {
        if (isset($this->driver) && $this->driver !== false) {
            return $this->driver;
        }

        if (!file_exists($this->path)) {
            $dir = $this->path;

            // Since PHP will understand paths with mixed slashes- both the windows \ and unix / variants- we have
            // to test for both and see which one is the last in the string.
            $pos1 = strrpos($this->path, '/');
            $pos2 = strrpos($this->path, '\\');

            if ($pos1 || $pos2) {
                $pos = $pos1 >= $pos2 ? $pos1 : $pos2;
                $dir = substr($this->path, 0, $pos);
            }

            if (!is_dir($dir)) {
                mkdir($dir, $this->dirPermissions, true);
            }
            $runInstall = true;
        } else {
            $runInstall = false;
        }

        $db = $this->buildDriver();

        if ($runInstall && !$db->query($this->creationSql)) {
            unlink($this->path);
            throw new RuntimeException('Unable to set SQLite: structure');
        }
        $this->driver = $db;

        // prevent the cache from getting hungup waiting on a return
        $this->setTimeout($this->busyTimeout);

        return $db;
    }

    /**
     * Creates the actual database driver itself.
     *
     * @return \SQLiteDatabase
     * @throws \Stash\Exception\RuntimeException
     */
    protected function buildDriver()
    {
        if (!$db = new \SQLiteDatabase($this->path, $this->filePermissions, $errorMessage)) {
            throw new RuntimeException('Unable to open SQLite Database: ' . $errorMessage);
        }

        return $db;
    }
}
