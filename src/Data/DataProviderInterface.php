<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Data;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface DataProviderInterface
{
    public function getName(): string;

    public function getRedirectUrl(string $trackingId, Request $request): ?string;

    public function getImageUrl(string $trackingId, Request $request): ?string;

    public function getPageUrl(string $trackingId, Request $request): ?string;

    public function register(string $trackingId, Request $request): Response;
}
