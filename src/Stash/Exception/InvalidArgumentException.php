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
 * Exception thrown if an argument does not match with the expected value.
 *
 * Class InvalidArgumentException
 * @package Stash\Exception
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception, \Psr\Cache\InvalidArgumentException
{
}
