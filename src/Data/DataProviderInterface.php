<?php

namespace Adshares\Aduser\Data;

use Symfony\Component\HttpFoundation\Request;

interface DataProviderInterface
{
    public function getRedirectUrl(string $trackingId, Request $request): ?string;

    public function getImageUrl(string $trackingId, Request $request): ?string;

    public function getPageUrl(string $trackingId, Request $request): ?string;
}
