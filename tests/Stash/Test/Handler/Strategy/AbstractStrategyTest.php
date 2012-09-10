<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Handler\Strategy;

use Stash\Handler\Ephemeral;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
abstract class AbstractStrategyTest extends \PHPUnit_Framework_TestCase
{
    protected $strategy;
    protected $strategyClass;

    public function testConstructor()
    {
        $options = $this->getStrategyOptions();
        $strategyType = $this->strategyClass;
        $strategy = new $strategyType($options);
        $this->assertTrue(is_a($strategy, $strategyType), 'Strategy is an instance of ' . $strategyType);
        $this->assertTrue(is_a($strategy, '\Stash\Handler\Strategy\InvocationStrategyInterface'), 'Strategy implments the Stash\Handler\Strategy\InvocationStrategyInterface');

        return $strategy;
    }

    /**
     * @depends testConstructor
     */
    public function testInvokeStore($strategy)
    {
        $handlers = $this->getHandlers();
        $fixture = array('strategy' => $strategy, 'handlers' => $handlers);

        $this->assertTrue($strategy->invokeStore($handlers, array('strategy', 'test', '1'), 'value', 300));

        return $fixture;
    }

    /**
     * @depends testInvokeStore
     */
    public function testInvokeGet($fixture)
    {
        $result = $fixture['strategy']->invokeGet($fixture['handlers'], array('strategy', 'test', '1'));

        $this->assertEquals($result['data'], 'value');

        return $fixture;
    }

    /**
     * @depends testInvokeGet
     */
    public function testInvokeClear($fixture)
    {
        $this->assertTrue($fixture['strategy']->invokeClear($fixture['handlers'], array('strategy')));
        $this->assertFalse($fixture['strategy']->invokeGet($fixture['handlers'], array('strategy', 'test', '1')));

        return $fixture;
    }

    /**
     * @depends testInvokeClear
     */
    public function testInvokePurge($fixture)
    {
        $this->assertTrue($fixture['strategy']->invokePurge($fixture['handlers']));
    }

    protected function getStrategyOptions()
    {
        return array();
    }

    protected function getHandlers()
    {
        return array(
            'e1' => new Ephemeral(),
            'e2' => new Ephemeral(),
            'e3' => new Ephemeral(),
        );
    }
}
