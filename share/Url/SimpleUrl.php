<?php

declare(strict_types = 1);

namespace Adshares\Share\Url;

use Adshares\Share\Url;

final class SimpleUrl implements Url
{
    /** @var string */
    private $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->url;
    }
}
