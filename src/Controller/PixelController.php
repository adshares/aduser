<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Controller;

use Adshares\Aduser\Data\DataProviderInterface;
use Adshares\Aduser\Data\DataProviderManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function base64_decode;
use function base64_encode;
use function getenv;
use function implode;
use function json_encode;
use function microtime;
use function sprintf;
use function substr;
use function time;

class PixelController extends AbstractController
{
    /** @var DataProviderManager|DataProviderInterface[] */
    private $providers;

    private $connection;

    private $logger;

    public function __construct(DataProviderManager $providers, Connection $connection, LoggerInterface $logger)
    {
        if ($logger === null) {
            $logger = new NullLogger();
        }
        $this->providers = $providers;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function register(Request $request): Response
    {
        $trackingId = $this->loadTrackingId($request);

        $this->logRequest('pixel', $trackingId, $request);

        if ($request->getRequestFormat() === 'gif') {
            $response = $this->syncRegister($trackingId, $request);
        } else {
            $response = $this->asyncRegister($trackingId, $request);
        }

        $response->headers->setCookie(new Cookie(
            getenv('ADUSER_COOKIE_NAME'),
            $trackingId,
            time() + getenv('ADUSER_COOKIE_EXPIRY_PERIOD'),
            '/'
        ));

        return $response;
    }

    private function loadTrackingId(Request $request)
    {
        $cookieTid = $request->cookies->get(getenv('ADUSER_COOKIE_NAME'));
        try {
            $dbTid = $this->connection->fetchColumn(
                'SELECT tracking_id FROM user_map WHERE adserver_id = ? AND adserver_user_id = ?',
                [
                    $request->get('adserver'),
                    $request->get('user'),
                ]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $dbTid = null;
        }

        if (!empty($cookieTid) && self::validTrackingId($cookieTid)) {
            $trackingId = $cookieTid;
        } else {
            if (!empty($dbTid)) {
                $trackingId = $dbTid;
            } else {
                $this->logger->debug('Generating tracking id');
                $trackingId = $this->generateTrackingId($request);
            }
        }

        if ($trackingId !== $dbTid) {
            try {
                if (!empty($dbTid)) {
                    $this->connection->update(
                        'user_map',
                        [
                            'tracking_id' => $trackingId,
                        ],
                        [
                            'adserver_id' => $request->get('adserver'),
                            'adserver_user_id' => $request->get('user'),
                        ]
                    );
                } else {
                    $this->connection->insert(
                        'user_map',
                        [
                            'tracking_id' => $trackingId,
                            'adserver_id' => $request->get('adserver'),
                            'adserver_user_id' => $request->get('user'),
                        ]
                    );
                }
            } catch (DBALException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $this->logger->info(sprintf('Tracking id: %s', $trackingId));

        return $trackingId;
    }

    private static function validTrackingId($trackingId): bool
    {
        $id = base64_decode($trackingId);
        $userId = substr($id, 0, 16);
        $checksum = substr($id, 16, 22);

        return self::trackingIdChecksum($userId) === $checksum;
    }

    private static function trackingIdChecksum(string $userId): string
    {
        return substr(sha1($userId . getenv('ADUSER_TRACKING_SECRET')), 0, 6);
    }

    private function generateTrackingId(Request $request): string
    {
        $elements = [
            microtime(true),
            $request->getClientIp(),
            $request->getPort(),
            $request->server->get('REQUEST_TIME_FLOAT'),
        ];

        try {
            $elements[] = random_bytes(22);
        } catch (Exception $e) {
            $elements[] = microtime(true);
        }

        $userId = substr(sha1(implode(':', $elements)), 0, 16);

        return base64_encode($userId . self::trackingIdChecksum($userId));
    }

    private function logRequest(string $type, string $trackingId, Request $request): void
    {
        $this->logger->debug(sprintf('%s log: %s -> %s', $type, $trackingId, $request));

        try {
            $this->connection->insert("{$type}_log",
                [
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
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function syncRegister(string $trackingId, Request $request): Response
    {
        $redirect = null;
        $response = null;

        foreach ($this->providers as $provider) {
            if ($redirect === null) {
                $r = $provider->getRedirect($trackingId, $request);

                if (!$r->isEmpty()) {
                    $redirect = $r;
                }
            }

            if ($response === null) {
                $r = $provider->register($trackingId, $request);

                if (!$r->isEmpty()) {
                    $response = $r;
                }
            }
        }

        if ($redirect !== null) {
            return $redirect;
        }

        return $response;
    }

    private function asyncRegister(string $trackingId, Request $request): Response
    {
        $images = [];
        $pages = [];

        foreach ($this->providers as $provider) {
            if ($image = (string)$provider->getImageUrl($trackingId, $request)) {
                $images[] = $image;
            }
            if ($page = (string)$provider->getPageUrl($trackingId, $request)) {
                $pages[] = $page;
            }
        }

        return new Response($this->getHtmlPixel($images, $pages));
    }

    private function getHtmlPixel(array $images, array $pages): string
    {
        $content = '<!DOCTYPE html><html lang="en"><body><script type="text/javascript">';
        foreach ($images as $image) {
            $content .= 'parent.postMessage({"adsharesTrack":[{"type": "image", "url": "' . $image . '"}]});';
        }
        foreach ($pages as $page) {
            $content .= 'parent.postMessage({"adsharesTrack":[{"type": "iframe", "url": "' . $page . '"}]});';
        }
        $content .= '</script></body></html>';

        return $content;
    }

    public function provider(Request $request): Response
    {
        if (($trackingId = $request->get('tracking')) === null) {
            $trackingId = $this->generateTrackingId($request);
        }

        $this->logRequest('provider', $trackingId, $request);

        $name = $request->get('provider');

        $provider = $this->providers->get($name);

        return $provider->register($trackingId, $request);
    }
}
