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
 * @version    Release: 0.9.3
 */

namespace Stash\Handlers;

use Stash;

/**
 * StashSqlite is a wrapper around one or more SQLite databases stored on the local system. While not as quick at at
 * reading as the StashFilesystem handler this class is signifigantly better when it comes to clearing multiple keys
 * at once.
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class Sqlite implements \Stash\Handler
{
	protected $defaultOptions = array(
						'filePermissions'	=> 0660,
						'dirPermissions'	=> 0770,
						'busyTimeout'		=> 500,
						'nesting'		=> 0,
						'subhandler'		=> 'PDO'
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
		if($lastChar != '/' && $lastChar != '\'')
			$path .= '/';

		$this->path = $path;
		if(!isset($options['extension']))
			$options['extension'] = 'pdo';

		$extension = isset($options['extension']) ? strtolower($options['extension']) : 'pdo';

		if($extension == 'sqlite')
		{
			$handler = '\Stash\Handlers\Sqlite_SQLite';
		}else{
			if(isset($options['version']) && $options['version'] == 2)
			{
				$handler = '\Stash\Handlers\Sqlite_PDO2';
			}else{
				$handler = '\Stash\Handlers\Sqlite_PDO';
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
		if(!($sqlHandler = $this->getSqliteHandler($key)))
			return false;

		$sqlKey = $this->makeSqlKey($key);

		if(!($data = $sqlHandler->get($sqlKey)))
		   return false;

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
		if(!($sqlHandler = $this->getSqliteHandler($key)))
			return false;

		$sqlKey = $this->makeSqlKey($key);

		$storeData = array('data'		=> \Stash\Utilities::encode($data),
						   'expiration'	=> $expiration,
						   'encoding'	=> \Stash\Utilities::encoding($data));

		return $sqlHandler->set($sqlKey, $storeData, $expiration);
	}

	/**
	 *
	 * @param null|array $key
	 * @return bool
	 */
	public function clear($key = null)
	{
		if(!($databases = $this->getCacheList()))
			return true;

		if(!is_null($key))
			$sqlKey = $this->makeSqlKey($key);

		foreach($databases as $database)
		{
			if(!($handler = $this->getSqliteHandler($database, true)))
				continue;
			

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
		if(!($databases = $this->getCacheList()))
			return true;

		$expiration = time();
		foreach($databases as $database)
		{
			if(!($handler = $this->getSqliteHandler($database, true)))
				continue;

			$handler->purge();
		}
		return true;
	}

	/**
	 *
	 * @param null|array $key
	 * @param bool $name = false
	 * @return Sqlite_SQLite
	 */
	protected function getSqliteHandler($key, $name = false)
	{
		if($name)
		{
			if(!is_scalar($key))
				return false;

			$file = $key;

		}else{
			if(!is_array($key))
				return false;

			$key = \Stash\Utilities::normalizeKeys($key);

			$nestingLevel = $this->nesting;
			$fileName = 'cache_';
			for($i = 1; $i < $nestingLevel; $i++)
				$fileName .= $key[$i-1] . '_';

			$file = $this->path . rtrim($fileName, '_') . '.sqlite';
		}

		if(isset($this->subHandlers[$file]))
			return $this->subHandlers[$file];

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
		if($this->subHandlers) {
			foreach($this->subHandlers as &$handler) {
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
		foreach($databases as $database)
			$caches[] = $database;

		return count($caches) > 0 ? $caches : false;
	}

	/**
	 * Returns whether the handler is able to run in the current environment or not. Any system checks- such as making
	 * sure any required extensions are missing- should be done here.
	 *
	 * @return bool
	 */
	static function canEnable()
	{
		$drivers = class_exists('\PDO', false) ? \PDO::getAvailableDrivers() : array();
		return (in_array('sqlite', $drivers) || in_array('sqlite2', $drivers)) || class_exists('SQLiteDatabase', false);
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
		foreach($key as $rawPathPiece)
			$path .= $rawPathPiece . ':::';

		return $path;
	}
}


class Sqlite_SQLite
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
		$this->responseCode = SQLITE_ASSOC;
	}

	public function __destruct()
	{
		unset($this->handler);
	}

	public function get($key)
	{
		if(!($handler = $this->getHandler()))
			return false;

		$query = $handler->query("SELECT * FROM cacheStore WHERE key LIKE '{$key}'");
		if($query === false)
			return false;

		if($resultArray = $query->fetch($this->responseCode))
			return unserialize(base64_decode($resultArray['data']));

		return false;
	}

	public function set($key, $value, $expiration)
	{
		if(!($handler = $this->getHandler()))
			return false;

		$data = base64_encode(serialize($value));

		$resetBusy = false;
		$contentLength = strlen($data);
		if($contentLength > 100000)
		{
			$resetBusy = true;
			$this->setTimeout($this->busyTimeout * (ceil($contentLength/100000))); // .5s per 100k
		}

		$query = $handler->query("INSERT INTO cacheStore (key, expiration, data)
											VALUES ('{$key}', '{$expiration}', '{$data}')");

		return true;
	}

	public function clear($key = null)
	{
		// return true if the cache is already empty
		if(!($handler = $this->getHandler()))
			return true;

		if(!isset($key))
		{
			unset($handler);
			unset($this->handler);
			$this->handler = false;
			\Stash\Utilities::deleteRecursive($this->path);
		}else{
			$query = $handler->query("DELETE FROM cacheStore WHERE key LIKE '{$key}%'");
		}
		return true;
	}

	public function purge()
	{
		if(!($handler = $this->getHandler()))
			return false;

		$handler->query('DELETE FROM cacheStore WHERE expiration < ' . time());
		$handler->query('VACUUM');
		return true;
	}

	protected function setTimeout($milliseconds)
	{
		if(!($handler = $this->getHandler()))
			return false;
		$handler->busyTimeout($milliseconds);
	}

	protected function getHandler()
	{
		if(isset($this->handler) && $this->handler !== false)
			return $this->handler;

		if(!file_exists($this->path))
		{
			$dir = $this->path;

			// Since PHP will understand paths with mixed slashes- both the windows \ and unix / variants- we have
			// to test for both and see which one is the last in the string.
			$pos1 = strrpos($this->path, '/');
			$pos2 = strrpos($this->path, '\\');

			if($pos1 || $pos2)
			{
				if($pos1 === false)
					$pos = $pos2;
				if($pos2 === false)
					$pos = $pos1;

				$pos = $pos1 >= $pos2 ? $pos1 : $pos2;
				$dir = substr($this->path, 0, $pos);
			}

			if(!is_dir($dir))
				mkdir($dir, $this->dirPermissions, true);
			$runInstall = true;
		}else{
			$runInstall = false;
		}

		$db = $this->buildHandler();

		if($runInstall && !$db->query($this->creationSql))
		{
			unlink($path);
			throw new StashSqliteError('Unable to set SQLite: structure');
		}
		$this->handler = $db;

		// prevent the cache from getting hungup waiting on a return
		$this->setTimeout($this->busyTimeout);

		return $db;
	}

	protected function buildHandler()
	{
		if(!$db = new \SQLiteDatabase($this->path, $this->filePermissions, $errorMessage))
			throw new StashSqliteError('Unable to open SQLite Database: '. $errorMessage);
		return $db;
	}
}

class Sqlite_PDO extends Sqlite_SQLite
{
	public function __construct($path, $directoryPermissiom, $filePermission, $busyTimeout)
	{
		$this->path = $path;
		$this->filePermissions = $filePermission;
		$this->dirPermissions = $directoryPermissiom;
		$this->busyTimeout = $busyTimeout;
		$this->responseCode = \PDO::FETCH_ASSOC;
	}


	protected function setTimeout($milliseconds)
	{
		if(!($handler = $this->getHandler()))
			return false;

		$timeout = ceil($milliseconds/1000);
		$handler->setAttribute(\PDO::ATTR_TIMEOUT, $timeout);
	}

	protected function buildHandler()
	{
		$db = new \PDO('sqlite:' . $this->path);
		return $db;
	}
}

class Sqlite_PDO2 extends Sqlite_PDO
{
	protected function buildHandler()
	{
		$db = new \PDO('sqlite2:' . $this->path);
		return $db;
	}
}

class StashSqliteError extends \Stash\Error {}
?>