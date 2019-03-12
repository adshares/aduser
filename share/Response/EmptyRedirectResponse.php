<?php
declare(strict_types = 1);

namespace Adshares\Share\Response;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class EmptyRedirectResponse extends RedirectResponse
{
    public function __construct()
    {
        parent::__construct(null, Response::HTTP_NO_CONTENT);
    }
}
