<?php declare(strict_types = 1);

namespace App\Command;

use App\Service\PageInfo;
use App\Utils\UrlNormalizer;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Types;
use Iodev\Whois\Loaders\SocketLoader;
use Iodev\Whois\Whois;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use function sprintf;

class ScanDomainsCommand extends Command
{
    use LockableTrait;

    const GOOGLE_SEARCH_URL = 'https://www.googleapis.com/customsearch/v1';

    protected static $defaultName = 'ops:domains:scan';

    /** @var Connection */
    private $connection;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Scan new domains')
            ->setHelp('This command is used to scan new domains')
            ->addArgument(
                'domain',
                InputArgument::IS_ARRAY,
                'List of domain to scan (default all new domains)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');

            return 1;
        }

        $domains = $input->getArgument('domain');
        $fromDb = false;
        if (empty($domains)) {
            $domains = $this->fetchDomains($io);
            $fromDb = true;
        }

        if (empty($domains)) {
            $io->warning('No domains to scan.');
            $this->release();

            return 1;
        }

        $count = $warnings = 0;
        foreach ($domains as $domain) {
            $domain = UrlNormalizer::normalizeHost($domain);
            $io->comment(sprintf('Scanning %s...', $domain));

            $whoisInfo = $this->fetchWhoisInfo($domain, $io);
            $googleInfo = $this->fetchGoogleInfo($domain, $io);

            if ($whoisInfo !== null || $googleInfo !== null) {
                $data = [
                    'status' => $googleInfo !== null ? PageInfo::STATUS_SCANNED : PageInfo::STATUS_NEW,
                    'dns_created_at' => $whoisInfo['created_at'] ?? null,
                    'google_results' => $googleInfo['results'] ?? null,
                ];
                $types = [
                    'status' => Types::INTEGER,
                    'dns_created_at' => Types::DATETIME_IMMUTABLE,
                    'google_results' => Types::INTEGER,
                ];
                try {
                    $id = $this->connection->fetchColumn('SELECT id FROM page_ranks WHERE url = ?', [$domain]);
                    if ($id === false) {
                        if (!$fromDb) {
                            $data['url'] = $domain;
                            $this->connection->insert('page_ranks', $data, $types);
                        }
                    } else {
                        $this->connection->update('page_ranks', $data, ['id' => $id], $types);
                    }
                } catch (DBALException $exception) {
                    $io->error($exception->getMessage());
                }
            }
            if ($whoisInfo === null || $googleInfo === null) {
                ++$warnings;
            }
            ++$count;
        }

        $io->success(sprintf('Scanned %d domains (%d incomplete)', $count, $warnings));
        $this->release();

        return 0;
    }

    private function fetchDomains(SymfonyStyle $io): array
    {
        return array_map(
            function ($row) {
                return $row['url'];
            },
            $this->connection->fetchAll('SELECT url from page_ranks WHERE status = ?', [PageInfo::STATUS_NEW])
        );
    }

    private function fetchWhoisInfo(string $host, SymfonyStyle $io): ?array
    {
        try {
            $createdAt = null;
            $info = Whois::create(new SocketLoader(2))->loadDomainInfo($host);
            if ($info !== null && $info->getCreationDate() > 0) {
                $createdAt = new DateTimeImmutable('@'.$info->getCreationDate());
            }

            return ['created_at' => $createdAt];
        } catch (\Exception $exception) {
            $io->warning($exception->getMessage());

            return null;
        }
    }

    private function fetchGoogleInfo(string $host, SymfonyStyle $io): ?array
    {
        $googleCx = (string)($_ENV['GOOGLE_SEARCH_CX'] ?? '');
        $googleKey = (string)($_ENV['GOOGLE_SEARCH_KEY'] ?? '');

        if (empty($googleCx) || empty($googleKey)) {
            return [];
        }

        try {
            $url = sprintf(
                '%s?%s',
                self::GOOGLE_SEARCH_URL,
                http_build_query(
                    [
                        'num' => 1,
                        'cx' => $googleCx,
                        'key' => $googleKey,
                        'q' => sprintf('site:%s', $host),
                    ]
                )
            );
            $client = HttpClient::create();
            $response = $client->request('GET', $url)->toArray();
            $results = $response['queries']['request'][0]['totalResults'] ?? null;

            return ['results' => $results !== null ? (int)$results : null];
        } catch (ExceptionInterface $exception) {
            $io->warning($exception->getMessage());

            return null;
        }
    }
}
