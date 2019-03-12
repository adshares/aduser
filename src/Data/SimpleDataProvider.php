<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Data;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SimpleDataProvider extends AbstractDataProvider
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'sim';
    }

    /**
     * @param string $trackingId
     * @param Request $request
     * @return string|null
     */
    public function getImageUrl(string $trackingId, Request $request): ?string
    {
        return $this->generatePixelUrl($trackingId);
    }

    /**
     * @param string $trackingId
     * @param Request $request
     * @return Response
     */
    public function register(string $trackingId, Request $request): Response
    {
        // log request
        $this->logRequest($trackingId, $request);

        // render
        return $this->createImageResponse();
    }
}
