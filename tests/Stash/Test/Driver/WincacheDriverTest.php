<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Driver;

/**
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class WincacheDriverTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Wincache';

    public function testSetOptions()
    {
        $driverType = $this->driverClass;
        $options = $this->getOptions();
        $options['namespace'] = 'namespace_test';
        $options['ttl'] = 15;
        $driver = new $driverType();
        $driver->setOptions($options);

        $this->assertAttributeEquals('namespace_test', 'wincacheNamespace', $driver, 'Wincache is setting supplied namespace.');
        $this->assertAttributeEquals(15, 'ttl', $driver, 'Wincache is setting supplied ttl.');

        return parent::testSetOptions();
    }
}
