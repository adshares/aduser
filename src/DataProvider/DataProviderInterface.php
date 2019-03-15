<?php
declare(strict_types = 1);

namespace Adshares\Aduser\DataProvider;

use Adshares\Share\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface DataProviderInterface
{
    public function getName(): string;

    public function getRedirect(string $trackingId, Request $request): ?RedirectResponse;

    public function getImageUrl(string $trackingId, Request $request): Url;

    public function getPageUrl(string $trackingId, Request $request): Url;

    public function register(string $trackingId, Request $request): ?Response;

    public function updateData(): bool;

    public function getTaxonomy(): array;

    public function getHumanScore(string $trackingId, Request $request): float;

    public function getKeywords(string $trackingId, Request $request): array;
}
