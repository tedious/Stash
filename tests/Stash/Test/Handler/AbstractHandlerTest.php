<?php

namespace Stash\Test\Handler;

use Stash\Utilities;

abstract class AbstractHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $data = array('string' => 'Hello world!',
                            'complexString' => "\t\tHello\r\n\r\'\'World!\"\'\\",
                            'int' => 4234,
                            'negint' => -6534,
                            'bigint' => 58635272821786587286382824657568871098287278276543219876543,
                            'float' => 1.8358023545,
                            'negfloat' => -5.7003249023,
                            'false' => false,
                            'true' => true,
                            'null' => null,
                            'array' => array(3, 5, 7),
                            'hashmap' => array('one' => 1, 'two' => 2),
                            'multidemensional array' => array(array(5345),
                                                              array(3, 'hello', false, array('one' => 1, 'two' => 2
                                                              )
                                                              )
                            ),
                            '@node' => 'stuff'
    );

    protected $expiration;
    protected $handlerClass;
    protected $startTime;
    private $setup = false;

    protected function setUp()
    {
        if (!$this->setup) {
            $this->startTime = time();
            $this->expiration = $this->startTime + 3600;
            $handlerClass = $this->handlerClass;

            if (!$handlerClass::canEnable()) {
                $this->markTestSkipped('Handler class unsuited for current environment');
            }

            $this->data['object'] = new \stdClass();
            $this->data['large_string'] = str_repeat('apples', ceil(200000 / 6));
        }
    }

    protected function getFreshHandler()
    {
        $handlerClass = $this->handlerClass;

        if (!$handlerClass::canEnable()) {
            return false;
        }

        $handlerClass = $this->handlerClass;
        $options = $this->getOptions();
        $handler = new $handlerClass($options);
        return $handler;
    }

    public function testConstructor()
    {
        $handlerType = $this->handlerClass;
        $options = $this->getOptions();
        $handler = new $handlerType($options);
        $this->assertTrue(is_a($handler, $handlerType), 'Handler is an instance of ' . $handlerType);
        $this->assertTrue(is_a($handler, '\Stash\Handler\HandlerInterface'), 'Handler implments the Stash\Handler\HandlerInterface interface');

        return $handler;
    }

    protected function getOptions()
    {
        return array();
    }

    /**
     * @depends testConstructor
     */
    public function testStoreData($handler)
    {
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $this->assertTrue($handler->storeData($key, $value, $this->expiration), 'Handler class able to store data type ' . $type);
        }
        return $handler;
    }

    /**
     * @depends testStoreData
     */
    public function testGetData($handler)
    {
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
        return $handler;
    }

    /**
     * @depends testGetData
     */
    public function testClear($handler)
    {
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $keyString = implode('::', $key);

            $return = $handler->getData($key);
            $this->assertArrayHasKey('data', $return, 'Repopulating ' . $type . ' stores data');
            $this->assertEquals($value, $return['data'], 'Repopulating ' . $type . ' returns same item as stored');

            $this->assertTrue($handler->clear($key), 'clear of ' . $keyString . ' returned true');
            $this->assertFalse($handler->getData($key), 'clear of ' . $keyString . ' removed data');
        }

        // repopulate
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $keyString = implode('::', $key);

            $handler->storeData($key, $value, $this->expiration);

            $return = $handler->getData($key);
            $this->assertArrayHasKey('data', $return, 'Repopulating ' . $type . ' stores data');
            $this->assertEquals($value, $return['data'], 'Repopulating ' . $type . ' returns same item as stored');
        }

        $this->assertTrue($handler->clear(array('base')), 'clear of base node returned true');

        foreach ($this->data as $type => $value) {
            $this->assertFalse($handler->getData(array('base', $type)), 'clear of base node removed data');
        }


        // repopulate
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $keyString = implode('::', $key);

            $handler->storeData($key, $value, $this->expiration);

            $return = $handler->getData($key);
            $this->assertArrayHasKey('data', $return, 'Repopulating ' . $type . ' stores data');
            $this->assertEquals($value, $return['data'], 'Repopulating ' . $type . ' returns same item as stored');
        }

        $this->assertTrue($handler->clear(), 'clear of root node returned true');

        foreach ($this->data as $type => $value) {
            $this->assertFalse($handler->getData(array('base', $type)), 'clear of root node removed data');
        }

        return $handler;
    }

    /**
     * @depends testClear
     */
    public function testPurge($handler)
    {
        // We're going to populate this with both stale and fresh data, but we're only checking that the stale data
        // is removed. This is to give handlers the flexibility to introduce their own removal algorithms- our only
        // restriction is that they can't keep things for longer than the developers tell them to, but it's okay to
        // remove things early.

        foreach ($this->data as $type => $value) {
            $handler->storeData(array('base', 'fresh', $type), $value, $this->expiration);
        }

        foreach ($this->data as $type => $value) {
            $handler->storeData(array('base', 'stale', $type), $value, $this->startTime - 600);
        }

        $this->assertTrue($handler->purge());

        foreach ($this->data as $type => $value) {
            $this->assertFalse($handler->getData(array('base', 'stale', $type)), 'purge removed stale data');
        }
    }

    /**
     * @depends testStoreData
     */
    public function testDestructor($handler)
    {
        $handler->__destruct();
        unset($handler);
        Utilities::deleteRecursive(Utilities::getBaseDirectory());
    }
}
