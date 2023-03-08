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

use App\Service\PageInfo;
use App\Service\RequestInfo;
use App\Utils\UrlValidator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v{apiVersion}', name: 'api_', requirements: ['apiVersion' => '1|2'])]
final class ApiController extends AbstractController
{
    private PageInfo $pageInfo;
    private RequestInfo $requestInfo;
    private Connection $connection;
    private LoggerInterface $logger;
    private float $humanScoreDefault = 0.48;
    private int $humanScoreExpiryPeriod = 3600;
    private float $humanScoreNoFingerprint = 0.41;
    private float $pageRankDefault = 0.0;

    public function __construct(
        PageInfo $pageInfo,
        RequestInfo $requestInfo,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->pageInfo = $pageInfo;
        $this->requestInfo = $requestInfo;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function setHumanScoreSettings(
        float $humanScoreDefault,
        int $humanScoreExpiryPeriod,
        float $humanScoreNoFingerprint
    ): self {
        $this->humanScoreDefault = $humanScoreDefault;
        $this->humanScoreExpiryPeriod = $humanScoreExpiryPeriod;
        $this->humanScoreNoFingerprint = $humanScoreNoFingerprint;
        return $this;
    }

    public function setPageRankSettings(float $pageRankDefault): self
    {
        $this->pageRankDefault = $pageRankDefault;
        return $this;
    }

    #[Route('/taxonomy', name: 'taxonomy', methods: ['GET'])]
    public function taxonomy(int $apiVersion): Response
    {
        $this->pageInfo->version($apiVersion);
        return new JsonResponse($this->pageInfo->getTaxonomy());
    }

    #[Route(
        '/data/{adserver}/{tracking}',
        name: 'data',
        requirements: [
            'adserver' => '[a-zA-Z0-9_:.-]+',
            'tracking' => '[a-zA-Z0-9_:.-]+',
        ],
        methods: ['GET', 'POST', 'OPTIONS'],
    )]
    public function data(int $apiVersion, string $adserver, string $tracking, Request $request): Response
    {
        $this->pageInfo->version($apiVersion);
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
            'Access-Control-Max-Age' => 24 * 3600,
        ];
        if ($request->isMethod('OPTIONS')) {
            return new Response('', Response::HTTP_NO_CONTENT, $headers);
        }

        $users = $this->getUsers($adserver, [$tracking]);
        $user = array_pop($users) ?? [];
        $params = $request->isMethod('GET') ? $request->query : $request->request;

        $this->logger->info(sprintf('Fetching data for %s:%s', $adserver, $tracking), $params->all());
        $this->logger->debug(sprintf('User: %s', $user['id'] ?? 'unknown'), $user);

        $data = $this->getData($user, new ParameterBag($params->all()));

        return new JsonResponse($data, Response::HTTP_OK, $headers);
    }

    #[Route(
        '/data/{adserver}',
        name: 'data_batch',
        requirements: ['adserver' => '[a-zA-Z0-9_:.-]+'],
        methods: ['POST'],
    )]
    public function batch(int $apiVersion, string $adserver, Request $request): Response
    {
        $this->pageInfo->version($apiVersion);
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            throw new BadRequestHttpException(json_last_error_msg());
        }

        $userIds = [];
        foreach ($data as $row) {
            if (isset($row['user'])) {
                $userIds[] = $row['user'];
            }
        }
        $users = $this->getUsers($adserver, $userIds);

        $response = [];
        foreach ($data as $key => $row) {
            if (!is_array($row) || !isset($row['user'])) {
                continue;
            }
            $user = [];
            if (isset($users[$row['user']])) {
                $user = $users[$row['user']];
            }

            $this->logger->info(sprintf('Fetching data for %s:%s', $adserver, $row['user']), $row);
            $this->logger->debug(sprintf('User: %s', $user['id'] ?? 'unknown'), $user);

            $response[$key] = $this->getData($user, new ParameterBag($row));
        }

        return new JsonResponse($response);
    }

    #[Route('/page-rank/{url}', name: 'page_rank', requirements: ['url' => '.+'], methods: ['GET', 'OPTIONS'])]
    public function pageRank(int $apiVersion, string $url, Request $request): Response
    {
        $this->pageInfo->version($apiVersion);
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Max-Age' => 24 * 3600,
        ];
        if ($request->isMethod('OPTIONS')) {
            return new Response('', Response::HTTP_NO_CONTENT, $headers);
        }

        if (!UrlValidator::isValid($url)) {
            throw new UnprocessableEntityHttpException('Invalid URL');
        }
        $pageRank = $this->pageInfo->getPageRank($url, $request->get('categories', []));

        $response = [
            'rank' => $pageRank['rank'],
            'info' => $pageRank['info'],
            'categories' => $pageRank['categories'],
            'quality' => $pageRank['quality'],
        ];

        return new JsonResponse($response, Response::HTTP_OK, $headers);
    }

    #[Route('/page-rank', name: 'page_rank_batch', methods: ['POST'])]
    public function pageRankBatch(int $apiVersion, Request $request): Response
    {
        $this->pageInfo->version($apiVersion);
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            throw new BadRequestHttpException(json_last_error_msg());
        }
        if (!isset($data['urls'])) {
            throw new BadRequestHttpException('Field `urls` is required');
        }
        $urls = $data['urls'];
        if (!is_array($urls)) {
            throw new BadRequestHttpException('Field `urls` must be an array');
        }

        $result = [];
        foreach ($urls as $id => $urlData) {
            $url = $urlData['url'] ?? null;
            if (UrlValidator::isValid($url)) {
                $pageRank = $this->pageInfo->getPageRank($url, $urlData['categories'] ?? []);
                $result[$id] = [
                    'rank' => $pageRank['rank'],
                    'info' => $pageRank['info'],
                    'categories' => $pageRank['categories'],
                    'quality' => $pageRank['quality'],
                ];
            } else {
                $result[$id] = ['error' => 'Invalid URL'];
            }
        }

        return new JsonResponse($result);
    }

    #[Route('/reassessment', name: 'reassessment_batch', methods: ['POST'])]
    public function reassessmentBatch(int $apiVersion, Request $request): Response
    {
        $this->pageInfo->version($apiVersion);
        if (null === ($data = json_decode($request->getContent(), true))) {
            throw new BadRequestHttpException(json_last_error_msg());
        }
        return new JsonResponse($this->pageInfo->reassessment($data));
    }

    private function getData(array $user, ParameterBag $params): array
    {
        $pageRank = $this->getPageRank($params);

        $keywords = [
            'user' => array_filter([
                'external_user_id' => $user['external_user_id'] ?? null,
                'language' => $user['languages'] ?? null,
                'country' => $user['country'] ?? null,
                'cookie3-tag' => $this->requestInfo->getCookie3Tags($params),
            ]),
            'device' => $this->requestInfo->getDeviceKeywords($params),
            'site' => $this->requestInfo->getSiteKeywords($params),
        ];
        $keywords['site'] = array_merge($keywords['site'], [
            'category' => $pageRank['categories'],
            'quality' => $pageRank['quality'],
        ]);

        $response = [
            'uuid' => $user['tracking_id'] ?? null,
            'human_score' => $this->getHumanScore($user, $params),
            'page_rank' => $pageRank['rank'],
            'page_rank_info' => $pageRank['info'],
            'keywords' => array_filter($keywords),
        ];

        $this->logger->info(sprintf('UUID: %s', $response['uuid']));
        $this->logger->info(sprintf('Human score: %f', $response['human_score']));
        $this->logger->info(sprintf('Page rank: %f', $response['page_rank']));
        $this->logger->info(sprintf('Keywords: %s', json_encode($response['keywords'])));

        return $response;
    }

    private function getHumanScore(array $user, ParameterBag $params): float
    {
        $humanScore = null;

        $scoreTime = $user['human_score_time'] ?? 0;
        $eventTime = $params->get('event_time', time());
        $expiryPeriod = $this->humanScoreExpiryPeriod * 2;

        if ($eventTime - $scoreTime <= $expiryPeriod) {
            $humanScore = (float)$user['human_score'];
        }

        if (empty($user['fingerprint'] ?? null)) {
            $humanScore = min($humanScore ?? 1, $this->humanScoreNoFingerprint);
        }

        if ($this->requestInfo->isCrawler($params)) {
            $humanScore = 0.0;
        }

        return $humanScore ?? $this->humanScoreDefault;
    }

    private function getPageRank(ParameterBag $params): array
    {
        if (($requestUrl = $params->get('url')) !== null) {
            $pageRank = $this->pageInfo->getPageRank($requestUrl, []);
        } else {
            $this->logger->debug('Cannot find URL', $params->all());
        }

        return [
            'rank' => (float)($pageRank['rank'] ?? $this->pageRankDefault),
            'info' => $pageRank['info'] ?? PageInfo::INFO_UNKNOWN,
            'categories' => $pageRank['categories'] ?? [PageInfo::INFO_UNKNOWN],
            'quality' => $pageRank['quality'] ?? PageInfo::INFO_UNKNOWN,
        ];
    }

    private function getUsers(string $adserverId, array $trackingIds): array
    {
        $users = [];
        try {
            foreach (
                $this->connection->fetchAllAssociative(
                    'SELECT
                        u.id,
                        r.tracking_id as adserver_tracking_id,
                        u.tracking_id,
                        u.country,
                        u.languages,
                        u.human_score,
                        u.human_score_time,
                        u.fingerprint,
                        u.external_user_id
                      FROM adserver_register r
                      JOIN users u ON u.id = r.user_id
                      WHERE r.adserver_id = ? AND r.tracking_id IN (?)',
                    [
                        $adserverId,
                        $trackingIds,
                    ],
                    [
                        Types::STRING,
                        Connection::PARAM_STR_ARRAY,
                    ]
                ) as $row
            ) {
                $users[$row['adserver_tracking_id']] = [
                    'id' => (int)$row['id'],
                    'tracking_id' => bin2hex($row['tracking_id']),
                    'country' => (string)$row['country'],
                    'languages' => json_decode($row['languages'], true),
                    'human_score' => $row['human_score'] !== null ? (float)$row['human_score'] : null,
                    'human_score_time' => $row['human_score_time'] !== null
                        ? strtotime($row['human_score_time'])
                        : null,
                    'fingerprint' => $row['fingerprint'],
                    'external_user_id' => $row['external_user_id'],
                ];
            }
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }

        return $users;
    }
}
