<?php

namespace Adshares\Aduser\Data;


class DataProviderManager implements \IteratorAggregate
{
    private $providers = [];

    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->providers);
    }
}