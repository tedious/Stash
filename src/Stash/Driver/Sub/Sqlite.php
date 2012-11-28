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
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Sqlite
{
    protected $path;
    protected $driver;

    protected $creationSql = 'CREATE TABLE cacheStore (
                              key TEXT UNIQUE ON CONFLICT REPLACE,
                              expiration INTEGER,
                              encoding TEXT,
                              data BLOB
                              );
                              CREATE INDEX keyIndex ON cacheStore (key);';


    protected $filePermissions;
    protected $dirPermissions;
    protected $busyTimeout;
    protected $responseCode;

    public function __construct($path, $directoryPermission, $filePermission, $busyTimeout)
    {
        $this->path = $path;
        $this->filePermissions = $filePermission;
        $this->dirPermissions = $directoryPermission;
        $this->busyTimeout = $busyTimeout;
        $this->responseCode = 1; // SQLITE_ASSOC
    }

    public function __destruct()
    {
        $this->driver = null;
    }

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

    public function set($key, $value, $expiration)
    {
        if (!($driver = $this->getDriver())) {
            return false;
        }

        $data = base64_encode(serialize($value));

        $resetBusy = false;
        $contentLength = strlen($data);
        if ($contentLength > 100000) {
            $resetBusy = true;
            $this->setTimeout($this->busyTimeout * (ceil($contentLength / 100000))); // .5s per 100k
        }

        $query = $driver->query("INSERT INTO cacheStore (key, expiration, data)
                                  VALUES ('{$key}', '{$expiration}', '{$data}')");

        return true;
    }

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
            $query = $driver->query("DELETE FROM cacheStore WHERE key LIKE '{$key}%'");
        }
        return true;
    }

    public function purge()
    {
        if (!($driver = $this->getDriver())) {
            return false;
        }

        $driver->query('DELETE FROM cacheStore WHERE expiration < ' . time());
        $driver->query('VACUUM');
        return true;
    }

    public function checkFileSystemPermissions()
    {
        if(!isset($this->path)) {
            throw new RuntimeException('No cache path is set.');
        }

        if(!is_writable($this->path) && !is_writable(dirname($this->path))) {
            throw new InvalidArgumentException('The cache sqlite file is not writable.');
        }
    }

    static public function isAvailable()
    {
        return class_exists('SQLiteDatabase', false);
    }

    protected function setTimeout($milliseconds)
    {
        if (!($driver = $this->getDriver())) {
            return false;
        }
        $driver->busyTimeout($milliseconds);
    }

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
                if ($pos1 === false) {
                    $pos = $pos2;
                }
                if ($pos2 === false) {
                    $pos = $pos1;
                }

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

    protected function buildDriver()
    {
        if (!$db = new \SQLiteDatabase($this->path, $this->filePermissions, $errorMessage)) {
            throw new RuntimeException('Unable to open SQLite Database: ' . $errorMessage);
        }
        return $db;
    }
}
