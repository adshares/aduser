<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20210319184127 extends AbstractMigration
{
    private const DOMAINS_TO_PROCESS_LIMIT = 500;

    public function getDescription(): string
    {
        return 'Create statistics table and extract categories';
    }

    public function up(Schema $schema): void
    {
        $this->createStatisticsTables();
        $this->extractCategories();
    }

    public function down(Schema $schema): void
    {
        $this->revertExtractingCategories();
        $this->dropStatisticsTables();
    }

    private function createStatisticsTables(): void
    {
        $this->addSql(
            "
            CREATE TABLE statistics_servers (
                id INT NOT NULL AUTO_INCREMENT,
                url VARCHAR(255) NOT NULL,
                PRIMARY KEY (id)
            );"
        );

        $this->addSql(
            "
            CREATE TABLE statistics_updates (
                id INT NOT NULL AUTO_INCREMENT,
                statistics_date DATE NOT NULL,
                server_id INT NOT NULL,
                status TINYINT NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                INDEX statistics_date (statistics_date),
                INDEX status_index (status)
            );"
        );

        $this->addSql(
            "
            CREATE TABLE statistics (
                id BIGINT NOT NULL AUTO_INCREMENT,
                update_id INT NOT NULL,
                domain VARCHAR(255) NOT NULL,
                size VARCHAR(16) DEFAULT '' NOT NULL,
                country VARCHAR(8) NULL,
                views_all INT UNSIGNED DEFAULT 0 NOT NULL,
                impressions INT UNSIGNED DEFAULT 0 NOT NULL,
                views INT UNSIGNED DEFAULT 0 NOT NULL,
                views_unique INT UNSIGNED DEFAULT 0 NOT NULL,
                clicks_all INT UNSIGNED DEFAULT 0 NOT NULL,
                clicks INT UNSIGNED DEFAULT 0 NOT NULL,
                revenue_case BIGINT DEFAULT 0 NOT NULL,
                PRIMARY KEY (id),
                INDEX update_id (update_id),
                INDEX domain (domain),
                INDEX size (size),
                INDEX country (country)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        );
    }

    private function dropStatisticsTables(): void
    {
        $this->addSql('DROP TABLE statistics');
        $this->addSql('DROP TABLE statistics_updates');
        $this->addSql('DROP TABLE statistics_servers');
    }

    private function extractCategories(): void
    {
        $this->addSql(
            '
            CREATE TABLE page_ranks_categories (
                id BIGINT NOT NULL AUTO_INCREMENT,
                page_rank_id BIGINT NOT NULL,
                category VARCHAR(30) NOT NULL,
                PRIMARY KEY (id),
                INDEX page_rank_id (page_rank_id),
                INDEX category (category)
            );'
        );

        [$idMin, $idMax] = array_values($this->connection->fetchAssociative('SELECT MIN(id), MAX(id) FROM page_ranks'));
        if ($idMin === null || $idMax === null) {
            $this->write('Categories were not added. Probably table page_rank is empty.');

            return;
        }

        $idMin = (int)$idMin;
        $idMax = (int)$idMax;

        $idFrom = $idMin;
        $idTo = $idFrom + self::DOMAINS_TO_PROCESS_LIMIT - 1;

        while ($idFrom <= $idMax) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, categories FROM page_ranks WHERE id BETWEEN ? AND ?;',
                [$idFrom, $idTo],
                [Types::BIGINT, Types::BIGINT]
            );

            $idFrom += self::DOMAINS_TO_PROCESS_LIMIT;
            $idTo += self::DOMAINS_TO_PROCESS_LIMIT;

            if (!$rows) {
                continue;
            }

            $insertQuery = 'INSERT INTO page_ranks_categories(page_rank_id,category) VALUES ';
            foreach ($rows as $row) {
                $pageRankId = $row['id'];
                $categories = explode(',', str_replace(['[', ']', '"', ' '], '', $row['categories']));
                foreach ($categories as $category) {
                    $insertQuery .= "($pageRankId,'$category'),";
                }
            }
            $insertQuery = substr($insertQuery, 0, -1) . ';';

            $this->addSql($insertQuery);
        }
    }

    private function revertExtractingCategories(): void
    {
        $this->addSql('DROP TABLE page_ranks_categories');
    }
}
