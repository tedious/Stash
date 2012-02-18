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

/**
 * Handlers contains various functions used to organize Handler classes that are available in the system.
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 */
class Handlers
{
	/**
	 * An array of possible cache storage data methods, with the handler class as the array value.
	 *
	 * @var array
	 */
	protected static $handlers = array(	'Apc'		=> '\Stash\Handlers\Apc',
						'FileSystem' 	=> '\Stash\Handlers\FileSystem',
						'Memcached'	=> '\Stash\Handlers\Memcached',
						'MultiHandler'	=> '\Stash\Handlers\MultiHandler',
						'SQLite'	=> '\Stash\Handlers\Sqlite');


	/**
	 * Returns a list of build-in cache handlers that are also supported by this system.
	 *
	 * @return array Handler Name => Class Name
	 */
	static function getHandlers()
	{
		$availableHandlers = array();
		foreach(self::$handlers as $name => $class)
		{
			if(!class_exists($class))
				continue;

			if(!in_array('Stash\Handler', class_implements($class)))
				continue;

			// This code is commented out until I have a chance to see if the $class::canEnable() line will throw a
			// php error with versions less than 5.3. If it does then the block is pointless and we'll just have to
			// break compatibility with code before 5.3 at some point.
			/*
			if(defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 50300)
			{
				if($class::canEnable())
					$availableHandlers[$name] = $class;
			}else */

			if(Utilities::staticFunctionHack($class, 'canEnable')){
				$availableHandlers[$name] = $class;
			}
		}

		return $availableHandlers;
	}

	static function registerHandler($name, $class)
	{
		self::$handlers[$name] = $class;
	}

	static function getHandlerClass($name)
	{
		if(!isset(self::$handlers[$name]))
			return false;

		return self::$handlers[$name];
	}

}

?>