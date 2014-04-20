<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->notName('SessionHandlerInterface*')
    ->in('src/Stash/Session/')
;

return Symfony\CS\Config\Config::create()
    ->finder($finder)
;