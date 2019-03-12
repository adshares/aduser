<?php

declare(strict_types = 1);

namespace Adshares\Share\Url;

use Adshares\Share\Url;
use RuntimeException;

final class NullUrl implements Url
{
    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        throw new RuntimeException('This is a NULL object.');
    }
}
