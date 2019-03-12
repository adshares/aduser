<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Data;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SimpleDataProvider extends AbstractDataProvider
{
    public function getName(): string
    {
        return 'sim';
    }

    public function getImageUrl(string $trackingId, Request $request): ?string
    {
        return $this->generatePixelUrl($trackingId);
    }

    public function register(string $trackingId, Request $request): Response
    {
        // log request
        $this->logRequest($trackingId, $request);

        // render
        return $this->createImageResponse();
    }
}
