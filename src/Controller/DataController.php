<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PageInfo;
use App\Service\RequestInfo;
use App\Service\Taxonomy;
use App\Utils\SiteCategory;
use App\Utils\UrlNormalizer;
use App\Utils\UrlValidator;
use DateTimeImmutable;
use DateTimeZone;
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

final class DataController extends AbstractController
{
    private PageInfo $pageInfo;

    private RequestInfo $requestInfo;

    private Connection $connection;

    private LoggerInterface $logger;

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

    public function taxonomy(): Response
    {
        $taxonomy = [
            'meta' => [
                'name' => $_ENV['TAXONOMY_NAME'],
                'version' => $_ENV['TAXONOMY_VERSION'],
            ],
            'data' => Taxonomy::getTaxonomy(),
        ];

        return new JsonResponse($taxonomy);
    }

    public function data(string $adserver, string $tracking, Request $request): Response
    {
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

        $data = $this->getData($user, $params);

        return new JsonResponse($data, Response::HTTP_OK, $headers);
    }

    public function batch(string $adserver, Request $request): Response
    {
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

    public function pageRank(string $url, Request $request): Response
    {
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
            return new Response('Invalid URL', Response::HTTP_UNPROCESSABLE_ENTITY, $headers);
        }

        $categories = $this->extractCategories(
            $request->get('categories'),
            SiteCategory::getCategoryValueToIncludedCategoriesValuesMap(SiteCategory::getTaxonomySiteCategories())
        );
        $pageRank = $this->getPageRankWithNote($url, $categories);

        $response = [
            'rank' => $pageRank['rank'],
            'info' => $pageRank['info'],
            'categories' => $pageRank['categories'],
            'quality' => $pageRank['quality'],
        ];

        return new JsonResponse($response, Response::HTTP_OK, $headers);
    }

    public function pageRankBatch(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return new Response(json_last_error_msg(), Response::HTTP_BAD_REQUEST);
        }
        if (!isset($data['urls'])) {
            return new Response('Field `urls` is required', Response::HTTP_BAD_REQUEST);
        }
        $urls = $data['urls'];
        if (!is_array($urls)) {
            return new Response('Field `urls` must be an array', Response::HTTP_BAD_REQUEST);
        }

