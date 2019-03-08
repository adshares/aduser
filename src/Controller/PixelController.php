<?php

namespace Adshares\Aduser\Controller;

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
    private $logger;
    private $providers = [];

    public function __construct(array $providers = [], LoggerInterface $logger = null)
    {
        if ($logger === null) {
            $logger = new \Psr\Log\NullLogger();
        }
        $this->logger = $logger;
        $this->providers = $providers;
    }

    public function register(Request $request, Connection $connection)
    {
        // log request
        $this->logRequest($request, $connection);

        // get tracking id
        $trackingId = $this->loadTrackingId($request, $connection);

        $redirect = null;
        $images = [];
        $pages = [];
        // data providers
        foreach ($this->providers as $provider) {
            /* @var $provider \Adshares\Aduser\Data\DataProviderInterface */
            if ($redirect = $provider->getRedirectUrl($trackingId, $request)) {
                break;
            }
            if ($image = $provider->getImageUrl($trackingId, $request)) {
                $images[] = $image;
            }
            if ($page = $provider->getPageUrl($trackingId, $request)) {
                $pages[] = $page;
            }
        }

        // render
        if (!empty($redirect)) {
            $response = new RedirectResponse($redirect);
        } elseif ($request->getRequestFormat() === 'gif' || (empty($images) && empty($pages))) {
            $response = new Response($this->getImagePixel());
            $response->headers->set('Content-Type', 'image/gif');
        } else {
            $response = new Response($this->getHtmlPixel($images, $pages));
        }

        $response->headers->setCookie(new Cookie(
            getenv('ADUSER_COOKIE_NAME'),
            $trackingId,
            time() + getenv('ADUSER_COOKIE_EXPIRY_PERIOD'),
            '/'
        ));

        return $response;
    }

    public function provider(Request $request, Connection $connection)
    {
        $name = $request->get('provider');
        if (!isset($this->providers[$name])) {
            throw new NotFoundHttpException(sprintf('Provider "%s" is not registered', $name));
        }

        return $this->providers[$name]->register($request, $connection);
    }

    private function logRequest(Request $request, Connection $connection)
    {
        try {
            $connection->insert('pixel_log', [
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

    private function loadTrackingId(Request $request, Connection $connection)
    {
        $cookieTid = $request->cookies->get(getenv('ADUSER_COOKIE_NAME'));
        try {
            $dbTid = $connection->fetchColumn(
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
                    $connection->update('user_map',
                        [
                            'tracking_id' => $trackingId
                        ],
                        [
                            'adserver_id' => $request->get('adserver'),
                            'adserver_user_id' => $request->get('user'),
                        ]);
                } else {
                    $connection->insert('user_map',
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

    private function getImagePixel(): string
    {
        return base64_decode("R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==");
    }

    private function getHtmlPixel(array $images, array $pages): string
    {
        $content = '<!DOCTYPE html><html lang="en"><body><script type="text/javascript">';
        foreach ($images as $image) {
            $content .= 'parent.postMessage({"adsharesTrack":[{"type": "image", "url": "' . $image . '"}]})';
        }
        foreach ($pages as $page) {
            $content .= 'parent.postMessage({"adsharesTrack":[{"type": "iframe", "url": "' . $page . '"}]})';
        }
        $content .= '</script></body></html>';

        return $content;
    }
}