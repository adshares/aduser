<?php declare(strict_types = 1);

namespace Adshares\Aduser\Controller;

use Adshares\Aduser\Service\PageInfo;
use Adshares\Aduser\Service\RequestInfo;
use Adshares\Aduser\Service\Taxonomy;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
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
    /** @var PageInfo */
    private $pageInfo;

    /** @var RequestInfo */
    private $requestInfo;

    /** @var Connection */
    private $connection;

    /** @var LoggerInterface */
    private $logger;

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
            return new Response('', 200, $headers);
        }

        $users = $this->getUsers($adserver, [$tracking]);
        $user = array_pop($users) ?? [];
        $params = $request->isMethod('GET') ? $request->query : $request->request;

        $this->logger->info(sprintf('Fetching data for %s:%s', $adserver, $tracking), $params->all());
        $this->logger->debug(sprintf('User: %s', $user['id'] ?? 'unknown'), $user);

        $data = $this->getData($user, $params);

        return new JsonResponse($data, 200, $headers);
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

    public function domain(string $domain, Request $request): Response
    {
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Max-Age' => 24 * 3600,
        ];
        if ($request->isMethod('OPTIONS')) {
            return new Response('', 200, $headers);
        }

        $pageRank = $this->getPageRankWithNote($domain);

        $response = [
            'rank' => $pageRank['rank'],
            'info' => $pageRank['info'],
        ];

        return new JsonResponse($response, 200, $headers);
    }

    private function getData(array $user, ParameterBag $params)
    {
        $keywords = [
            'user' => array_filter(['language' => $user['languages'] ?? null, 'country' => $user['country'] ?? null]),
            'device' => $this->requestInfo->getDeviceKeywords($params),
            'site' => $this->requestInfo->getSiteKeywords($params),
        ];

        $pageRank = $this->getPageRank($params);

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
        $rank = null;
        if (($requestUrl = $params->get('url')) !== null) {
            $pageRank = $this->pageInfo->getPageRank($requestUrl);
        } else {
            $this->logger->debug('Cannot find URL', $params->all());
        }

        return [
            'rank' => (float)($pageRank[0] ?? $_ENV['ADUSER_DEFAULT_PAGE_RANK']),
            'info' => $pageRank[1] ?? PageInfo::INFO_UNKNOWN,
        ];
    }

    private function getPageRankWithNote($domain): array
    {
        $pageRank = $this->pageInfo->getPageRank($domain);
        if ($pageRank === null) {
            $this->pageInfo->noteDomain($domain);
        }

        return [
            'rank' => (float)($pageRank[0] ?? 0),
            'info' => $pageRank[1] ?? PageInfo::INFO_UNKNOWN,
        ];
    }

    private function getUsers(string $adserverId, array $trackingIds): array
    {
        $users = [];
        try {
            foreach ($this->connection->fetchAll(
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
            ) as $row) {
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
}
