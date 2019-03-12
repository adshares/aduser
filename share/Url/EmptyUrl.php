<?php

declare(strict_types = 1);

namespace Adshares\Share\Url;

use Adshares\Share\Url;

final class EmptyUrl implements Url
{
    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return '';
    }
}
