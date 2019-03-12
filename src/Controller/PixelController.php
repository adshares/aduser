<?php

namespace Adshares\Aduser\Controller;

use Adshares\Aduser\Data\DataProviderManager;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PixelController extends AbstractController
{
    /**
     * @var DataProviderManager
     */
    private $providers;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(DataProviderManager $providers, Connection $connection, LoggerInterface $logger)
    {
        if ($logger === null) {
            $logger = new \Psr\Log\NullLogger();
        }
        $this->providers = $providers;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function register(Request $request)
    {
        // get tracking id
        $trackingId = $this->loadTrackingId($request);
        // log request
        $this->logRequest('pixel', $trackingId, $request);
        // register
        if ($request->getRequestFormat() === 'gif') {
            $response = $this->syncRegister($trackingId, $request);
        } else {
            $response = $this->asyncRegister($trackingId, $request);
        }
        // cookie
        $response->headers->setCookie(new Cookie(
            getenv('ADUSER_COOKIE_NAME'),
            $trackingId,
            time() + getenv('ADUSER_COOKIE_EXPIRY_PERIOD'),
            '/'
        ));

        // render
        return $response;
    }

    public function provider(Request $request)
    {
        // get tracking id
        if (($trackingId = $request->get('tracking')) === null) {
            $trackingId = $this->generateTrackingId($request);
        }
        // log request
        $this->logRequest('provider', $trackingId, $request);
        // get data provider
        $name = $request->get('provider');
        /* @var $provider \Adshares\Aduser\Data\DataProviderInterface */
        if (($provider = $this->providers->get($name)) === null) {
            throw new NotFoundHttpException(sprintf('Provider "%s" is not registered', $name));
        }

        // register
        return $provider->register($trackingId, $request);
    }

    private function syncRegister(string $trackingId, Request $request): Response
    {
        $redirect = null;
        $response = null;

        /* @var $provider \Adshares\Aduser\Data\DataProviderInterface */
        foreach ($this->providers as $provider) {
            if (($r = $provider->getRedirectUrl($trackingId, $request)) && $redirect === null) {
                $redirect = $r;
            }
            if (($r = $provider->register($trackingId, $request)) && $response === null) {
                $response = $r;
            }
        }

        if ($redirect !== null) {
            $response = new RedirectResponse($redirect);
        }

        return $response;
    }

    private function asyncRegister(string $trackingId, Request $request): Response
    {
        $images = [];
        $pages = [];

        /* @var $provider \Adshares\Aduser\Data\DataProviderInterface */
        foreach ($this->providers as $provider) {
            if ($image = $provider->getImageUrl($trackingId, $request)) {
                $images[] = $image;
            }
            if ($page = $provider->getPageUrl($trackingId, $request)) {
                $pages[] = $page;
            }
        }

        return new Response($this->getHtmlPixel($images, $pages));
    }

    private function logRequest(string $type, string $trackingId, Request $request)
    {
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

    private function loadTrackingId(Request $request)
    {
        $cookieTid = $request->cookies->get(getenv('ADUSER_COOKIE_NAME'));
        try {
            $dbTid = $this->connection->fetchColumn(
                'SELECT tracking_id FROM user_map WHERE adserver_id = ? AND adserver_user_id = ?',
                [
                    $request->get('adserver'),
                    $request->get('user'),
                ]);
        } catch (\Doctrine\DBAL\DBALException $e) {
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
                    $this->connection->update('user_map',
                        [
                            'tracking_id' => $trackingId
                        ],
                        [
                            'adserver_id' => $request->get('adserver'),
                            'adserver_user_id' => $request->get('user'),
                        ]);
                } else {
                    $this->connection->insert('user_map',
                        [
                            'tracking_id' => $trackingId,
                            'adserver_id' => $request->get('adserver'),
                            'adserver_user_id' => $request->get('user'),
                        ]);
                }
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $this->logger->info(sprintf('Tracking id: %s', $trackingId));
        return $trackingId;
    }

    private static function validTrackingId($trackingId)
    {
        $id = base64_decode($trackingId);
        $userId = substr($id, 0, 16);
        $checksum = substr($id, 16, 22);

        return self::trackingIdChecksum($userId) == $checksum;
    }

    private static function trackingIdChecksum($userId)
    {
        return substr(sha1($userId . getenv('ADUSER_TRACKING_SECRET')), 0, 6);

    }

    private function generateTrackingId(Request $request)
    {
        $elements = [
            // Microsecond epoch time
            microtime(true),
            // Client IP
            $request->getClientIp(),
            // Client port
            $request->getPort(),
            // Client request time (float)
            $request->server->get('REQUEST_TIME_FLOAT'),
        ];

        try {
            // 22 random bytes
            $elements[] = random_bytes(22);
        } catch (\Exception $e) {
            $elements[] = microtime(true);
        }

        $userId = substr(sha1(implode(':', $elements)), 0, 16);

        return base64_encode($userId . self::trackingIdChecksum($userId));
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
}