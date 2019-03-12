<?php

namespace Adshares\Aduser\Data;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface DataProviderInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $trackingId
     * @param Request $request
     * @return string|null
     */
    public function getRedirectUrl(string $trackingId, Request $request): ?string;

    /**
     * @param string $trackingId
     * @param Request $request
     * @return string|null
     */
    public function getImageUrl(string $trackingId, Request $request): ?string;

    /**
     * @param string $trackingId
     * @param Request $request
     * @return string|null
     */
    public function getPageUrl(string $trackingId, Request $request): ?string;

    /**
     * @param string $trackingId
     * @param Request $request
     * @return Response
     */
    public function register(string $trackingId, Request $request): Response;
}
