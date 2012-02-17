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
 * StashMultieHandler is a wrapper around one or more StashHandlers, allowing faster caching engines with size or
 * persistance limitations to be backed up by slower but larger and more persistant caches. There are no artificial
 * limits placed on how many handlers can be staggered.
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class StashMultiHandler implements StashHandler
{

	protected $handlers = array();

	/**
	 * This function should takes an array which is used to pass option values to the handler.
	 *
	 * @param array $options
	 */
	public function __construct($options = array())
	{
		if(!isset($options['handlers']) || !is_array($options['handlers']) || count($options['handlers']) < 1)
			throw new StashMultiHandlerError('This handler requires secondary handlers to run.');

		foreach($options['handlers'] as $handler)
		{
			if(!(is_object($handler) && $handler instanceof StashHandler))
				throw new StashMultiHandlerError('Handler objects are expected to implement StashHandler');

			if(!StashUtilities::staticFunctionHack($handler, 'canEnable'))
				continue;

			$this->handlers[] = $handler;
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
	 * is not present. This array should have a value for "data" and for "expiration", which should be the data the
	 * main script is trying to store.
	 *
	 * @return array
	 */
	public function getData($key)
	{
		$failedHandlers = array();
		foreach($this->handlers as $handler)
		{
			if($return = $handler->getData($key))
			{
				$failedHandlers = array_reverse($failedHandlers);
				foreach($failedHandlers as $failedHandler)
					$failedHandler->storeData($key, $return['data'], $return['expiration']);

				break;
			}else{
				$failedHandlers[] = $handler;
			}
		}

		return $return;
	}

	/**
	 * This function takes an array as its first argument and the expiration time as the second. This array contains two
	 * items, "expiration" describing when the data expires and "data", which is the item that needs to be
	 * stored. This function needs to store that data in such a way that it can be retrieved exactly as it was sent. The
	 * expiration time needs to be stored with this data.
	 *
	 * @param array $data
	 * @param int $expiration
	 * @return bool
	 */
	public function storeData($key, $data, $expiration)
	{
		$handlers = array_reverse($this->handlers);
		$return = true;
		foreach($handlers as $handler)
		{
			$storeResults = $handler->storeData($key, $data, $expiration);
			$return = ($return) ? $storeResults : false;
		}

		return $return;
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
		$handlers = array_reverse($this->handlers);
		$return = true;
		foreach($handlers as $handler)
		{
			$clearResults = $handler->clear($key);
			$return = ($return) ? $clearResults : false;
		}

		return $return;
	}

	/**
	 * This function is used to remove expired items from the cache.
	 *
	 * @return bool
	 */
	public function purge()
	{
		$handlers = array_reverse($this->handlers);
		$return = true;
		foreach($handlers as $handler)
		{
			$purgeResults = $handler->purge();
			$return = ($return) ? $purgeResults : false;
		}

		return $return;
	}

	/**
	 * This function checks to see if it is possible to enable this handler. This always returns true because this
	 * handler has no dependencies, beign a wrapper around other classes.
	 *
	 * @return bool true
	 */
	static function canEnable()
	{
		return true;
	}
}

class StashMultiHandlerError extends StashError {}
?>