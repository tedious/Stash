<?php
/**
 * Stash
 *
 * Copyright (c) 2009-2011, Robert Hafner <tedivm@tedivm.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Robert Hafner nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Stash
 * @subpackage Handlers
 * @author     Robert Hafner <tedivm@tedivm.com>
 * @copyright  2009-2011 Robert Hafner <tedivm@tedivm.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://code.google.com/p/stash/
 * @since      File available since Release 0.9.1
 * @version    Release: 0.9.5
 */

namespace Stash\Handler\Sub;

use Stash\Exception\SqliteException;
use Stash\Handler\EnabledInterface;

class Sqlite implements EnabledInterface
{
    protected $path;
    protected $handler;

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

    public function __construct($path, $directoryPermissiom, $filePermission, $busyTimeout)
    {
        $this->path = $path;
        $this->filePermissions = $filePermission;
        $this->dirPermissions = $directoryPermissiom;
        $this->busyTimeout = $busyTimeout;
        $this->responseCode = 1; // SQLITE_ASSOC
    }

    public function __destruct()
    {
        unset($this->handler);
    }

    public function get($key)
    {
        if (!($handler = $this->getHandler())) {
            return false;
        }

        $query = $handler->query("SELECT * FROM cacheStore WHERE key LIKE '{$key}'");
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
        if (!($handler = $this->getHandler())) {
            return false;
        }

        $data = base64_encode(serialize($value));

        $resetBusy = false;
        $contentLength = strlen($data);
        if ($contentLength > 100000) {
            $resetBusy = true;
            $this->setTimeout($this->busyTimeout * (ceil($contentLength / 100000))); // .5s per 100k
        }

        $query = $handler->query("INSERT INTO cacheStore (key, expiration, data)
                                  VALUES ('{$key}', '{$expiration}', '{$data}')");

        return true;
    }

    public function clear($key = null)
    {
        // return true if the cache is already empty
        if (!($handler = $this->getHandler())) {
            return true;
        }

        if (!isset($key)) {
            unset($handler);
            unset($this->handler);
            $this->handler = false;
            \Stash\Utilities::deleteRecursive($this->path);
        } else {
            $query = $handler->query("DELETE FROM cacheStore WHERE key LIKE '{$key}%'");
        }
        return true;
    }

    public function purge()
    {
        if (!($handler = $this->getHandler())) {
            return false;
        }

        $handler->query('DELETE FROM cacheStore WHERE expiration < ' . time());
        $handler->query('VACUUM');
        return true;
    }

    public function canEnable()
    {
        return class_exists('SQLiteDatabase', false);
    }

    protected function setTimeout($milliseconds)
    {
        if (!($handler = $this->getHandler())) {
            return false;
        }
        $handler->busyTimeout($milliseconds);
    }

    protected function getHandler()
    {
        if (isset($this->handler) && $this->handler !== false) {
            return $this->handler;
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

        $db = $this->buildHandler();

        if ($runInstall && !$db->query($this->creationSql)) {
            unlink($path);
            throw new SqliteException('Unable to set SQLite: structure');
        }
        $this->handler = $db;

        // prevent the cache from getting hungup waiting on a return
        $this->setTimeout($this->busyTimeout);

        return $db;
    }

    protected function buildHandler()
    {
        if (!$db = new \SQLiteDatabase($this->path, $this->filePermissions, $errorMessage)) {
            throw new SqliteException('Unable to open SQLite Database: ' . $errorMessage);
        }
        return $db;
    }
}
