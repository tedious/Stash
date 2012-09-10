<?php

namespace Stash\Handler\Strategy;

/**
 * Invocation strategies define a system by which multiple handlers are
 * individually invoked when using the CompositeHandler. Each one must
 * accept a series of options and provide wrapper methods for the four
 * basic cache operations (store, get, clear, and purge.)
 */
interface InvocationStrategyInterface
{
    public function __construct(array $options = array());

    public function invokeStore($handlers, $key, $data, $expiration);

    public function invokeGet($handlers, $key);

    public function invokeClear($handlers, $key = null);

    public function invokePurge($handlers);
}