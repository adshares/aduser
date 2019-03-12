<?php

declare(strict_types = 1);

namespace Adshares\Share;

interface Url
{
    public function __toString(): string;

    public function toString(): string;
}
