<?php

namespace Adshares\Aduser\Data;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface DataProviderInterface
{
    public function getRedirectUrl(string $trackingId, Request $request): ?string;

    public function getImageUrl(string $trackingId, Request $request): ?string;

    public function getPageUrl(string $trackingId, Request $request): ?string;

    public function register(Request $request, Connection $connection): Response;
}