        $categoriesMap = SiteCategory::getCategoryValueToIncludedCategoriesValuesMap(
            SiteCategory::getTaxonomySiteCategories()
        );
        $result = [];
        foreach ($urls as $id => $urlData) {
            $url = $urlData['url'] ?? null;
            if (UrlValidator::isValid($url)) {
                $categories = $this->extractCategories($urlData['categories'] ?? null, $categoriesMap);
                $pageRank = $this->getPageRankWithNote($url, $categories);
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

    public function reassessmentBatch(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return new Response(json_last_error_msg(), Response::HTTP_BAD_REQUEST);
        }
        if (!isset($data['urls'])) {
            return new Response('Field `urls` is required', Response::HTTP_BAD_REQUEST);
        }
        $urls = $data['urls'];
        if (!is_array($urls)) {
            return new Response('Field `urls` must be an array', Response::HTTP_BAD_REQUEST);
        }

        $result = [];
        $idToDomain = [];
        $domainToReassessmentReason = [];
        foreach ($urls as $id => $urlData) {
            if (!isset($urlData['url'])) {
                return new Response('Field `urls[][url]` is required', Response::HTTP_BAD_REQUEST);
            }
            if (!isset($urlData['extra'])) {
                return new Response('Field `urls[][extra]` is required', Response::HTTP_BAD_REQUEST);
            }
            $extra = $urlData['extra'];
            if (!is_array($extra)) {
                return new Response('Field `urls[][extra]` must be an array', Response::HTTP_BAD_REQUEST);
            }
            if (!$extra) {
                return new Response('Field `urls[][extra]` must not be empty', Response::HTTP_BAD_REQUEST);
            }
            foreach ($extra as $extraEntry) {
                if (
                    !isset($extraEntry['reason']) || !isset($extraEntry['message'])
                    || !is_string($extraEntry['reason']) || !is_string($extraEntry['message'])
                ) {
                    return new Response('Field `urls[][extra]` is invalid', Response::HTTP_BAD_REQUEST);
                }
            }
            $extra = json_encode($extra);
            if (!$extra) {
                return new Response('Field `urls[][extra]` is invalid', Response::HTTP_BAD_REQUEST);
            }

            $url = $urlData['url'];
            if (!UrlValidator::isValid($url)) {
                $result[$id] = ['status' => PageInfo::REASSESSMENT_STATE_INVALID_URL];
                continue;
            }

            $domain = UrlNormalizer::normalizeHost($url);
            if (empty($domain)) {
                $result[$id] = ['status' => PageInfo::REASSESSMENT_STATE_INVALID_URL];
                continue;
            }

            $idToDomain[$id] = $domain;
            $domainToReassessmentReason[$domain] = $extra;
        }
        $rows = $this->pageInfo->fetchReassessmentData(array_values($idToDomain));
        $domainToRow = [];
        $now = new DateTimeImmutable();
        $dbTimezone = new DateTimeZone('+0000');
        foreach ($rows as $row) {
            if (null !== $row['reassess_reason']) {
                $domainToRow[$row['domain']] = ['status' => PageInfo::REASSESSMENT_STATE_PROCESSING];
                continue;
            }

            $reassessAvailableAt = new DateTimeImmutable($row['reassess_available_at'], $dbTimezone);

            if ($reassessAvailableAt > $now) {
                $domainToRow[$row['domain']] = [
                    'status' => PageInfo::REASSESSMENT_STATE_LOCKED,
                    'reassess_available_at' => $reassessAvailableAt->format(DateTimeImmutable::ATOM),
                ];
                continue;
            }

            $domain = $row['domain'];
            $updatedCount = $this->pageInfo->updateReassessment((int)$row['id'], $domainToReassessmentReason[$domain]);
            $domainToRow[$domain] = [
                'status' =>
                    $updatedCount > 0 ? PageInfo::REASSESSMENT_STATE_ACCEPTED : PageInfo::REASSESSMENT_STATE_ERROR,
            ];
        }
        foreach ($idToDomain as $id => $domain) {
            $result[$id] = $domainToRow[$domain] ?? ['status' => PageInfo::REASSESSMENT_STATE_NOT_REGISTERED];
        }

        return new JsonResponse($result);
    }

    private function getData(array $user, ParameterBag $params): array
    {
        $pageRank = $this->getPageRank($params);

        $keywords = [
            'user' => array_filter(['language' => $user['languages'] ?? null, 'country' => $user['country'] ?? null]),
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
        $expiryPeriod = (int)$_ENV['ADUSER_HUMAN_SCORE_EXPIRY_PERIOD'] * 2;

        if ($eventTime - $scoreTime <= $expiryPeriod) {
            $humanScore = $user['human_score'];
        }

        if ($this->requestInfo->isCrawler($params)) {
            $humanScore = 0;
        }

        return (float)($humanScore ?? $_ENV['ADUSER_DEFAULT_HUMAN_SCORE']);
    }

    private function getPageRank(ParameterBag $params): array
    {
        if (($requestUrl = $params->get('url')) !== null) {
            $pageRank = $this->pageInfo->getPageRank($requestUrl);
        } else {
            $this->logger->debug('Cannot find URL', $params->all());
        }

        return [
            'rank' => (float)($pageRank[0] ?? $_ENV['ADUSER_DEFAULT_PAGE_RANK']),
            'info' => $pageRank[1] ?? PageInfo::INFO_UNKNOWN,
            'categories' => $pageRank[2] ?? [Taxonomy::SITE_UNKNOWN],
            'quality' => $pageRank[3] ?? Taxonomy::SITE_UNKNOWN,
        ];
    }

    private function getPageRankWithNote(string $url, array $categories): array
    {
        $pageRank = $this->pageInfo->getPageRank($url, true);
        if ($pageRank === null) {
            $this->pageInfo->noteDomain($url, $categories);
            $pageRank = [
                0,
                PageInfo::INFO_UNKNOWN,
                $categories,
            ];
        }

        return [
            'rank' => (float)($pageRank[0] ?? 0),
            'info' => $pageRank[1] ?? PageInfo::INFO_UNKNOWN,
            'categories' => $pageRank[2] ?? [Taxonomy::SITE_UNKNOWN],
            'quality' => $pageRank[3] ?? Taxonomy::SITE_UNKNOWN,
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
                        u.human_score_time
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
                    'human_score_time' => $row['human_score_time'] !== null ? strtotime($row['human_score_time'])
                        : null,
                ];
            }
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }

        return $users;
    }

    private function extractCategories($categories, array $categoriesMap): array
    {
        if (!is_array($categories)) {
            return [Taxonomy::SITE_UNKNOWN];
        }

        $extractedCategories = [];
        foreach ($categories as $category) {
            if (Taxonomy::SITE_UNKNOWN === $category) {
                return [Taxonomy::SITE_UNKNOWN];
            }

            if (isset($categoriesMap[$category])) {
                array_push($extractedCategories, ...$categoriesMap[$category]);
            }
        }

        if (empty($extractedCategories)) {
            return [Taxonomy::SITE_UNKNOWN];
        }

        return array_values(array_unique($extractedCategories));
    }
}
