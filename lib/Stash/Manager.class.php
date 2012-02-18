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
 * @author     Robert Hafner <tedivm@tedivm.com>
 * @copyright  2009-2011 Robert Hafner <tedivm@tedivm.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://code.google.com/p/stash/
 * @since      File available since Release 0.9.1
 * @version    Release: 0.9.3
 */

namespace Stash;

/**
 * StashManager is a collection of static functions used to make certain repetitive tasks easier by consilidating their
 * steps. Unlike the StashBox class StashManager can work with multiple distinct cache pools.
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class Manager
{
	static protected $handlers = array();


	/**
	 * Takes the same arguments as the Stash->setupKey() function and returns with a new Stash object. If a handler
	 * has been set for this class then it is used, otherwise the Stash object will be set to use script memory only.
	 * Any Stash object set for this class uses a custom namespace.
	 * The first argument must be the name of the specific cache being used, which should correspond to the name of a
	 * handler passed in through the setHandler function- if using a one cache system please check out StashBox instead.
	 *
	 * @example $cache = new StashBox::getCache('Primary Cache', 'permissions', 'user', '4', '2');
	 *
	 * @param string $name
	 * @param string|array $key, $key, $key...
	 * @return Stash
	 */
	static function getCache()
	{
		$args = func_get_args();

		// check to see if a single array was used instead of multiple arguments
		if(count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		if(count($args) < 1)
			throw new StashManagerError('getCache function requires a cache name.');

		$name = array_shift($args);
		$group = '__StashManager_' . $name;

		// Check to see if keys were passed as an extended argument or a single array
		if(count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		if(isset(self::$handlers[$name]) && self::$handlers[$name] != false)
		{
			$group .= '_' . get_class(self::$handlers[$name]) . '__';
			$stash = new Cache(self::$handlers[$name], $group);
		}else{
			$group .= '_memory__';
			$stash = new Cache(null, $group);
		}

		if(count($args) > 0)
			$stash->setupKey($args);

		return $stash;
	}


	/**
	 * Works like the Stash->clear() function, except it can be called as a single function which will build the
	 * Stash object internally, load the handler, and clear the portion of the cache pool specified all in one call.
	 * The first argument must be the name of the specific cache being used, which should correspond to the name of a
	 * handler passed in through the setHandler function- if using a one cache system please check out StashBox instead.
	 *
	 * @param string $name The name of the stored cache item.
 	 * @param null|string|array $key, $key, $key...
 	 * @return bool success
	 */
	static function clearCache()
	{
		$stash = self::getCache(func_get_args());
		return $stash->clear();
	}

	/**
	 * Works like the Stash->purge() function, except it can be called as a single function which will build the
	 * Stash object internally, load the handler, and run the purge function all in one call.
	 * The first argument must be the name of the specific cache being used, which should correspond to the name of a
	 * handler passed in through the setHandler function- if using a one cache system please check out StashBox instead.
	 *
	 * @param string $name Specific cache to purge
 	 * @return bool success
	 */
	static function purgeCache($name)
	{
		$stash = self::getCache($name);
		return $stash->purge();
	}

	/**
	 * Returns a list of all available handlers that are registered with the system.
	 *
	 * @return array ShortName => Class
	 */
	static function getCacheHandlers()
	{
		return Handlers::getHandlers();
	}

	/**
	 * Sets a handler for each Stash object created by this class. This allows the handlers to be created just once
	 * and reused, making it much easier to incorporate caching into any code. The name used for this handler should
	 * match the one used by the other cache items in order to reuse this handler.
	 *
	 * @param string $name The label for the handler being passed
	 * @param StashHandler $handler
	 */
	static function setHandler($name, Handler $handler)
	{
		if(!isset($handler))
			$handler = false;
		self::$handlers[$name] = $handler;
	}

}

class StashManagerError extends Error {}
?>