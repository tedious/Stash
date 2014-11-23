<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->notName('SessionHandlerInterface*')
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
    ->finder($finder)
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)

;