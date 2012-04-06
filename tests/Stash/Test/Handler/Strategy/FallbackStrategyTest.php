<?php

namespace Stash\Test\Handler\Strategy;

use Stash\Handler\Strategy;

class FallbackStrategyTest extends AbstractStrategyTest
{
    protected $strategyClass = 'Stash\Handler\Strategy\FallbackStrategy';

    public function testLayeredStore()
    {
        $strategy = new $this->strategyClass($this->getStrategyOptions());
        $handlers = $this->getHandlers();

        $strategy->invokeStore($handlers, array('strategy', 'test', 'fallback'), 'valuetwo', 300);

        foreach($handlers as $handler) {
            $result = $handler->getData(array('strategy', 'test', 'fallback'));
            $this->assertEquals($result['data'], 'valuetwo');
        }

        return array('strategy' => $strategy, 'handlers' => $handlers);
    }

    /**
     * @depends testLayeredStore
     */
    public function testRelayeredStore($fixture)
    {
        $fixture['handlers']['e1']->clear();

        $result = $fixture['strategy']->invokeGet($fixture['handlers'], array('strategy', 'test', 'fallback'));
        $this->assertEquals($result['data'], 'valuetwo');

        $fixture['handlers']['e3']->clear();

        $result = $fixture['strategy']->invokeGet($fixture['handlers'], array('strategy', 'test', 'fallback'));
        $this->assertEquals($result['data'], 'valuetwo');

        $fixture['strategy']->invokeStore($fixture['handlers'], array('strategy', 'test', 'fallback'), 'valuethree', 300);

        foreach($fixture['handlers'] as $handler) {
            $result = $handler->getData(array('strategy', 'test', 'fallback'));
            $this->assertEquals($result['data'], 'valuethree');
        }

        return $fixture;
    }

    /**
     * @depends testRelayeredStore
     */
    public function testLayeredClear($fixture)
    {
        $fixture['strategy']->invokeClear($fixture['handlers'], array('strategy'));

        foreach($fixture['handlers'] as $handler) {
            $this->assertFalse($handler->getData(array('strategy', 'test', 'fallback')));
        }
    }
}