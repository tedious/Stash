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

/**
 * StashSqlite is a wrapper around the xcache php extension, which allows developers to store data in memory.
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 * @codeCoverageIgnore Just until I figure out how to get phpunit working over http, or xcache over cli
 */
class StashXcache extends StashApc
{
	protected $user;
	protected $password;

	public function __construct($options = array())
	{
		if(isset($options['user']))
			$this->user = $options['user'];

		if(isset($options['user']))
			$this->password = $options['password'];

		parent::__construct($options);
	}

	/**
	 * Empty destructor to maintain a standardized interface across all handlers.
	 *
	 */
	public function __destruct()
	{
	}

	/**
	 * This function should return the data array, exactly as it was received by the storeData function, or false if it
	 * is not present. This array should have a value for "createdOn" and for "return", which should be the data the
	 * main script is trying to store.
	 *
	 * @return array
	 */
	public function getData($key)
	{
		$keyString = $this->makeKey($key);
		if(!$keyString)
			return false;

		if(!xcache_isset($keyString))
			return false;

		$data = xcache_get($keyString);
		return unserialize($data);
	}

	/**
	 * This function takes an array as its first argument and the expiration time as the second. This array contains two
	 * items, "createdOn" describing the first time the item was called and "return", which is the data that needs to be
	 * stored. This function needs to store that data in such a way that it can be retrieced exactly as it was sent. The
	 * expiration time needs to be stored with this data.
	 *
	 * @param array $data
	 * @param int $expiration
	 * @return bool
	 */
	public function storeData($key, $data, $expiration)
	{
		$keyString = self::makeKey($key);
		if(!$keyString)
			return false;

		$cacheTime = $this->getCacheTime($expiration);
		return xcache_set($keyString, serialize(array('return' => $data, 'expiration' => $expiration)), $cacheTime);
	}


	/**
	 * This function should clear the cache tree using the key array provided. If called with no arguments the entire
	 * cache needs to be cleared.
	 *
	 * @param null|array $key
	 * @return bool
	 */
	public function clear($key = null)
	{
		if(isset($key))
			$key = array();

		$keyString = $this->makeKey($key);
		if(!$keyString)
			return false;

		return xcache_unset_by_prefix($keyString);
	}

	/**
	 * This function is used to remove expired items from the cache.
	 *
	 * @return bool
	 */
	public function purge()
	{
		return $this->clear();

/*

		// xcache loses points for its login choice, but not as many as it gained for xcache_unset_by_prefix
		$original = array();
		if(isset($_SERVER['PHP_AUTH_USER']))
		{
			$original['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
			unset($_SERVER['PHP_AUTH_USER']);
		}

		if(isset($_SERVER['PHP_AUTH_PW']))
		{
			$original['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'];
			unset($_SERVER['PHP_AUTH_USER']);
		}

		if(isset($this->user))
			$_SERVER['PHP_AUTH_USER'] = $this->user;

		if(isset($this->password))
			$_SERVER['PHP_AUTH_PW'] = $this->password;

		if(isset($key) && function_exists('xcache_unset_by_prefix'))
		{
			$keyString = self::makeKey($key);
			if($keyString = self::makeKey($key))
			{
				// this is such a sexy function, soooo many points to xcache
				$return = xcache_unset_by_prefix($keyString);
			}else{
				return false;
			}
		}else{
			xcache_clear_cache(XC_TYPE_VAR, 0);
			$return = true;
		}

		if(isset($original['PHP_AUTH_USER']))
		{
			$_SERVER['PHP_AUTH_USER'] = $original['PHP_AUTH_USER'];
		}elseif(isset($_SERVER['PHP_AUTH_USER'])){
			unset($_SERVER['PHP_AUTH_USER']);
		}

		if(isset($original['PHP_AUTH_PW']))
		{
			$_SERVER['PHP_AUTH_PW'] = $original['PHP_AUTH_PW'];
		}elseif(isset($_SERVER['PHP_AUTH_PW'])){
			unset($_SERVER['PHP_AUTH_PW']);
		}

		return $return;



*/

	}

	/**
	 * This function checks to see if it is possible to enable this handler.
	 *
	 * @return bool true
	 */
	static function canEnable()
	{
		// xcache isn't available over CLI
		return extension_loaded('xcache') && !(defined('STDIN') || !isset($_SERVER['REQUEST_METHOD']));
	}
}

class StashXcacheError extends StashError {}
?>