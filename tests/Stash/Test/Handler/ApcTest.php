<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Handler;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class ApcTest extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handler\Apc';

    public function testConstructor()
    {
        $handlerType = $this->handlerClass;
        $options = $this->getOptions();
        $options['namespace'] = 'namespace_test';
        $options['ttl'] = 15;
        $handler = new $handlerType($options);

        $this->assertAttributeEquals('namespace_test', 'apcNamespace', $handler, 'APC is setting supplied namespace.');
        $this->assertAttributeEquals(15, 'ttl', $handler, 'APC is setting supplied ttl.');

        return parent::testConstructor();
    }


}
