<?php

namespace Adshares\Aduser\Data;


use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractDataProvider implements DataProviderInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(RouterInterface $router, LoggerInterface $logger)
    {
        $this->router = $router;
        $this->logger = $logger;
    }

    public function getRedirectUrl(string $trackingId, Request $request): ?string
    {
        return null;
    }

    public function getImageUrl(string $trackingId, Request $request): ?string
    {
        return null;
    }

    public function getPageUrl(string $trackingId, Request $request): ?string
    {
        return null;
    }

    public function register(Request $request, Connection $connection): Response
    {
        throw new NotFoundHttpException(sprintf('Provider "%s" does not support registration',
            $request->get('provider')));
    }

    protected static function createImageResponse(?string $data = null)
    {
        if ($data === null) {
            $data = "R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
        }
        $response = new Response(base64_decode($data));
        $response->headers->set('Content-Type', 'image/gif');

        return $response;
    }

    protected static function createHtmlResponse(?string $head = null, ?string $body = null)
    {
        $content = '<!DOCTYPE html><html lang="en">';
        if ($head !== null) {
            $content .= '<head>' . $head . '</head>';
        }
        if ($body !== null) {
            $content .= '<body>' . $body . '</body>';
        }
        $content .= '</html>';

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        return $response;
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @see UrlGeneratorInterface
     * @param string $route
     * @param array $parameters
     * @param int $referenceType
     * @return string
     */
    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->router->generate($route, $parameters, $referenceType);
    }

    /**
     * @param int $length
     * @return string
     */
    protected static function generateNonce($length = 8): string
    {
        try {
            return substr(sha1(random_bytes(256)), 0, $length);
        } catch (\Exception $e) {
            return '';
        }
    }
}