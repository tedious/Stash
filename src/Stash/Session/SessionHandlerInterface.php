<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Session;

/**
 * This is a filthy hack to deal with the differences between php5.3 and php5.4.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */

// It's impossible to get complete code coverage because of the different
// php versions involved.

// @codeCoverageIgnoreStart
if (version_compare(phpversion(), '5.4.0', '>=')) {
    include('SessionHandlerInterface_Modern.php');
} else {
    include('SessionHandlerInterface_Legacy.php');
}
// @codeCoverageIgnoreEnd
