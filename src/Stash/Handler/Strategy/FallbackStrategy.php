<?php

namespace Stash\Handler\Strategy;


/**
 * Implements an invocation strategy where all handlers are arrayed in order.
 * Each request will be stored in every handler, but retrieved from the first
 * handler in order which provides a return value.
 */
class FallbackStrategy implements InvocationStrategyInterface
{
    /**
     * The fallback handler currently accepts no configuration options.
     * 
     * @param array $options
     */
    public function __construct(array $options = array())
    {

    }

    /**
     * Each value is stored in every specified handler; storage is successful
     * as long as at least one handler returns true.
     * 
     * @param array $handlers
     * @param array $key
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function invokeStore($handlers, $key, $data, $expiration)
    {
        $handlers = array_reverse($handlers);
        $return = true;
        foreach ($handlers as $handler) {
            $storeResults = $handler->storeData($key, $data, $expiration);
            $return = $return && $storeResults;
        }

        return $return;
    }


    /**
     * Returns the value found in the first handler to return a value. If
     * any other handlers were tried and failed to return, the final value
     * is stored in each of them.
     *
     * @param array $handlers
     * @param array $key
     * @return array
     */
    public function invokeGet($handlers, $key)
    {
        $failedHandlers = array();
        $return = false;
        foreach ($handlers as $handler) {
            if ($return = $handler->getData($key)) {
                $failedHandlers = array_reverse($failedHandlers);
                foreach ($failedHandlers as $failedHandler) {
                    $failedHandler->storeData($key, $return['data'], $return['expiration']);
                }

                break;
            } else {
                $failedHandlers[] = $handler;
            }
        }

        return $return;
    }

    /**
     * Clears the value from all handlers.
     *
     * @param array $handlers
     * @param null|array $key
     * @return bool
     */
    public function invokeClear($handlers, $key = null)
    {
        $handlers = array_reverse($handlers);
        $return = true;
        foreach ($handlers as $handler) {
            $clearResults = $handler->clear($key);
            $return = $return && $clearResults;
        }

        return $return;
    }


    /**
     * Purges all handlers.
     *
     * @param array $handlers
     * @return bool
     */
    public function invokePurge($handlers)
    {
        $handlers = array_reverse($handlers);
        $return = true;
        foreach ($handlers as $handler) {
            $purgeResults = $handler->purge();
            $return = $return && $purgeResults;
        }

        return $return;
    }
}