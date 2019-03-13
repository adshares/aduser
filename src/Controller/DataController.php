<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Controller;

use Adshares\Aduser\DataProvider\DataProviderInterface;
use Adshares\Aduser\DataProvider\DataProviderManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DataController extends AbstractController
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

    public function taxonomy(): Response
    {
        $data = [];
        foreach ($this->providers as $provider) {
            $data = array_merge($data, $provider->getTaxonomy());
        }

        $taxonomy = [
            'meta' => [
                'name' => getenv('TAXONOMY_NAME'),
                'version' => getenv('TAXONOMY_VERSION'),
            ],
            'data' => $data,
        ];

        return new JsonResponse($taxonomy);
    }

    public function data(Request $request): Response
    {
        $trackingId = $this->getTrackingId(
            $request->get('adserver'),
            $request->get('user')
        );

        if ($trackingId === null) {
            throw new NotFoundHttpException(sprintf(
                'Cannot find user %s/%s',
                $request->get('adserver'),
                $request->get('user')
            ));
        }

        $humanScore = -1;
        $keywords = [];

        foreach ($this->providers as $provider) {
            $keywords = array_merge($keywords, $provider->getKeywords($trackingId));
            if (($score = $provider->getHumanScore($trackingId)) >= 0) {
                $humanScore = $humanScore < 0 ? $score : min($humanScore, $score);
            }
        }

        $this->logger->info(sprintf('Human score: %s -> %s', $trackingId, $humanScore));
        $this->logger->info(sprintf('Keywords: %s -> %s', $trackingId, json_encode($keywords)));

        $data = [
            'human_score' => $humanScore < 0 ? getenv('ADUSER_DEFAULT_HUMAN_SCORE') : $humanScore,
            'keywords' => $keywords,
        ];

        $this->logRequest($trackingId, $request, $data);

        return new JsonResponse($data);
    }

    private function getTrackingId(string $adserverId, string $userId): ?string
    {
        try {
            $trackingId = $this->connection->fetchColumn(
                'SELECT tracking_id FROM user_map WHERE adserver_id = ? AND adserver_user_id = ?',
                [
                    $adserverId,
                    $userId,
                ]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $trackingId = null;
        }

        return $trackingId;
    }

    private function logRequest(string $trackingId, Request $request, array $data): void
    {
        $this->logger->debug(sprintf(
            'Data log: %s -> %s: %s',
            $trackingId,
            $request,
            json_encode($data)
        ));

        try {
            $this->connection->insert(
                'data_log',
                [
                    'tracking_id' => $trackingId,
                    'data' => json_encode($data),
                    'uri' => $request->getRequestUri(),
                    'attributes' => json_encode($request->attributes->get('_route_params')),
                    'headers' => json_encode($request->headers->all()),
                    'ip' => $request->getClientIp(),
                    'ips' => json_encode($request->getClientIps()),
                    'port' => (int)$request->getPort(),
                ]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
