<?php

namespace Stash\Test\Handler\Strategy;

use Stash\Handler\Strategy;

class DataSizeStrategyTest extends AbstractStrategyTest
{
    protected $strategyClass = 'Stash\Handler\Strategy\DataSizeStrategy';

    public function getStrategyOptions()
    {
        return array(
            'thresholds' => array(
                'e1' => 5,
                'e2' => 10,
                'e3' => 'all'
            )
        );
    }

    public function testSortedStore()
    {
        $strategy = new $this->strategyClass($this->getStrategyOptions());
        $handlers = $this->getHandlers();

        $strategy->invokeStore($handlers, array('strategy', 'test', 'datasize', '3'), '---', 300);
        $strategy->invokeStore($handlers, array('strategy', 'test', 'datasize', '5'), '----!', 300);
        $strategy->invokeStore($handlers, array('strategy', 'test', 'datasize', '7'), '----!--', 300);
        $strategy->invokeStore($handlers, array('strategy', 'test', 'datasize', '10'), '----!----!', 300);
        $strategy->invokeStore($handlers, array('strategy', 'test', 'datasize', '12'), '----!----!--', 300);

        $this->checkValuesInHandler($handlers['e1'], array(3 => true, 5 => true, 7 => false, 10 => false, 12 => false));
        $this->checkValuesInHandler($handlers['e2'], array(3 => false, 5 => false, 7 => true, 10 => true, 12 => false));
        $this->checkValuesInHandler($handlers['e3'], array(3 => false, 5 => false, 7 => false, 10 => false, 12 => true));

        return array('strategy' => $strategy, 'handlers' => $handlers);
    }

    /**
     * @depends testSortedStore
     */
    public function testResortedStore($fixture)
    {
        $strategy = $fixture['strategy'];
        $handlers = $fixture['handlers'];

        $strategy->invokeStore($handlers, array('strategy', 'test', 'datasize', '3'), '----!---', 300);
        $strategy->invokeStore($handlers, array('strategy', 'test', 'datasize', '5'), '--', 300);
        $strategy->invokeStore($handlers, array('strategy', 'test', 'datasize', '7'), '----!----!---', 300);
        $strategy->invokeStore($handlers, array('strategy', 'test', 'datasize', '10'), '----', 300);
        $strategy->invokeStore($handlers, array('strategy', 'test', 'datasize', '12'), '----!----!--', 300);

        $this->checkValuesInHandler($handlers['e1'], array(3 => false, 5 => true, 7 => false, 10 => true, 12 => false));
        $this->checkValuesInHandler($handlers['e2'], array(3 => true, 5 => false, 7 => false, 10 => false, 12 => false));
        $this->checkValuesInHandler($handlers['e3'], array(3 => false, 5 => false, 7 => true, 10 => false, 12 => true));
    }

    protected function checkValuesInHandler($handler, $values)
    {
        foreach($values as $value => $expected) {
            $result = $handler->getData(array('strategy', 'test', 'datasize', $value));
            if($expected) {
                $this->assertTrue(isset($result['data']));
            } else {
                $this->assertFalse($result);
            }
        }
    }
}