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
 * Exception that represents error in the program logic.
 * This kind of exceptions should directly lead to a fix in your code.
 *
 * Class LogicException
 * @package Stash\Exception
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class LogicException extends \LogicException implements Exception
{
}
