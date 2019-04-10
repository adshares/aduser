<?php
declare(strict_types = 1);

namespace Adshares\Aduser\DataProvider;

use Adshares\Share\Url;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractDataProvider implements DataProviderInterface
{
    /** @var RouterInterface */
    protected $router;

    /** @var Connection */
    protected $connection;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(RouterInterface $router, Connection $connection, LoggerInterface $logger)
    {
        $this->router = $router;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public static function createImageResponse(?string $data = null): Response
    {
        if ($data === null) {
            $data = 'R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
        }

        $response = new Response(base64_decode($data));
        $response->headers->set('Content-Type', 'image/gif');

        return $response;
    }

    public static function createHtmlResponse(?string $body = null): Response
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

    protected static function httpPost($url, array $data = []): string
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            throw new RuntimeException("POST request to $url failed");
        }

        return $response;
    }

    public function getRedirect(string $trackingId, Request $request): ?RedirectResponse
    {
        return null;
    }

    public function getImageUrl(string $trackingId, Request $request): Url
    {
        return new Url\EmptyUrl();
    }

    public function getPageUrl(string $trackingId, Request $request): Url
    {
        return new Url\EmptyUrl();
    }

    public function register(string $trackingId, Request $request): ?Response
    {
        return null;
    }

    public function updateData(): bool
    {
        return true;
    }

    public function getTaxonomy(): array
    {
        return [];
    }

    public function getHumanScore(string $trackingId, Request $request): float
    {
        return -1.0;
    }

    public function getKeywords(string $trackingId, Request $request): array
    {
        return [];
    }

    protected function getRequestLog($trackingId): array
    {
        $log = [
            'attributes' => new ParameterBag(),
            'query' => new ParameterBag(),
            'request' => new ParameterBag(),
            'headers' => new HeaderBag(),
            'cookies' => new ParameterBag(),
            'ips' => [],
        ];

        try {
            $pixel = $this->connection->fetchAssoc(
                'SELECT * FROM pixel_log WHERE tracking_id = ? ORDER BY date DESC',
                [$trackingId]
            );
            if ($pixel !== false) {
                $log['attributes'] = new ParameterBag(json_decode($pixel['attributes'], true));
                $log['query'] = new ParameterBag(json_decode($pixel['query'], true));
                $log['request'] = new ParameterBag(json_decode($pixel['request'], true));
                $log['headers'] = new HeaderBag(json_decode($pixel['headers'], true));
                $log['cookies'] = new ParameterBag(json_decode($pixel['cookies'], true));
                $log['ips'] = json_decode($pixel['ips'], true);
            }
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }

        return $log;
    }

    protected function generatePixelUrl(
        string $trackingId,
        $format = 'gif',
        array $parameters = []
    ): Url {
        return $this->generateUrl(
            'pixel_provider',
            array_merge(
                [
                'provider' => $this->getName(),
                'tracking' => $trackingId,
                'nonce' => self::generateNonce(),
                '_format' => $format,
                ],
                $parameters
            ),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): Url {
        return new Url\SimpleUrl($this->router->generate(
            $route,
            $parameters,
            $referenceType
        ));
    }

    public static function generateNonce($length = 8): string
    {
        try {
            return substr(sha1(random_bytes(256)), 0, $length);
        } catch (Exception $e) {
            return '';
        }
    }
}
