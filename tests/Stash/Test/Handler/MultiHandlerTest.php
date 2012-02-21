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

use Stash\Handler\Ephemeral;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class StashMultiHandlerTest extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handler\MultiHandler';
    protected $subHandlers;

    protected function getOptions()
    {
        $options = array();
        $options['handlers'][] = new Ephemeral(array());
        $options['handlers'][] = new Ephemeral(array());
        $options['handlers'][] = new Ephemeral(array());
        $this->subHandlers = $options['handlers'];
        return $options;
    }

    public function testStaggeredStore()
    {
        $handler = $this->getFreshHandler();
        $a = $this->subHandlers[0];
        $b = $this->subHandlers[1];
        $c = $this->subHandlers[2];

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $this->assertTrue($handler->storeData($key, $value, $this->expiration), 'Handler class able to store data type ' . $type);
        }

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $return = $c->getData($key);

            $this->assertTrue(is_array($return), 'getData ' . $type . ' returns array');

            $this->assertArrayHasKey('expiration', $return, 'getData ' . $type . ' has expiration');
            $this->assertLessThanOrEqual($this->expiration, $return['expiration'], 'getData ' . $type . ' returns same expiration that is equal to or sooner than the one passed.');

            $this->assertGreaterThan($this->startTime, $return['expiration'], 'getData ' . $type . ' returns expiration that after it\'s storage time');

            $this->assertArrayHasKey('data', $return, 'getData ' . $type . ' has data');
            $this->assertEquals($value, $return['data'], 'getData ' . $type . ' returns same item as stored');
        }
    }

    public function testStaggeredGet()
    {
        $handler = $this->getFreshHandler();
        $a = $this->subHandlers[0];
        $b = $this->subHandlers[1];
        $c = $this->subHandlers[2];

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $this->assertTrue($c->storeData($key, $value, $this->expiration), 'Handler class able to store data type ' . $type);
        }

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $return = $handler->getData($key);


            $this->assertTrue(is_array($return), 'getData ' . $type . ' returns array');


            $this->assertArrayHasKey('expiration', $return, 'getData ' . $type . ' has expiration');
            $this->assertLessThanOrEqual($this->expiration, $return['expiration'], 'getData ' . $type . ' returns same expiration that is equal to or sooner than the one passed.');

            $this->assertGreaterThan($this->startTime, $return['expiration'], 'getData ' . $type . ' returns expiration that after it\'s storage time');

            $this->assertArrayHasKey('data', $return, 'getData ' . $type . ' has data');
            $this->assertEquals($value, $return['data'], 'getData ' . $type . ' returns same item as stored');
        }
    }

}
