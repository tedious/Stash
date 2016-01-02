<?php

namespace Stash\Test;

use Cache\IntegrationTests\CachePoolTest;
use Stash\Pool;

class IntegrationTest extends CachePoolTest
{
    public function createCachePool()
    {
        return new Pool();
    }
}
