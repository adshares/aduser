<?php

declare(strict_types=1);


namespace Adshares\Aduser\Command;

use Adshares\Aduser\SiteDataProvider\SiteProviderInterface;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Embed\Exceptions\EmbedException;
use Exception;
use function sprintf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSiteCommand extends Command
{
    private const NUMBER_OF_MINUTES = 10;

    protected static $defaultName = 'aduser:site:update';

    /** @var SiteProviderInterface */
    private $siteProvider;
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection, SiteProviderInterface $siteProvider)
    {
        parent::__construct();

        $this->connection = $connection;
        $this->siteProvider = $siteProvider;
    }


    protected function configure()
    {
        $this->setDescription('Update information about site')->setHelp(
            'This command allows you to fetch information about site'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Started updating site\'s information.');

        $urlSiteMap = [];

        try {
            $date = (new DateTime(sprintf('-%s minutes', self::NUMBER_OF_MINUTES)))->format('Y-m-d H:i:s');
            $query = 'SELECT id, url FROM url_site_map WHERE site_id is NULL AND created_at > :created_at';
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':created_at', $date);
            $stmt->execute();

            while ($row = $stmt->fetch(FetchMode::ASSOCIATIVE)) {
                $urlSiteMap[] = $row;
            }
        } catch (DBALException $e) {
            $output->writeln($e->getMessage());
            exit();
        }

        foreach ($urlSiteMap as $urlSite) {
            $url = $urlSite['url'];

            try {
                $site = $this->connection->fetchAssoc(
                    'SELECT url, id FROM site WHERE url = ?',
                    [$url]
                );

                if ($site !== false) {
                    $this->connection->update(
                        'url_site_map',
                        ['site_id' => $site['id']],
                        ['id' => $urlSite['id']]
                    );

                    continue; // do not duplicate in a table
                }
            } catch (DBALException $exception) {
                $output->writeln($exception->getMessage());
                continue;
            }

            try {
                $fetchedSite = $this->siteProvider->fetchSite($url);
            } catch (EmbedException $exception) {
                $output->writeln(sprintf(
                    'Could not fetch information about site (%s) - %s',
                    $url,
                    $exception->getMessage()
                ));

                continue;
            }

            $output->writeln(sprintf('Site (%s) has been fetched.', $url));
            try {
                $this->connection->insert(
                    'site',
                    [
                        'url' => $fetchedSite->getUrl(),
                        'created_at' => new DateTime(),
                        'updated_at' => new DateTime(),
                        'keywords' => $fetchedSite->getKeywords(),
                        'description' => $fetchedSite->getDescription(),
                    ],
                    [
                        'string',
                        'datetime',
                        'datetime',
                        'string',
                        'string',
                    ]
                );

                $id = $this->connection->lastInsertId();

                $this->connection->update(
                    'url_site_map',
                    ['site_id' => $id],
                    ['id' => $urlSite['id']]
                );

                $output->writeln(sprintf('Site (%s) has been stored.', $url));
            } catch (DBALException $e) {
                $output->writeln($e->getMessage());
                continue;
            }
        }

        $output->writeln('Finished updating site\'s information.');
    }
}
