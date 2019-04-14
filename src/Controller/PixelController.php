<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Controller;

use Adshares\Aduser\DataProvider\AbstractDataProvider;
use Adshares\Aduser\DataProvider\DataProviderInterface;
use Adshares\Aduser\DataProvider\DataProviderManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

        $dbTid = $this->updateUserMap($trackingId, $request->get('adserver'), $request->get('user'));
        $this->updateTracking($trackingId, $dbTid, $request);

        if ($request->getRequestFormat() === 'gif') {
            $response = $this->syncRegister($trackingId, $request);
        } else {
            $response = $this->asyncRegister($trackingId, $request);
        }

        $response->headers->setCookie(
            new Cookie(
                getenv('ADUSER_COOKIE_NAME'),
                $trackingId,
                time() + getenv('ADUSER_COOKIE_EXPIRY_PERIOD'),
                '/',
                null,
                null
            )
        );
        $response->setCache(
            [
                'max_age' => 0,
                's_maxage' => 0,
                'private' => true,
            ]
        );

        return $response;
    }

    public function provider(Request $request): Response
    {
        $trackingId = $request->get('tracking');
        $name = $request->get('provider');

        if (!self::validTrackingId($trackingId)) {
            throw new BadRequestHttpException('Invalid tracking id');
        }

        $this->logRequest('provider', $trackingId, $request);

        $provider = $this->providers->get($name);

        $response = $provider->register($trackingId, $request);
        $response->setCache(
            [
                'max_age' => 0,
                's_maxage' => 0,
                'private' => true,
            ]
        );

        return $response;
    }

    public function sync(Request $request): Response
    {
        $trackingId = $request->get('tracking');
        $cookieTid = $request->cookies->get(getenv('ADUSER_COOKIE_NAME'));

        if (!self::validTrackingId($trackingId)) {
            throw new BadRequestHttpException('Invalid tracking id');
        }

        if (empty($cookieTid) || !self::validTrackingId($cookieTid)) {
            $cookieTid = $trackingId;
        }

        if ($cookieTid !== $trackingId) {
            //TODO add tracking id map
        }

        $response = AbstractDataProvider::createImageResponse();
        $response->headers->setCookie(
            new Cookie(
                getenv('ADUSER_COOKIE_NAME'),
                $cookieTid,
                time() + getenv('ADUSER_COOKIE_EXPIRY_PERIOD'),
                '/',
                null,
                null
            )
        );

        return $response;
    }

    public function fingerprint(Request $request): Response
    {
        $this->updateFingerprint($request);

        return new Response('', 204);
    }

    private function loadTrackingId(Request $request): string
    {
        $cookieTid = $request->cookies->get(getenv('ADUSER_COOKIE_NAME'));
        if (!empty($cookieTid) && self::validTrackingId($cookieTid)) {
            $trackingId = $cookieTid;
        } else {
            $this->logger->debug('Generating tracking id');
            $trackingId = self::generateTrackingId($request);
        }

        $this->logger->info(sprintf('Tracking id: %s', $trackingId));

        return $trackingId;
    }

    private static function validTrackingId(string $trackingId): bool
    {
        $id = base64_decode($trackingId);
        $userId = substr($id, 0, 16);
        $checksum = substr($id, 16, 22);

        return self::trackingIdChecksum($userId) === $checksum;
    }

    private static function trackingIdChecksum(string $userId): string
    {
        $secret = getenv('TRACKING_SECRET') ?: getenv('ADUSER_TRACKING_SECRET');

        return substr(sha1($userId.$secret), 0, 6);
    }

    private static function generateTrackingId(Request $request): string
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

        return base64_encode($userId.self::trackingIdChecksum($userId));
    }

    private function updateTracking(string $trackingId, ?string $dbTid, Request $request): void
    {
        $userId = $this->loadUserId($trackingId, $dbTid);
        $this->saveTrackingId($trackingId, $userId, $request);
        $this->saveLocation($trackingId, $request);
        if ($dbTid !== null) {
            $this->addTrackingMap($dbTid, $trackingId);
        }
    }

    private function saveTrackingId(string $trackingId, string $userId, Request $request): void
    {
        $data = [
            'user_id' => $userId,
            'user_agent' => $request->headers->get('user-agent'),
            'accept' => $request->headers->get('accept'),
            'accept_encoding' => $request->headers->get('accept-encoding'),
            'accept_language' => $request->headers->get('accept-language'),
        ];

        try {
            $uid = $this->connection->fetchColumn(
                'SELECT user_id FROM tracking WHERE tracking_id = ?',
                [$trackingId]
            );

            if ($uid !== false) {
                $this->connection->update(
                    'tracking',
                    $data,
                    ['tracking_id' => $trackingId]
                );
            } else {
                $this->connection->insert(
                    'tracking',
                    array_merge(['tracking_id' => $trackingId], $data)
                );
            }
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function loadUserId(string $trackingId, ?string $dbTid): string
    {
        try {
            $userId = $this->connection->fetchColumn(
                'SELECT user_id FROM tracking WHERE tracking_id = ?',
                [$trackingId]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $userId = false;
        }

        if ($userId === false && $dbTid !== null) {
            try {
                $userId = $this->connection->fetchColumn(
                    'SELECT user_id FROM tracking WHERE tracking_id = ?',
                    [$dbTid]
                );
            } catch (DBALException $e) {
                $this->logger->error($e->getMessage());
                $userId = false;
            }
        }

        if ($userId === false) {
            $userId = self::generateUserId();
            try {
                $this->connection->insert(
                    'user',
                    ['user_id' => $userId]
                );
            } catch (DBALException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $userId;
    }

    private static function generateUserId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    private function saveLocation(string $trackingId, Request $request): void
    {
        $ip = $request->getClientIp();
        $country = $request->headers->get('cf-ipcountry', 'n/a');

        try {
            $count = $this->connection->fetchColumn(
                'SELECT count FROM location WHERE tracking_id = ? AND ip = ? AND country = ?',
                [$trackingId, $ip, $country]
            );

            if ($count !== false) {
                $this->connection->update(
                    'location',
                    [
                        'count' => (int)$count + 1,
                        'updated_at' => new \DateTime(),
                    ],
                    [
                        'tracking_id' => $trackingId,
                        'ip' => $ip,
                        'country' => $country,
                    ],
                    [
                        'integer',
                        'datetime',
                    ]
                );
            } else {
                $this->connection->insert(
                    'location',
                    [
                        'tracking_id' => $trackingId,
                        'ip' => $ip,
                        'country' => $country,
                    ]
                );
            }
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function addTrackingMap(string $trackingIdA, string $trackingIdB): void
    {
        if (empty($trackingIdA) || empty($trackingIdB) || $trackingIdA === $trackingIdB) {
            return;
        }

        try {
            $this->connection->insert(
                'tracking_map',
                [
                    'tracking_id_a' => $trackingIdA,
                    'tracking_id_b' => $trackingIdB,
                ]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function updateUserMap(string $trackingId, string $adserverId, string $userId): ?string
    {
        try {
            $dbTid = $this->connection->fetchColumn(
                'SELECT tracking_id FROM user_map WHERE adserver_id = ? AND adserver_user_id = ?',
                [$adserverId, $userId]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $dbTid = false;
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
                            'adserver_id' => $adserverId,
                            'adserver_user_id' => $userId,
                        ]
                    );
                } else {
                    $this->connection->insert(
                        'user_map',
                        [
                            'tracking_id' => $trackingId,
                            'adserver_id' => $adserverId,
                            'adserver_user_id' => $userId,
                        ]
                    );
                }
            } catch (DBALException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $dbTid !== false ? (string)$dbTid : null;
    }

    private static function getRequestField(Request $request, string $key, string $type = 'string')
    {
        $value = $request->request->get($key);
        if ($value === null) {
            return $value;
        }

        switch ($type) {
            case 'bool':
                return (int)in_array($value, [true, 1, '1', 'true', 'on'], true);
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            default:
                return (string)$value;
        }
    }

    private function updateFingerprint(Request $request): void
    {
        $trackingId = $request->get('tracking');

        try {
            $register = $this->connection->fetchAssoc(
                'SELECT user_agent,  accept, accept_encoding, accept_language 
                FROM tracking 
                WHERE tracking_id = ?',
                [$trackingId]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $register = false;
        }

        if ($register === false) {
            return;
        }

        $data = [
            'language' => self::getRequestField($request, 'language'),
            'color_depth' => self::getRequestField($request, 'colorDepth', 'int'),
            'device_memory' => self::getRequestField($request, 'deviceMemory', 'int'),
            'hardware_concurrency' => self::getRequestField($request, 'hardwareConcurrency', 'int'),
            'screen_resolution' => self::getRequestField($request, 'screenResolution'),
            'available_screen_resolution' => self::getRequestField($request, 'availableScreenResolution'),
            'timezone_offset' => self::getRequestField($request, 'timezoneOffset', 'int'),
            'timezone' => self::getRequestField($request, 'timezone'),
            'session_storage' => self::getRequestField($request, 'sessionStorage', 'bool'),
            'local_storage' => self::getRequestField($request, 'localStorage', 'bool'),
            'indexed_db' => self::getRequestField($request, 'indexedDb', 'bool'),
            'add_behavior' => self::getRequestField($request, 'addBehavior', 'bool'),
            'open_database' => self::getRequestField($request, 'openDatabase', 'bool'),
            'cpu_class' => self::getRequestField($request, 'cpuClass'),
            'platform' => self::getRequestField($request, 'platform'),
            'plugins' => self::getRequestField($request, 'plugins'),
            'canvas' => self::getRequestField($request, 'canvas'),
            'webgl' => self::getRequestField($request, 'webgl'),
            'webgl_vendor_and_renderer' => self::getRequestField($request, 'webglVendorAndRenderer'),
            'ad_block' => self::getRequestField($request, 'adBlock', 'bool'),
            'has_lied_languages' => self::getRequestField($request, 'hasLiedLanguages', 'bool'),
            'has_lied_resolution' => self::getRequestField($request, 'hasLiedResolution', 'bool'),
            'has_lied_os' => self::getRequestField($request, 'hasLiedOs', 'bool'),
            'has_lied_browser' => self::getRequestField($request, 'hasLiedBrowser', 'bool'),
            'touch_support' => self::getRequestField($request, 'touchSupport'),
            'fonts' => self::getRequestField($request, 'fonts'),
            'audio' => self::getRequestField($request, 'audio'),
        ];

        if (!empty(array_filter($data))) {
            $hash = md5(implode('', array_merge($register, $data)));
        } else {
            $hash = null;
        }

        try {
            $this->connection->update(
                'tracking',
                array_merge(['hash' => $hash], $data),
                ['tracking_id' => $trackingId]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function logRequest(string $type, string $trackingId, Request $request): void
    {
        $this->logger->debug(sprintf('%s log: %s -> %s', $type, $trackingId, $request));

        try {
            $this->connection->insert(
                "{$type}_log",
                [
                    'tracking_id' => $trackingId,
                    'uri' => $request->getRequestUri(),
                    'attributes' => json_encode($request->attributes->get('_route_params')),
                    'query' => json_encode($request->query->all()),
                    'request' => json_encode($request->request->all()),
                    'headers' => json_encode($request->headers->all()),
                    'cookies' => json_encode($request->cookies->all()),
                    'ip' => $request->getClientIp(),
                    'ips' => json_encode($request->getClientIps()),
                    'port' => (int)$request->getPort(),
                ]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function syncRegister(string $trackingId, Request $request): Response
    {
        $redirect = null;
        $response = null;

        foreach ($this->providers as $provider) {
            $r = $provider->register($trackingId, $request);
            if ($response === null) {
                $response = $r;
            }
            if ($redirect === null) {
                $redirect = $provider->getRedirect($trackingId, $request);
            }
        }

        return $redirect !== null ? $redirect : $response;
    }

    private function asyncRegister(string $trackingId, Request $request): Response
    {
        $images = [];
        $pages = [];
        $sync = self::getSyncImages($trackingId, $request);

        foreach ($this->providers as $provider) {
            if ($image = (string)$provider->getImageUrl($trackingId, $request)) {
                $images[] = $image;
            }
            if ($page = (string)$provider->getPageUrl($trackingId, $request)) {
                $pages[] = $page;
            }
        }

        $fgCode = self::getFingerprintCode($trackingId);

        return new Response($this->getHtmlPixel($images, $pages, $fgCode, $sync));
    }

    private function getSyncImages($trackingId, Request $request, $length = 3): array
    {
        $sync = [];
        $available = array_filter(explode(',', getenv('ADUSER_DOMAINS')));
        if (count($available) > 0) {
            srand(crc32($request->getHost().date('-d-m-Y-h')));
            foreach ((array)array_rand($available, min($length, count($available))) as $key) {
                $sync[] = str_replace(
                    $request->getHost(),
                    $available[$key],
                    $this->generateUrl(
                        'pixel_sync',
                        [
                            'tracking' => $trackingId,
                            'nonce' => AbstractDataProvider::generateNonce(),
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                );
            }
        }

        return $sync;
    }

    private function getFingerprintCode($trackingId)
    {
        $fgUrl = $this->generateUrl(
            'pixel_fingerprint',
            [
                'tracking' => $trackingId,
                'nonce' => AbstractDataProvider::generateNonce(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return 'const fgp = function() { Fingerprint2.get(function (c) {
              const f = new FormData();
              for(var k in c) { f.append(c[k].key, ["canvas", "webgl"].includes(c[k].key) ? Fingerprint2.x64hash128(c[k].value.join(""), 31) : c[k].value) }
              const r = new XMLHttpRequest(); r.open("POST", "'.$fgUrl.'"); r.send(f);
        })}
        if (window.requestIdleCallback) { requestIdleCallback(fgp); } else { setTimeout(fgp, 200); }';
    }

    private function getHtmlPixel(array $images, array $pages, $code = '', array $sync = []): string
    {
        $content = '<!DOCTYPE html><html lang="en"><head>';
        $content .= "\n"
            .'<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/fingerprintjs2/2.0.6/fingerprint2.min.js"></script>';
        $content .= "\n".'</head><body>';
        foreach ($sync as $image) {
            $content .= "\n".'<img src="'.$image.'" width="1" height="1" alt="" />';
        }
        $content .= "\n".'<script type="text/javascript">';
        foreach ($images as $image) {
            $content .= "\n".'parent.postMessage({"insertElem":[{"type": "img", "url": "'.$image.'"}]}, "*");';
        }
        foreach ($pages as $page) {
            $content .= "\n".'parent.postMessage({"insertElem":[{"type": "iframe", "url": "'.$page.'"}]}, "*");';
        }
        $content .= "\n".$code;
        $content .= "\n".'</script></body></html>';

        return $content;
    }
}
