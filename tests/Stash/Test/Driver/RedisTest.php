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

use Stash\Driver\Redis;
use Stash\Exception\InvalidArgumentException;
use Stash\Utilities;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class RedisTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Redis';
    protected $redisServer = '127.0.0.1';
    protected $redisPort   = 6379;

    protected $redisNoServer = '127.0.0.1';
    protected $redisNoPort   = 6381;
    protected $persistence   = true;

    /** @var  \Redis */
    protected $redisClient;

    protected function setUp()
    {
        if (!$this->setup) {
            $this->startTime = time();
            $this->expiration = $this->startTime + 3600;

            if (!($sock = @fsockopen($this->redisServer, $this->redisPort, $errno, $errstr, 1))) {
                $this->markTestSkipped('Redis server unavailable for testing.');
            }
            fclose($sock);

            if ($sock = @fsockopen($this->redisNoServer, $this->redisNoPort, $errno, $errstr, 1)) {
                fclose($sock);
                $this->markTestSkipped("No server should be listening on {$this->redisNoServer}:{$this->redisNoPort} " .
                    "so that we can test for exceptions.");
            }

            if (!$this->getFreshDriver()) {
                $this->markTestSkipped('Driver class unsuited for current environment');
            }

            $this->data['object'] = new \stdClass();
            $this->data['large_string'] = str_repeat('apples', ceil(200000 / 6));

            //Connect a redis client so we can do validation outside of the test we are testing
            $this->redisClient = new \Redis();
            $this->redisClient->connect($this->redisServer, $this->redisPort);
            $this->redisClient->flushDB();
        }
    }

    protected function getOptions()
    {
        return [
            'servers' => [
                ['server' => $this->redisServer, 'port' => $this->redisPort, 'ttl' => 0.1]
            ]
        ];
    }

    protected function getNormalizedOptions($normalizeKeys = true)
    {
        $options = $this->getOptions();
        $options['normalize_keys'] = $normalizeKeys;

        return $options;
    }

    protected function getInvalidOptions()
    {
        return [
            'servers' => [
                ['server' => $this->redisNoServer, 'port' => $this->redisNoPort, 'ttl' => 0.1]
            ]
        ];
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testBadDisconnect()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('This test can not run on HHVM as HHVM throws a different set of errors.');
        }

        $driver = $this->getFreshDriver($this->getInvalidOptions());
        $driver->__destruct();
        $driver = null;
    }

    public function testItDeletesUnnormalizedSubkeys()
    {
        $this->deleteSubkeysTest($normalizeKeys = false);
    }

    public function testItDeletedNormalizedSubkeys()
    {
        $this->deleteSubkeysTest($normalizeKeys = true);
    }

    public function testItCannotUseReservedCharactersIfUnnormalized()
    {
        $this->redisClient->flushDB();

        /** @var Redis $redisDriver */
        $redisDriver = $this->getFreshDriver($this->getNormalizedOptions($normalizeKeys = false));

        $expectedException = null;
        try {
            $redisDriver->storeData(['cache', 'namespace', 'illegalkey:'], ['data'], null);
        } catch (InvalidArgumentException $e) {
            $expectedException = $e;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $expectedException);
        $this->assertEquals('You cannot use `:` or `_` in keys if key_normalization is off.',
            $expectedException->getMessage());

        $expectedException = null;
        try {
            $redisDriver->storeData(['cache', 'namespace', 'illegalkey_'], ['data'], null);
        } catch (InvalidArgumentException $e) {
            $expectedException = $e;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $expectedException);
        $this->assertEquals('You cannot use `:` or `_` in keys if key_normalization is off.',
            $expectedException->getMessage());
    }

    public function testItIncreasedTheIndexAfterStackParentDeletion()
    {
        $this->redisClient->flushDB();

        /** @var Redis $redisDriver */
        $redisDriver = $this->getFreshDriver($this->getNormalizedOptions($normalizeKeys = false));

        $keyBase = ['cache', 'namespace', 'test', 'directory'];
        $pathDbProperty = (new \ReflectionClass($redisDriver))->getProperty('pathPrefix');
        $pathDbProperty->setAccessible(true);
        $pathDb = $pathDbProperty->getValue($redisDriver);

        $testKey = $keyBase;
        $testKey[] = 'key1';
        $redisDriver->storeData($testKey, ['testData'], null);
        $this->assertNotFalse($this->redisClient->get('cache:namespace:test:directory:key1'));

        $redisDriver->clear($keyBase);
        $this->assertFalse($this->redisClient->get('cache:namespace:test:directory:key1'));
        $this->assertEquals(1, $this->redisClient->get($pathDb . 'cache:namespace:test:directory'));

        $redisDriver->storeData($testKey, ['testData'], null);
        $this->assertNotFalse($this->redisClient->get('cache:namespace:test:directory_1:key1'));

        $redisDriver->clear($keyBase);
        $this->assertFalse($this->redisClient->get('cache:namespace:test:directory_1:key1'));
        $this->assertEquals(2, $this->redisClient->get($pathDb . 'cache:namespace:test:directory'));
    }

    public function testItDoesNotIncreaseAnIndexAfterLeafDeletion()
    {
        $this->redisClient->flushDB();

        /** @var Redis $redisDriver */
        $redisDriver = $this->getFreshDriver($this->getNormalizedOptions($normalizeKeys = false));

        $keyBase = ['cache', 'namespace', 'test', 'directory'];
        $pathDbProperty = (new \ReflectionClass($redisDriver))->getProperty('pathPrefix');
        $pathDbProperty->setAccessible(true);
        $pathDb = $pathDbProperty->getValue($redisDriver);

        $redisDriver->storeData($keyBase, ['testData'], null);
        $this->assertNotFalse($this->redisClient->get('cache:namespace:test:directory'));

        $redisDriver->clear($keyBase);
        $this->assertFalse($this->redisClient->get($pathDb . 'cache:namespace:test:directory'));
    }

    private function deleteSubkeysTest($normalizeKeys = true)
    {
        $this->redisClient->flushDB();

        /** @var Redis $redisDriver */
        $redisDriver = $this->getFreshDriver($this->getNormalizedOptions($normalizeKeys));

        $keyBase = ['cache', 'namespace', 'test', 'directory'];

        $redisDriver->storeData($keyBase, 'stackparent', null);
        $amountOfTestKeys = 5;
        //Insert initial data in a stacked structure
        for ($i = 0; $i < $amountOfTestKeys; $i++) {
            $key = $keyBase;
            $testKeyIndexed = 'test' . $i;
            $key[] = $testKeyIndexed;

            $redisDriver->storeData($key, 'stackChild', null);

            if ($normalizeKeys) {
                $key = Utilities::normalizeKeys($key);
            }
            $keyCheck = implode(':', $key);

            $this->assertNotFalse($this->redisClient->get($keyCheck));
        }

        //Delete the stackparent
        $redisDriver->clear($keyBase);

        $this->assertFalse($redisDriver->getData($keyBase), 'The stackparent should not exist after deletion');

        //Insert the second batch of data that should now have a new index
        for ($i = 0; $i < $amountOfTestKeys; $i++) {
            $key = $keyBase;
            $testKeyIndexed = 'test' . $i;
            $key[] = $testKeyIndexed;

            $redisDriver->storeData($key, 'testdata', null);

            $keyCheckOldIndex = $keyCheckNewIndex = $key;

            if ($normalizeKeys) {
                $keyCheckOldIndex = Utilities::normalizeKeys($key);
                $keyCheckNewIndex = Utilities::normalizeKeys($key);
            }

            $keyCheckStringOldIndex = implode(':', $keyCheckOldIndex);

            $keyCheckNewIndex[count($key) - 2] .= '_1';
            $keyCheckStringNewIndex = implode(':', $keyCheckNewIndex);

            $this->assertFalse($this->redisClient->get($keyCheckStringOldIndex), 'initial keys should be gone');
            $this->assertNotFalse($this->redisClient->get($keyCheckStringNewIndex),
                'second batch of keys should exist with index');
        }
    }
}
