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

namespace Stash\Handlers;

use Stash;

/**
 * StashMemcached is a wrapper around the popular memcached server. StashMemcached supports both memcached php
 * extensions and allows access to all of their options as well as all Stash features (including hierarchical caching).
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class Memcached implements \Stash\Handler
{
	/**
	 * Memcache subhandler used by this class.
	 *
	 * @var StashMemcached_Memcached|StashMemcached_Memcache
	 */
	protected $memcached;

	/**
	 *
	 * * servers - An array of servers, with each server represented by its own array (array(host, port, [weight])). If
	 * not passed the default is array('127.0.0.1', 11211).
	 *
	 * * extension - Which php extension to use, either 'memcached' or 'memcache'. Defaults to memcached with memcache
	 * as a fallback.
	 *
	 * * Options can be passed to the "memcached" handler by adding them to the options array. The memcached extension
	 * defined options using contants, ie Memcached::OPT_*. By passing in the * portion ('compression' for
	 * Memcached::OPT_COMPRESSION) and its respective option. Please see the php manual for the specific options
	 * (http://us2.php.net/manual/en/memcached.constants.php)
	 *
	 * @param array $options
	 */
	public function __construct($options = array())
	{
		if(!isset($options['servers']))
			$options['servers'] = array('127.0.0.1', 11211);

		if(!is_array($options['servers']))
			throw new StashMemcachedError('Server list required to be an array.');

		if(is_scalar($options['servers'][0]))
		{
			$servers = array($options['servers']);
		}else{
			$servers = $options['servers'];
		}


		if(!isset($options['extension']))
			$options['extension'] = 'any';

		$extension = strtolower($options['extension']);

		if(class_exists('Memcached', false) && $extension != 'memcache')
		{
			$this->memcached = new StashMemcached_Memcached();
		}elseif(class_exists('Memcache', false) && $extension != 'memcached'){
			$this->memcached = new StashMemcached_Memcache();
		}else{
			throw new StashMemcachedError('Unable to load either memcache extension.');
		}

		if($this->memcached->initialize($servers, $options))
			return;
	}

	/**
	 * Empty destructor to maintain a standardized interface across all handlers.
	 *
	 */
	public function __destruct()
	{
	}

	/**
	 *
	 * @return array
	 */
	public function getData($key)
	{
		$keyString = $this->makeKeyString($key);
		return $this->memcached->get($keyString);
	}

	/**
	 *
	 * @param array $data
	 * @param int $expiration
	 * @return bool
	 */
	public function storeData($key, $data, $expiration)
	{
		$keyString = $this->makeKeyString($key);
		return $this->memcached->set($keyString, $data, $expiration);
	}

	/**
	 *
	 * @param null|array $key
	 * @return bool
	 */
	public function clear($key = null)
	{
		$this->keyCache = array();
		if(is_null($key))
		{
			$this->memcached->flush();
		}else{
			$keyString = $this->makeKeyString($key, true);
			$this->memcached->inc($keyString);
			$this->keyCache = array();
			$this->makeKeyString($key);
		}
		$this->keyCache = array();
		return true;
	}

	/**
	 *
	 * @return bool
	 */
	public function purge()
	{
		return true;
	}

	protected function makeKeyString($key, $path = false)
	{
		// array(name, sub);
		// a => name, b => sub;

		$key = \StashUtilities::normalizeKeys($key);

		$keyString = 'cache:::';
		foreach($key as $name)
		{
			//a. cache:::name
			//b. cache:::name0:::sub
			$keyString .= $name;

			//a. :pathdb::cache:::name
			//b. :pathdb::cache:::name0:::sub
			$pathKey = ':pathdb::' . $keyString;
			$pathKey = md5($pathKey);

			if(isset($this->keyCache[$pathKey]))
			{
				$index = $this->keyCache[$pathKey];
			}else{
				$index = $this->memcached->cas($pathKey, 0);
				$this->keyCache[$pathKey] = $index;
			}

			//a. cache:::name0:::
			//b. cache:::name0:::sub1:::
			$keyString .= '_' . $index . ':::';
		}

		return $path ? $pathKey : md5($keyString);
	}

	/**
	 *
	 * @return bool
	 */
	static function canEnable()
	{
		return class_exists('Memcached', false) || class_exists('Memcache', false);
	}
}


