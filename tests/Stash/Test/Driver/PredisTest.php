<?php

namespace Stash\Test\Driver;

class PredisTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Predis';
    protected $redisServer = '127.0.0.1';
    protected $redisPort = '6379';

    protected $redisNoServer = '127.0.0.1';
    protected $redisNoPort = '6381';

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
        }
    }

    protected function getOptions()
    {
        return [
            'servers' => sprintf('tcp://%s:%s', $this->redisServer, $this->redisPort)
        ];
    }

    public function testStoreDataWithoutExpiration()
    {
        $driver = $this->getFreshDriver($this->getOptions());

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $this->assertTrue($driver->storeData($key, $value, null), 'Driver class able to store data type ' . $type);
        }

        return $driver;
    }
}
