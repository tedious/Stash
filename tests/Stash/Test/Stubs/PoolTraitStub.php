<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Stubs;

use Stash;

/**
 * PoolTraitStub is used to test that PoolTrait can be used on any objects.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 *
 * @codeCoverageIgnore
 */
class PoolTraitStub
{
    use \Stash\PoolTrait;
}
