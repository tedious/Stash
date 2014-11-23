<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test;

use Stash\Pool;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class PoolNamespaceTest extends AbstractPoolTest
{
    protected function getTestPool($skipNametest = false)
    {
        $pool = parent::getTestPool();

        if (!$skipNametest) {
            $pool->setNamespace('TestSpace');
        }

        return $pool;
    }

    public function testFlushNamespacedCache()
    {
        $pool = $this->getTestPool(true);

        // No Namespace
        $item = $pool->getItem(array('base', 'one'));
        $item->set($this->data);

        // TestNamespace
        $pool->setNamespace('TestNamespace');
        $item = $pool->getItem(array('test', 'one'));
        $item->set($this->data);

        // TestNamespace2
        $pool->setNamespace('TestNamespace2');
        $item = $pool->getItem(array('test', 'one'));
        $item->set($this->data);

        // Flush TestNamespace
        $pool->setNamespace('TestNamespace');
        $this->assertTrue($pool->flush(), 'Flush succeeds with namespace selected.');

        // Return to No Namespace
        $pool->setNamespace();
        $item = $pool->getItem(array('base', 'one'));
        $this->assertFalse($item->isMiss(), 'Base item exists after other namespace was flushed.');
        $this->assertEquals($this->data, $item->get(), 'Base item returns data after other namespace was flushed.');

        // Flush All
        $this->assertTrue($pool->flush(), 'Flush succeeds with no namespace.');

        // Return to TestNamespace2
        $pool->setNamespace('TestNamespace2');
        $item = $pool->getItem(array('base', 'one'));
        $this->assertTrue($item->isMiss(), 'Namespaced item disappears after complete flush.');
    }

    public function testNamespacing()
    {
        $pool = $this->getTestPool(true);

        $this->assertAttributeEquals(null, 'namespace', $pool, 'Namespace starts empty.');
        $this->assertTrue($pool->setNamespace('TestSpace'), 'setNamespace returns true.');
        $this->assertAttributeEquals('TestSpace', 'namespace', $pool, 'setNamespace sets the namespace.');
        $this->assertEquals('TestSpace', $pool->getNamespace(), 'getNamespace returns current namespace.');

        $this->assertTrue($pool->setNamespace(), 'setNamespace returns true when setting null.');
        $this->assertAttributeEquals(null, 'namespace', $pool, 'setNamespace() empties namespace.');
        $this->assertFalse($pool->getNamespace(), 'getNamespace returns false when no namespace is set.');
    }
}
