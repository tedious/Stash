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
 * The StashApc is a wrapper for the APC extension, which allows developers to store data in memory.
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class Apc implements \StashHandler
{
	protected $ttl = 300;
	protected $apcNamespace;

	/**
	 * This function should takes an array which is used to pass option values to the handler.
	 *
	 * * ttl - This is the maximum time the item will be stored.
	 * * namespace - This should be used when multiple projects may use the same library.
	 *
	 * @param array $options
	 */
	public function __construct($options = array())
	{
		if(isset($options['ttl']) && is_numeric($options['ttl']))
			$this->ttl = (int) $options['ttl'];

		if(isset($options['namespace']))
		{
			$this->apcNamespace = $options['namespace'];
		}else{
			$this->apcNamespace = md5(__file__);
		}
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
		$keyString = self::makeKey($key);
		if(!$keyString)
			return false;

		$data = apc_fetch($keyString, $success);
		if(!$success)
			return false;

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
		$life = $this->getCacheTime($expiration);
		$keyString = $this->makeKey($key);
		$storage = serialize(array('data' => $data, 'expiration' => $expiration));
		$errors = apc_store(array($keyString => $storage), null, $life);
		return count($errors) === 0;
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
		if(!isset($key))
		{
			return apc_clear_cache('user');
		}else{
			$keyRegex = '[' . $this->makeKey($key) . '*]';
			$chunkSize = isset($this->chunkSize) && is_numeric($this->chunkSize) ? $this->chunkSize : 100;
			$it = new APCIterator('user', $keyRegex, APC_ITER_KEY, $chunkSize);
			foreach($it as $key)
				apc_delete($key);
		}
		return true;
	}

	/**
	 * This function is used to remove expired items from the cache.
	 *
	 * @return bool
	 */
	public function purge()
	{
		$now = time();
		$keyRegex = '[' . $this->makeKey(array()) . '*]';
		$chunkSize = isset($this->chunkSize) && is_numeric($this->chunkSize) ? $this->chunkSize : 100;
		$it = new APCIterator('user', $keyRegex, APC_ITER_KEY, $chunkSize);
		foreach($it as $key)
		{
			$data = apc_fetch($key, $success);
			$data = unserialize($data[$key['key']]);

			if($success && is_array($data) && $data['expiration'] <= $now)
				apc_delete($key);
		}

		return true;
	}

	/**
	 * This function checks to see if it is possible to enable this handler. This returns true no matter what, since
	 * this is the handler of last resort.
	 *
	 * @return bool true
	 */
	static function canEnable()
	{
		return extension_loaded('apc');
	}

	protected function makeKey($key)
	{
		$keyString = md5(__file__) . '::'; // make it unique per install

		if(isset($this->apcNamespace))
			$keyString .= $this->apcNamespace . '::';

		foreach($key as $piece)
			$keyString .= $piece . '::';

		return $keyString;
	}

	protected function getCacheTime($expiration)
	{
		$currentTime = time(true);
		$life = $expiration - $currentTime;

		if(isset($this->ttl) && $this->ttl > $life)
			$life = $this->ttl;

		return $life;
	}
}

?>