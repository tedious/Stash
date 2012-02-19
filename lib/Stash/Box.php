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
 * @version    Release: 0.9.5
 */

namespace Stash;

use Stash\Handlers\HandlerInterface;

/**
 * StashBox makes managing a simply cache system easier by encapsulating certain commonly used tasks. StashBox also
 * makes it easier to reuse a handler object for each Stash instance. The downside to StashBox is that it only works
 * with one handler at a time, so systems with multiple cache pools will want to use the StashManager class instead.
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class Box
{
	static protected $handler;


	/**
	 * Takes the same arguments as the Stash->setupKey() function and returns with a new Stash object. If a handler
	 * has been set for this class then it is used, otherwise the Stash object will be set to use script memory only.
	 * Any Stash object set for this class uses the 'stashbox' namespace.
	 *
	 * @example $cache = new StashBox::getCache('permissions', 'user', '4', '2');
	 *
	 * @param string|array $key, $key, $key...
	 * @return Stash
	 */
	static function getCache()
	{
		$args = func_get_args();

		// check to see if a single array was used instead of multiple arguments
		if(count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$handler = (isset(self::$handler)) ? self::$handler : null;
		$stash = new Cache($handler, 'stashbox');

		if(count($args) > 0)
			$stash->setupKey($args);

		return $stash;
	}

	/**
	 * Works exactly like the Stash->clear() function, except it can be called as a single function which will build the
	 * Stash object internally, load the handler, and clear the portion of the cache pool specified all in one call.
	 *
 	 * @param null|string|array $key, $key, $key...
 	 * @return bool success
	 */
	static function clearCache()
	{
		$stash = self::getCache(func_get_args());
		return $stash->clear();
	}

	/**
	 * Works exactly like the Stash->purge() function, except it can be called as a single function which will build the
	 * Stash object internally, load the handler, and run the purge function all in one call.
	 *
 	 * @return bool success
	 */
	static function purgeCache()
	{
		$stash = self::getCache();
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
	 * and reused, making it much easier to incorporate caching into any code.
	 *
	 * @param HandlerInterface $handler
	 */
	static function setHandler(HandlerInterface $handler)
	{
		self::$handler = $handler;
	}
}

?>