<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PageInfo;
use App\Service\PageInfoProviderInterface;
use App\Service\RequestInfo;
use App\Service\Taxonomy;
use App\Utils\SiteCategory;
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
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v{version}", name="api_", requirements={"version": "1"})
 */
final class ApiController extends AbstractController
{
    private PageInfoProviderInterface $pageInfoProvider;
    private PageInfo $pageInfo;
    private RequestInfo $requestInfo;
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(
        PageInfoProviderInterface $pageInfoProvider,
        PageInfo $pageInfo,
        RequestInfo $requestInfo,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->pageInfoProvider = $pageInfoProvider;
        $this->pageInfo = $pageInfo;
        $this->requestInfo = $requestInfo;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @Route("/taxonomy",
     *     name="taxonomy",
     *     methods={"GET"}
     * )
     */
    public function taxonomy(): Response
    {
        return new JsonResponse($this->pageInfoProvider->getTaxonomy());
    }

    /**
     * @Route("/data/{adserver}/{tracking}",
     *     name="data",
     *     methods={"GET", "POST", "OPTIONS"},
     *     requirements={
     *         "adserver": "[a-zA-Z0-9_:.-]+",
     *         "tracking": "[a-zA-Z0-9_:.-]+"
     *     }
     * )
     */
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

    /**
     * @Route("/data/{adserver}",
     *     name="data_batch",
     *     methods={"POST"},
     *     requirements={
     *         "adserver": "[a-zA-Z0-9_:.-]+"
     *     }
     * )
     */
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

    /**
     * @Route("/page-rank/{url}",
     *     name="page_rank",
     *     methods={"GET", "OPTIONS"},
     *     requirements={
     *         "url": ".+"
     *     }
     * )
     */
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

    /**
     * @Route("/page-rank/{url}",
     *     name="page_rank_batch",
     *     methods={"POST"}
     * )
     */
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

    /**
     * @Route("/reassessment",
     *     name="reassessment_batch",
     *     methods={"POST"}
     * )
     */
    public function reassessmentBatch(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        return new JsonResponse($this->pageInfoProvider->reassessment($data));
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
