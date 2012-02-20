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
 * StashExceptionTest is used for testing how Stash reacts to thrown errors. Every function but the constructor throws
 * an exception.
 *
 * @package Stash
 * @author Robert Hafner <tedivm@tedivm.com>
 * @codeCoverageIgnore
 */
class ExceptionTest implements \Stash\Handler
{
	protected $store = array();

	public function __construct($options = array())
	{

	}

	public function __destruct()
	{
	}

	public function getData($key)
	{
		throw new StashExceptionTestError('Test exception for ' . __FUNCTION__ . ' call');
	}

	protected function getKeyIndex($key)
	{
		throw new StashExceptionTestError('Test exception for ' . __FUNCTION__ . ' call');
	}

	public function storeData($key, $data, $expiration)
	{
		throw new StashExceptionTestError('Test exception for ' . __FUNCTION__ . ' call');
	}

	public function clear($key = null)
	{
		throw new StashExceptionTestError('Test exception for ' . __FUNCTION__ . ' call');
	}

	public function purge()
	{
		throw new StashExceptionTestError('Test exception for ' . __FUNCTION__ . ' call');
	}

	static function canEnable()
	{
		return (defined('TESTING') && TESTING);
	}
}

class StashExceptionTestError extends \Stash\Error {}