/**
 * StashMemcached_Memcached a subhandler for the StashMemcached class that provides access to memcached using the php
 * "memcached" extension. This is the preferred method for accessing memcached as it is the newest and most feature
 * complete extension.
 *
 * @internal
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class StashMemcached_Memcached
{
	/**
	 * @var Memcached
	 */
	protected $memcached;

	public function initialize($servers, $options = array())
	{
		// build this array here instead of as a class variable since the constants are only defined if the extension
		// exists
		$memOptions = array(
			'COMPRESSION',
			'SERIALIZER',
			'PREFIX_KEY',
			'HASH',
			'DISTRIBUTION',
			'LIBKETAMA_COMPATIBLE',
			'BUFFER_WRITES',
			'BINARY_PROTOCOL',
			'NO_BLOCK',
			'TCP_NODELAY',
			'SOCKET_SEND_SIZE',
			'SOCKET_RECV_SIZE',
			'CONNECT_TIMEOUT',
			'RETRY_TIMEOUT',
			'SEND_TIMEOUT',
			'RECV_TIMEOUT',
			'POLL_TIMEOUT',
			'CACHE_LOOKUPS',
			'SERVER_FAILURE_LIMIT');

		$memcached = new Memcached();


		$memcached->addServers($servers);

		foreach($options as $name => $value)
		{
			$name = strtoupper($name);

			if(!in_array($name, $memOptions) || !defined('Memcached::OPT_' . $name))
				continue;

			switch($name)
			{
				case 'HASH':
					$value = strtoupper($value);
					if(!defined('\Memcached::HASH_' . $value))
						throw new StashMemcached_MemcachedError('Memcached option ' . $name . ' requires valid memcache hash option value');
					$value = constant('Memcached::HASH_' . $value);
						break;

				case 'DISTRIBUTION':
					$value = strtoupper($value);
					if(!defined('\Memcached::DISTRIBUTION_' . $value))
						throw new StashMemcached_MemcachedError('Memcached option ' . $name . ' requires valid memcache distribution option value');
					$value = constant('Memcached::DISTRIBUTION_' . $value);
						break;

				case 'SERIALIZER':
					$value = strtoupper($value);
					if(!defined('\Memcached::SERIALIZER_' . $value))
						throw new StashMemcached_MemcachedError('Memcached option ' . $name . ' requires valid memcache serializer option value');
					$value = constant('Memcached::SERIALIZER_' . $value);
						break;

				case 'SOCKET_SEND_SIZE':
				case 'SOCKET_RECV_SIZE':
				case 'CONNECT_TIMEOUT':
				case 'RETRY_TIMEOUT':
				case 'SEND_TIMEOUT':
				case 'RECV_TIMEOUT':
				case 'POLL_TIMEOUT':
				case 'SERVER_FAILURE_LIMIT':
					if(!is_numeric($value))
						throw new StashMemcached_MemcachedError('Memcached option ' . $name . ' requires numeric value');
					break;

				case 'PREFIX_KEY':
					if(!is_string($value))
						throw new StashMemcached_MemcachedError('Memcached option ' . $name . ' requires string value');
					break;

				case 'COMPRESSION':
				case 'LIBKETAMA_COMPATIBLE':
				case 'BUFFER_WRITES':
				case 'BINARY_PROTOCOL':
				case 'NO_BLOCK':
				case 'TCP_NODELAY':
				case 'CACHE_LOOKUPS':
					if(!is_bool($value))
						throw new StashMemcached_MemcachedError('Memcached option ' . $name . ' requires boolean value');
					break;
			}

			$memcached->setOption(constant('\Memcached::OPT_' . $name), $value);
		}

		$this->memcached = $memcached;
	}

	public function set($key, $value, $expire = null)
	{
		return $this->memcached->set($key, array('data' => $value, 'expiration' => $expire), $expire);
	}

	public function get($key)
	{
		$value = $this->memcached->get($key);
		if($value === false && $this->memcached->getResultCode() == Memcached::RES_NOTFOUND)
			return false;

		return $value;
	}

	public function cas($key, $value)
	{
		if(($rValue = $this->memcached->get($key, null, $token)) !== false)
			return $rValue;

		if($this->memcached->getResultCode() === Memcached::RES_NOTFOUND)
		{
			$this->memcached->add($key, $value);
		}else{
			$this->memcached->cas($token, $key, $value);
		}
		return $value;
	}

	public function inc($key)
	{
		$this->cas($key, 0);
		return $this->memcached->increment($key);
	}

	public function flush()
	{
		$this->memcached->flush();
	}
}

/**
 * StashMemcached_Memcache a subhandler for the StashMemcached class that provides access to memcached using the php
 * "memcache" extension. This is not the best way to access memcached but is here for systems that require it.
 *
 * @internal
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class StashMemcached_Memcache extends StashMemcached_Memcached
{
	public function initialize($servers, $options = array())
	{
		$memcached = new Memcache();

		foreach($servers as $server)
		{
			$host = $server[0];
			$port = isset($server[1]) ? $server[1] : 11211;
			$weight = isset($server[2]) ? $server[2] : null;


			if(is_numeric($weight))
			{
				$memcached->addServer($host, $port, true, $weight);
			}else{
				$memcached->addServer($host, $port);
			}
		}

		$this->memcached = $memcached;
	}

	public function set($key, $value, $expire = null)
	{
		return $this->memcached->set($key, array('data' => $value, 'expiration' => $expire), null, $expire);
	}

	public function get($key)
	{
		return @$this->memcached->get($key);
	}

	public function cas($key, $value)
	{
		if(($return = @$this->memcached->get($key)) !== false)
			return $return;

		$this->memcached->set($key, $value);
		return $value;
	}
}



class StashMemcachedError extends \Stash\Error {}
class StashMemcached_MemcachedError extends \Stash\Error {}
?>