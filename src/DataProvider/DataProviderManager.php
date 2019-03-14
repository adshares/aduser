<?php
declare(strict_types = 1);

namespace Adshares\Aduser\DataProvider;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;

final class DataProviderManager implements \IteratorAggregate
{
    private $providers = [];

    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }
    }

    public function registerProvider(DataProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->providers);
    }

    public function get(string $name): DataProviderInterface
    {
        if (!array_key_exists($name, $this->providers)) {
            throw new ResourceNotFoundException(sprintf('Provider "%s" is not registered', $name));
        }

        return $this->providers[$name];
    }
}
