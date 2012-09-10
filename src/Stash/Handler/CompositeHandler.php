<?php

namespace Stash\Handler;

use Stash;
use Stash\Exception\InvalidArgumentException;
use Stash\Handler\Strategy\InvocationStrategyInterface;

/**
 * The composite handler is used to create more complex caching logic that utilizes multiple backends. The user
 * provides an array of handlers as well as an invocation strategy that actually decides how to make use of each
 * handler when a request is made.
 */
class CompositeHandler implements HandlerInterface
{
    protected $strategy;
    protected $handlers;

    /**
     * Takes an option array containing
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if(!isset($options['strategy'])) {
            throw new InvalidArgumentException('No invocation strategy provided.');
        }

        if(!$options['strategy'] instanceof InvocationStrategyInterface) {
            throw new InvalidArgumentException('Strategy does not implement InvocationStrategyInterface.');
        }

        $this->strategy = $options['strategy'];

        if(!isset($options['handlers'])) {
            throw new InvalidArgumentException('No handlers provided.');
        }

        foreach($options['handlers'] as $name => $handler) {
            if(!$handler instanceof HandlerInterface) {
                throw new InvalidArgumentException('Handler ' . $name . ' does not implement HandlerInterface.');
            }
        }

        $this->handlers = $options['handlers'];
    }

    /**
     * Passes the get command along to the strategy.
     *
     * @param array $key
     * @return array
     */
    public function getData($key)
    {
        return $this->strategy->invokeGet($this->handlers, $key);
    }

    /**
     * Passes the store command along to the strategy.
     *
     * @param array $key
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        return $this->strategy->invokeStore($this->handlers, $key, $data, $expiration);
    }

    /**
     * Passes the clear command along to the strategy.
     *
     * @param null|array $key
     * @return bool
     */
    public function clear($key = null)
    {
        return $this->strategy->invokeClear($this->handlers, $key);
    }

    /**
     * Passes the purge command along to the strategy.
     *
     * @return bool
     */
    public function purge()
    {
        return $this->strategy->invokePurge($this->handlers);
    }

    /**
     * Returns true -- the composite handler is always available.
     *
     * @return true
     */
    static public function isAvailable()
    {
        return true;
    }

    public function __destruct()
    {

    }
}