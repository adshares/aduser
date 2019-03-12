<?php
declare(strict_types = 1);

namespace Adshares\Share\Response;

use Symfony\Component\HttpFoundation\Response;

final class NoResponse extends Response
{
    public function __toString()
    {
        return '';
    }
}
