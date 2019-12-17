<?php declare(strict_types = 1);

namespace Adshares\Aduser\Controller;

use Adshares\Aduser\Service\PageInfo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class PanelController extends AbstractController
{
    /** @var Connection */
    private $connection;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function index(Request $request): Response
    {
        $limit = 50;
        $page = max(1, (int)$request->query->get('page', 1));

        $ratedFilter = (bool)$request->get('rated', false);
        $urlFilter = $request->get('url');
        $infoFilter = $request->get('info');

        $sort = $request->get('sort');
        if (!in_array($sort, ['id', 'url', 'rank', 'created_at', 'dns_created_at', 'google_results'])) {
            $sort = 'id';
        }

        $order = $request->get('order');
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        $domains =
            $this->fetchDomains($ratedFilter, $urlFilter, $infoFilter, $limit, ($page - 1) * $limit, $sort, $order);

        return $this->render(
            'panel/index.html.twig',
            [
                'domains' => $domains['rows'],
                'currentPage' => $page,
                'totalPages' => ceil($domains['count'] / $limit),
                'ranks' => self::getRanks(),
                'reasons' => self::getReasons(),
            ]
        );
    }

    public function patch(int $id, Request $request): Response
    {
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Methods' => 'PATCH, OPTIONS',
            'Access-Control-Max-Age' => 24 * 3600,
        ];
        if ($request->isMethod('OPTIONS')) {
            return new Response('', 200, $headers);
        }

        $data = json_decode($request->getContent(), true);

        $rank = (float)($data['rank'] ?? 0);
        $info = $data['info'] ?? null;

        if ($rank < 0 || $rank > 1) {
            throw new UnprocessableEntityHttpException('Invalid rank ');
        }
        if (!array_key_exists($info, self::getReasons())) {
            throw new UnprocessableEntityHttpException('Invalid info ');
        }

        $result = $this->patchDomain($id, $rank, $info);

        return new Response('', $result ? 204 : 500, $headers);
    }

    private function fetchDomains(
        bool $showRated = true,
        ?string $url = null,
        ?string $info = null,
        int $limit = 100,
        int $offset = 0,
        string $sort = 'id',
        string $order = 'desc'
    ): array {
        $conditions = '1=1';
        $params = [
            ':limit' => $limit,
            ':offset' => $offset,
        ];
        $types = [
            ':limit' => Types::INTEGER,
            ':offset' => Types::INTEGER,
        ];

        if (!$showRated) {
            $conditions .= ' AND rank IS NULL';
        }
        if ($url !== null) {
            $conditions .= ' AND url LIKE :url';
            $params[':url'] = '%'.$url.'%';
            $types[':url'] = Types::STRING;
        }
        if ($info !== null) {
            $conditions .= ' AND info = :info';
            $params[':info'] = $info;
            $types[':info'] = Types::STRING;
        }

        $sortBy = sprintf(' ORDER BY %s %s', $sort, $order);
        $rowsQuery =
            'SELECT id, url, rank, info, created_at, dns_created_at, google_results FROM page_ranks WHERE '
            .$conditions.$sortBy
            .' LIMIT :offset, :limit';
        $countQuery = 'SELECT count(*) FROM page_ranks WHERE '.$conditions.$sortBy;

        return [
            'rows' => $this->connection->fetchAll($rowsQuery, $params, $types),
            'count' => $this->connection->fetchColumn($countQuery, $params, 0, $types),
        ];
    }

    private function patchDomain(int $id, float $rank, ?string $info): bool
    {
        try {
            $this->connection->update(
                'page_ranks',
                ['rank' => $rank, 'info' => $info, 'status' => PageInfo::STATUS_VERIFIED],
                ['id' => $id]
            );
        } catch (DBALException $exception) {
            $this->logger->error($exception->getMessage());

            return false;
        }

        return true;
    }

    private static function getReasons(): array
    {
        return [
            PageInfo::INFO_OK => 'OK',
            PageInfo::INFO_HIGH_IVR => 'High IVR',
            PageInfo::INFO_HIGH_CTR => 'High CTR',
            PageInfo::INFO_LOW_CTR => 'Low CTR',
            PageInfo::INFO_POOR_TRAFFIC => 'Poor traffic',
            PageInfo::INFO_POOR_CONTENT => 'Poor content',
            PageInfo::INFO_SUSPICIOUS_DOMAIN => 'Suspicious domain',
        ];
    }

    private static function getRanks(): array
    {
        return [0.9, 0.7, 0.5, 0.3, 0.1, 0.01];
    }
}
