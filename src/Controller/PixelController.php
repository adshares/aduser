<?php

/**
 * Copyright (c) 2018-2023 Adshares sp. z o.o.
 *
 * This file is part of AdUser
 *
 * AdUser is free software: you can redistribute and/or modify it
 * under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AdUser is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AdServer. If not, see <https://www.gnu.org/licenses/>
 */

declare(strict_types=1);

namespace App\Controller;

use App\Service\DclHeadersVerifierInterface;
use App\Service\Fingerprint;
use App\Service\ReCaptcha;
use App\Service\Taxonomy;
use App\Utils\IdGenerator;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PixelController extends AbstractController
{
    private ?string $cookieName = '__au';
    private int $cookieExpiryPeriod = 31536000;
    private int $humanScoreExpiryPeriod = 3600;
    private int $fingerprintExpiryPeriod = 24 * 3600;

    public function __construct(
        private readonly IdGenerator $idGenerator,
        private readonly ReCaptcha $reCaptcha,
        private readonly Fingerprint $fingerprint,
        private readonly Connection $connection,
        private readonly DclHeadersVerifierInterface $dclHeadersVerifier,
        private readonly LoggerInterface $logger
    ) {
    }

    public function setCookieSettings(?string $cookieName, int $cookieExpiryPeriod): self
    {
        $this->cookieName = $cookieName;
        $this->cookieExpiryPeriod = $cookieExpiryPeriod;
        return $this;
    }

    public function setHumanScoreSettings(int $humanScoreExpiryPeriod): self
    {
        $this->humanScoreExpiryPeriod = $humanScoreExpiryPeriod;
        return $this;
    }

    public function setFingerprintSettings(int $fingerprintExpiryPeriod): self
    {
        $this->fingerprintExpiryPeriod = $fingerprintExpiryPeriod;
        return $this;
    }

    #[Route(
        '/{slug}/{adserver}/{tracking}/{nonce}.{_format}',
        name: 'pixel_register',
        requirements: [
            'slug' => '[a-zA-Z0-9_:.-]{8}',
            'adserver' => '[a-zA-Z0-9_:.-]+',
            'tracking' => '[a-zA-Z0-9_:.-]+',
            'nonce' => '[a-zA-Z0-9_:.-]+',
            '_format' => 'html|htm',
        ],
        defaults: ['_format' => 'html'],
        methods: ['GET', 'OPTIONS'],
    )]
    public function register(string $adserver, string $tracking, Request $request): Response
    {
        $response = new Response();
        if ($request->headers->has('Origin')) {
            $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
            $response->headers->set('Access-Control-Allow-Headers', '*');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        }
        if ('OPTIONS' === $request->getRealMethod()) {
            $response->setStatusCode(Response::HTTP_NO_CONTENT);
            $response->headers->set('Access-Control-Max-Age', '1728000');
            return $response;
        }

        $cookieTrackingId = $this->getTrackingCookie($request);
        $adserverUserId = $this->loadAdserverUserId($adserver, $tracking);
        $externalUserId = $this->getExternalUserId($request);
        if (null === ($user = $this->loadUser($cookieTrackingId, $adserverUserId, $externalUserId))) {
            $user = $this->createUser($request, $externalUserId);
        }

        if ($user['id'] !== null) {
            $this->updateAdserverUserId($user['id'], $adserver, $tracking);
        }
        $response->setContent($this->getRegisterResponseContent($user));

        return $this->prepareResponse($user['tracking_id'], $response);
    }

    #[Route(
        '/re/{tracking}/{nonce}.html',
        name: 'pixel_recaptcha',
        requirements: [
            'tracking' => '[0-9a-f]+',
            'nonce' => '[0-9a-f]+',
        ],
    )]
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

    #[Route(
        '/fp/{tracking}/{nonce}.html',
        name: 'pixel_fingerprint',
        requirements: [
            'tracking' => '[0-9a-f]+',
            'nonce' => '[0-9a-f]+',
        ],
    )]
    public function fingerprintRegister($tracking, Request $request): Response
    {
        $trackingId = hex2bin($tracking);
        if ($request->isMethod('POST')) {
            $this->updateUser($trackingId, null, $this->fingerprint->getHash($trackingId, $request));
            $response = new Response('', 204);
        } else {
            $response = new Response($this->fingerprint->getRegisterCode());
        }
        return $response;
    }

    private function loadAdserverUserId(string $adserverId, string $trackingId): ?int
    {
        try {
            $userId = $this->connection->fetchOne(
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
            $this->connection->executeStatement(
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

    private function loadUser(?string $trackingId, ?int $userId, ?string $externalUserId): ?array
    {
        if ($trackingId !== null && !$this->idGenerator->validTrackingId($trackingId)) {
            $trackingId = null;
        }

        $user = false;
        try {
            $query = 'SELECT 
                    id,
                    tracking_id,
                    country,
                    human_score,
                    human_score_time,
                    fingerprint,
                    fingerprint_time,
                    mapped_user_id,
                    external_user_id
                FROM users';
            if ($trackingId !== null) {
                $user = $this->connection->fetchAssociative($query . ' WHERE tracking_id = ? LIMIT 1', [$trackingId]);
            }
            if (false === $user && null !== $externalUserId) {
                $user = $this->connection->fetchAssociative(
                    $query . ' WHERE external_user_id = ? LIMIT 1',
                    [$externalUserId]
                );
            }
            if ($user === false && $userId !== null) {
                $user = $this->connection->fetchAssociative($query . ' WHERE id = ? LIMIT 1', [$userId]);
            }

            if ($user !== false && $user['mapped_user_id'] !== null) {
                $mappedUser = $this->connection->fetchAssociative(
                    $query . ' WHERE id = ? LIMIT 1',
                    [$user['mapped_user_id']]
                );
                if ($mappedUser !== false) {
                    $user = $mappedUser;
                }
            }
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $user = false;
        }

        if ($user !== false) {
            if (null === $user['external_user_id'] && null !== $externalUserId) {
                try {
                    $this->connection->update('users', ['external_user_id' => $externalUserId], ['id' => $user['id']]);
                } catch (DBALException $exception) {
                    $this->logger->error(sprintf('Updating external_user_id failed: %s', $exception->getMessage()));
                }
            }

            $user['id'] = (int)$user['id'];
            $user['human_score'] = $user['human_score'] !== null ? (float)$user['human_score'] : null;
            $user['human_score_time'] =
                $user['human_score_time'] !== null ? strtotime($user['human_score_time']) : null;
            $user['fingerprint_time'] =
                $user['fingerprint_time'] !== null ? strtotime($user['fingerprint_time']) : null;
            $user['mapped_user_id'] = $user['mapped_user_id'] !== null ? (int)$user['mapped_user_id'] : null;

            return $user;
        }

        return null;
    }

    private function createUser(Request $request, ?string $externalUserId): array
    {
        $trackingId = $this->idGenerator->generateTrackingId($request);
        $country = $this->getCountry($request);
        $languages = $this->getLanguages($request);

        try {
            $this->connection->insert(
                'users',
                [
                    'tracking_id' => $trackingId,
                    'country' => $country,
                    'languages' => $languages,
                    'external_user_id' => $externalUserId,
                ],
                [
                    'tracking_id' => Types::BINARY,
                    'country' => Types::STRING,
                    'languages' => Types::JSON,
                    'external_user_id' => Types::STRING,
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
            'fingerprint' => null,
            'fingerprint_time' => null,
            'mapped_user_id' => null,
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

    private function updateUser(string $trackingId, ?float $humanScore = null, ?string $fingerprint = null): void
    {
        if ($humanScore === null && $fingerprint === null) {
            return;
        }

        if (!$this->idGenerator->validTrackingId($trackingId)) {
            return;
        }

        $data = $types = [];
        if ($humanScore !== null) {
            $data['human_score'] = $humanScore;
            $data['human_score_time'] = new DateTimeImmutable();
            $types['human_score'] = Types::FLOAT;
            $types['human_score_time'] = Types::DATETIME_IMMUTABLE;
        }
        if ($fingerprint !== null) {
            $data['fingerprint'] = $fingerprint;
            $data['fingerprint_time'] = new DateTimeImmutable();
            $types['fingerprint'] = Types::STRING;
            $types['fingerprint_time'] = Types::DATETIME_IMMUTABLE;
        }

        try {
            $this->connection->update('users', $data, ['tracking_id' => $trackingId], $types);
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function getRegisterResponseContent(array $user): string
    {
        $trackingId = $user['tracking_id'];

        $images = [];
        $pages = [];

        if (
            $user['human_score'] === null
            || $user['human_score_time'] < time() - $this->humanScoreExpiryPeriod
        ) {
            $pages[] = $this->reCaptcha->getPageUrl($trackingId);
        }

        if (
            empty($user['fingerprint'])
            || $user['fingerprint_time'] < time() - $this->fingerprintExpiryPeriod
        ) {
            $pages[] = $this->fingerprint->getPageUrl($trackingId);
        }

        return $this->getHtmlPixel($images, array_filter($pages));
    }

    private function getHtmlPixel(array $images, array $pages, array $sync = []): string
    {
        $content = '<!DOCTYPE html><html lang="en"><head>';
        $content .= "\n" . '</head><body>';
        foreach ($sync as $image) {
            $content .= "\n" . '<img src="' . $image . '" width="1" height="1" alt="" />';
        }
        $content .= "\n" . '<script type="text/javascript">';
        foreach ($images as $image) {
            $content .= "\n" . 'parent.postMessage({"insertElem":[{"type": "img", "url": "' . $image . '"}]}, "*");';
        }
        foreach ($pages as $page) {
            $content .= "\n" . 'parent.postMessage({"insertElem":[{"type": "iframe", "url": "' . $page . '"}]}, "*");';
        }
        $content .= "\n" . '</script></body></html>';

        return $content;
    }

    private function prepareResponse(string $trackingId, Response $response): Response
    {
        if (!empty($this->cookieName)) {
            $response->headers->setCookie(
                Cookie::create(
                    $this->cookieName,
                    base64_encode($trackingId),
                    time() + $this->cookieExpiryPeriod,
                    '/',
                    null,
                    true,
                    true,
                    false,
                    'none'
                )
            );
        }

        $response->setCache(
            [
                'max_age' => 0,
                's_maxage' => 0,
                'private' => true,
            ]
        );

        return $response;
    }

    private function getTrackingCookie(Request $request): ?string
    {
        $value = !empty($this->cookieName) ? $request->cookies->get($this->cookieName) : null;
        return $value !== null ? base64_decode($value) : null;
    }

    private function getExternalUserId(Request $request): ?string
    {
        if ($this->dclHeadersVerifier->verify($request->headers)) {
            return $this->dclHeadersVerifier->getUserId($request->headers);
        }
        return null;
    }
}
