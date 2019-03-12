<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Data;

use Adshares\Share\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SimpleDataProvider extends AbstractDataProvider
{
    private const NAME = 'sim';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getImageUrl(string $trackingId, Request $request): Url
    {
        return $this->generatePixelUrl($trackingId);
    }

    public function register(string $trackingId, Request $request): Response
    {
        $this->logRequest($trackingId, $request);

        return self::createImageResponse();
    }
}
