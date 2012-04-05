<?php

namespace Stash\Handler\Strategy;

interface InvocationStrategyInterface
{
    public function __construct(array $options = array());

    public function invokeStore($handlers, $key, $data, $expiration);

    public function invokeGet($handlers, $key);

    public function invokeClear($handlers, $key = null);

    public function invokePurge($handlers);
}