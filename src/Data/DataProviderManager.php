<?php

namespace Adshares\Aduser\Data;

final class DataProviderManager implements \IteratorAggregate
{
    /**
     * @var array
     */
    private $providers = [];

    /**
     * DataProviderManager constructor.
     * @param array $providers
     */
    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }
    }

    /**
     * @param DataProviderInterface $provider
     */
    public function registerProvider(DataProviderInterface $provider)
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->providers);
    }

    /**
     * @param string $name
     * @return DataProviderInterface
     */
    public function get(string $name): DataProviderInterface
    {
        return array_key_exists($name, $this->providers) ? $this->providers[$name] : null;
    }
}
