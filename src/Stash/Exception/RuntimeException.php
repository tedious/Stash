<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Exception;

/**
 * Exception thrown if an error which can only be found on runtime occurs.
 *
 * Class RuntimeException
 * @package Stash\Exception
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class RuntimeException extends \RuntimeException implements Exception
{
}
