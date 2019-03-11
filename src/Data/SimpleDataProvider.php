<?php

namespace Adshares\Aduser\Data;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SimpleDataProvider extends AbstractDataProvider
{
    public const NAME = 'sim';

    /**
     * @param string $trackingId
     * @param Request $request
     * @return string|null
     */
    public function getImageUrl(string $trackingId, Request $request): ?string
    {
        return $this->generateUrl(
            'pixel_provider',
            [
                'provider' => self::NAME,
                'tracking' => $trackingId,
                'nonce' => self::generateNonce(),
                '_format' => 'gif',
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}