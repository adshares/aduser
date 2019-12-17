<?php declare(strict_types = 1);

namespace Adshares\Aduser\Controller;

use Adshares\Aduser\Service\ReCaptcha;
use Adshares\Aduser\Service\Taxonomy;
use Adshares\Aduser\Utils\IdGenerator;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function time;

final class PixelController extends AbstractController
{
    /** @var ReCaptcha */
    private $reCaptcha;

    /** @var Connection */
    private $connection;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ReCaptcha $reCaptcha,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->reCaptcha = $reCaptcha;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function register(string $adserver, string $tracking, Request $request): Response
    {
        $cookieTrackingId = self::getTrackingCookie($request);
        $adserverUserId = $this->loadAdserverUserId($adserver, $tracking);
        if (($user = $this->loadUser($cookieTrackingId, $adserverUserId)) === null) {
            $user = $this->createUser($request);
        }

        if ($user['id'] !== null) {
            $this->updateAdserverUserId($user['id'], $adserver, $tracking);
        }
        $response = $this->getRegisterResponse($user, $request);

        return self::prepareResponse($user['tracking_id'], $response);
    }

    public function recaptchaRegister(string $tracking, Request $request): ?Response
    {
        $trackingId = hex2bin($tracking);
        if ($request->isMethod('POST')) {
            $this->updateUser($trackingId, $this->reCaptcha->getHumanScore($trackingId, $request));
            $response = new Response('', 204);
        } else {
            $response = new Response($this->reCaptcha->getRegisterCode());
        }

        return $response;
    }

    private function loadAdserverUserId(string $adserverId, string $trackingId): ?int
    {
        try {
            $userId = $this->connection->fetchColumn(
                'SELECT user_id FROM adserver_register WHERE adserver_id = ? AND tracking_id = ?',
                [$adserverId, $trackingId]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $userId = false;
        }

        return $userId !== false ? (int)$userId : null;
    }

    private function updateAdserverUserId(int $userId, string $adserverId, string $trackingId): void
    {
        try {
            $this->connection->executeUpdate(
                'INSERT INTO adserver_register(adserver_id, tracking_id, user_id)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE user_id = ?',
                [
                    $adserverId,
                    $trackingId,
                    $userId,
                    $userId,
                ]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function loadUser(?string $trackingId, ?int $userId): ?array
    {
        if ($trackingId !== null && !IdGenerator::validTrackingId($trackingId)) {
            $trackingId = null;
        }

        $user = false;
        try {
            $query = 'SELECT 
                    id,
                    tracking_id,
                    country,
                    human_score,
                    human_score_time
                FROM users';
            if ($trackingId !== null) {
                $user = $this->connection->fetchAssoc($query.' WHERE tracking_id = ? LIMIT 1', [$trackingId]);
            }
            if ($user === false && $userId !== null) {
                $user = $this->connection->fetchAssoc($query.' WHERE id = ? LIMIT 1', [$userId]);
            }
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $user = false;
        }

        if ($user !== false) {
            $user['id'] = (int)$user['id'];
            $user['human_score'] = $user['human_score'] !== null ? (float)$user['human_score'] : null;
            $user['human_score_time'] =
                $user['human_score_time'] !== null ? strtotime($user['human_score_time']) : null;

            return $user;
        }

        return null;
    }

    private function createUser(Request $request): array
    {
        $trackingId = IdGenerator::generateTrackingId($request);
        $country = $this->getCountry($request);
        $languages = $this->getLanguages($request);

        try {
            $this->connection->insert(
                'users',
                [
                    'tracking_id' => $trackingId,
                    'country' => $country,
                    'languages' => $languages,
                ],
                [
                    'tracking_id' => Types::BINARY,
                    'country' => Types::STRING,
                    'languages' => Types::JSON,
                ]
            );
            $userId = (int)$this->connection->lastInsertId();
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $userId = null;
        }

        return [
            'id' => $userId,
            'tracking_id' => $trackingId,
            'country' => $country,
            'languages' => $languages,
            'human_score' => null,
            'human_score_time' => null,
        ];
    }

    private function getCountry(Request $request): string
    {
        $country = strtolower($request->headers->get('cf-ipcountry', 'n/a'));
        if (!array_key_exists($country, Taxonomy::getCountries())) {
            $country = 'other';
        }

        return $country;
    }

    private function getLanguages(Request $request): array
    {
        $acceptLanguage = $request->headers->get('accept-language', 'n/a');
        $languages = [];
        $list = Taxonomy::getLanguages();
        foreach (explode(',', $acceptLanguage) as $part) {
            $code = strtolower(substr($part, 0, 2));
            if (!array_key_exists($code, $list)) {
                $code = 'other';
            }
            $languages[] = $code;
        }

        return array_values(array_unique($languages));
    }

    private function updateUser(string $trackingId, ?float $humanScore = null): void
    {
        if ($humanScore === null) {
            return;
        }

        if (!IdGenerator::validTrackingId($trackingId)) {
            return;
        }

        $data = $types = [];
        if ($humanScore !== null) {
            $data['human_score'] = $humanScore;
            $data['human_score_time'] = new DateTimeImmutable();
            $types['human_score'] = Types::FLOAT;
            $types['human_score_time'] = Types::DATETIME_IMMUTABLE;
        }

        try {
            $this->connection->update('users', $data, ['tracking_id' => $trackingId], $types);
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function getRegisterResponse(array $user, Request $request): Response
    {
        $trackingId = $user['tracking_id'];

        $images = [];
        $pages = [];

        if ($user['human_score'] === null
            || $user['human_score_time'] < time() - $_ENV['ADUSER_HUMAN_SCORE_EXPIRY_PERIOD']) {
            $pages[] = $this->reCaptcha->getPageUrl($trackingId);
        }

        return new Response($this->getHtmlPixel($images, array_filter($pages)));
    }

    private function getHtmlPixel(array $images, array $pages, array $sync = []): string
    {
        $content = '<!DOCTYPE html><html lang="en"><head>';
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
        $content .= "\n".'</script></body></html>';

        return $content;
    }

    private static function prepareResponse(string $trackingId, Response $response): Response
    {
        $response->headers->setCookie(
            Cookie::create(
                $_ENV['ADUSER_COOKIE_NAME'],
                base64_encode($trackingId),
                time() + $_ENV['ADUSER_COOKIE_EXPIRY_PERIOD'],
                '/',
                null,
                null,
                true,
                false,
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

    private static function getTrackingCookie(Request $request): ?string
    {
        $value = $request->cookies->get($_ENV['ADUSER_COOKIE_NAME']);

        return $value !== null ? base64_decode($value) : null;
    }
}
