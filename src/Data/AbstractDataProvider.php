<?php

namespace Adshares\Aduser\Data;


use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractDataProvider implements DataProviderInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * AbstractDataProvider constructor.
     * @param RouterInterface $router
     * @param Connection $connection
     * @param LoggerInterface $logger
     */
    public function __construct(RouterInterface $router, Connection $connection, LoggerInterface $logger)
    {
        $this->router = $router;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @see DataProviderInterface
     *
     * @param string $trackingId
     * @param Request $request
     * @return string|null
     */
    public function getRedirectUrl(string $trackingId, Request $request): ?string
    {
        return null;
    }

    /**
     * @see DataProviderInterface
     *
     * @param string $trackingId
     * @param Request $request
     * @return string|null
     */
    public function getImageUrl(string $trackingId, Request $request): ?string
    {
        return null;
    }

    /**
     * @see DataProviderInterface
     *
     * @param string $trackingId
     * @param Request $request
     * @return string|null
     */
    public function getPageUrl(string $trackingId, Request $request): ?string
    {
        return null;
    }

    /**
     * @see DataProviderInterface
     *
     * @param string $trackingId
     * @param Request $request
     * @return Response
     */
    public function register(string $trackingId, Request $request): Response
    {
        return null;
    }

    /**
     * @param string $trackingId
     * @param Request $request
     */
    protected function logRequest(string $trackingId, Request $request)
    {
        $type = $this->getName();
        $this->logger->debug(sprintf('%s log: %s -> %s', $type, $trackingId, $request));
        try {
            $this->connection->insert("{$type}_log", [
                'tracking_id' => $trackingId,
                'uri' => $request->getRequestUri(),
                'attributes' => json_encode($request->attributes->get('_route_params')),
                'query' => json_encode($request->query->all()),
                'headers' => json_encode($request->headers->all()),
                'cookies' => json_encode($request->cookies->all()),
                'ip' => $request->getClientIp(),
                'ips' => json_encode($request->getClientIps()),
                'port' => (int)$request->getPort(),
            ]);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param string|null $data
     * @return Response
     */
    protected static function createImageResponse(?string $data = null)
    {
        if ($data === null) {
            $data = "R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
        }
        $response = new Response(base64_decode($data));
        $response->headers->set('Content-Type', 'image/gif');

        return $response;
    }

    /**
     * @param string|null $body
     * @return Response
     */
    protected static function createHtmlResponse(?string $body = null)
    {
        $content = '<!DOCTYPE html><html lang="en">';
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
     * @see UrlGeneratorInterface
     *
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
        return $this->router->generate(
            $route,
            $parameters,
            $referenceType
        );
    }

    /**
     * @param string $trackingId
     * @param string $format
     * @param array $parameters
     * @return string
     */
    protected function generatePixelUrl(
        string $trackingId,
        $format = 'gif',
        array $parameters = []
    ): string {
        return $this->generateUrl(
            'pixel_provider',
            array_merge([
                'provider' => $this->getName(),
                'tracking' => $trackingId,
                'nonce' => self::generateNonce(),
                '_format' => $format,
            ], $parameters),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param $url
     * @param array $data
     * @return bool|string
     */
    protected static function httpPost($url, array $data = [])
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    /**
     * Generate random nonce string.
     *
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