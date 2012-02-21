<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Handler;

use Stash;
use Stash\Exception\SqliteException;

/**
 * StashSqlite is a wrapper around one or more SQLite databases stored on the local system. While not as quick at at
 * reading as the StashFilesystem handler this class is signifigantly better when it comes to clearing multiple keys
 * at once.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Sqlite implements HandlerInterface
{
    protected $defaultOptions = array('filePermissions' => 0660,
                                      'dirPermissions' => 0770,
                                      'busyTimeout' => 500,
                                      'nesting' => 0,
                                      'subhandler' => 'PDO'
    );

    protected $filePerms;
    protected $dirPerms;
    protected $busyTimeout;
    protected $path;
    protected $handlerClass;
    protected $nesting;
    protected $subHandlers;

    /**
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        $options = array_merge($this->defaultOptions, $options);

        $path = isset($options['path']) ? $options['path'] : \Stash\Utilities::getBaseDirectory($this);
        $lastChar = substr($path, -1);
        if ($lastChar != '/' && $lastChar != '\'') {
            $path .= '/';
        }

        $this->path = $path;
        if (!isset($options['extension'])) {
            $options['extension'] = 'pdo';
        }

        $extension = isset($options['extension']) ? strtolower($options['extension']) : 'pdo';

        if ($extension == 'sqlite') {
            $handler = '\Stash\Handler\Sub\Sqlite';
        } else {
            if (isset($options['version']) && $options['version'] == 2) {
                $handler = '\Stash\Handler\Sub\SqlitePdo2';
            } else {
                $handler = '\Stash\Handler\Sub\SqlitePdo';
            }
        }

        $this->handlerClass = $handler;
        $this->filePerms = $options['filePermissions'];
        $this->dirPerms = $options['dirPermissions'];
        $this->busyTimeout = $options['busyTimeout'];
        $this->nesting = $options['nesting'];
    }

    /**
     *
     * @return array
     */
    public function getData($key)
    {
        if (!($sqlHandler = $this->getSqliteHandler($key))) {
            return false;
        }

        $sqlKey = $this->makeSqlKey($key);

        if (!($data = $sqlHandler->get($sqlKey))) {
            return false;
        }

        $data['data'] = \Stash\Utilities::decode($data['data'], $data['encoding']);

        return $data;
    }

    /**
     *
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        if (!($sqlHandler = $this->getSqliteHandler($key))) {
            return false;
        }

        $sqlKey = $this->makeSqlKey($key);

        $storeData = array('data' => \Stash\Utilities::encode($data),
                           'expiration' => $expiration,
                           'encoding' => \Stash\Utilities::encoding($data)
        );

        return $sqlHandler->set($sqlKey, $storeData, $expiration);
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
            if (!($handler = $this->getSqliteHandler($database, true))) {
                continue;
            }


            isset($sqlKey) ? $handler->clear($sqlKey) : $handler->clear();
            $handler->__destruct();
            unset($handler);
        }
        $this->subHandlers = array();

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
            if (!($handler = $this->getSqliteHandler($database, true))) {
                continue;
            }

            $handler->purge();
        }
        return true;
    }

    /**
     *
     * @param null|array $key
     * @param bool $name = false
     * @return \Stash\Handler\Sub\Sqlite
     */
    protected function getSqliteHandler($key, $name = false)
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

        if (isset($this->subHandlers[$file])) {
            return $this->subHandlers[$file];
        }

        $handlerClass = $this->handlerClass;
        $handler = new $handlerClass($file, $this->dirPerms, $this->filePerms, $this->busyTimeout);

        $this->subHandlers[$file] = $handler;
        return $handler;
    }

    /**
     * Destroys the sub-handlers when this handler is unset -- required for Windows compatibility.
     *
     */
    public function __destruct()
    {
        if ($this->subHandlers) {
            foreach ($this->subHandlers as &$handler) {
                $handler->__destruct();
                unset($handler);
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
     * Returns whether the handler is able to run in the current environment or not. Any system checks- such as making
     * sure any required extensions are missing- should be done here.
     *
     * @return bool
     */
    public function canEnable()
    {
        $handler = $this->getSqliteHandler(array('_none'));

        return $handler->canEnable();
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

