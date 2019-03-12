<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Controller;

use Adshares\Aduser\Data\DataProviderInterface;
use Adshares\Aduser\Data\DataProviderManager;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

    public function data(): Response
    {
        return new JsonResponse('data');
    }
}
