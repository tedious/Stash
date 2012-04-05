<?php

namespace Stash\Handler\Strategy;

class FallbackStrategy implements InvocationStrategyInterface
{
    public function __construct(array $options = array())
    {

    }

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