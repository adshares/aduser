<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211124160348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove panel & stats';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE api_key');
        $this->addSql('DROP TABLE sessions');
        $this->addSql('DROP TABLE statistics');
        $this->addSql('DROP TABLE statistics_servers');
        $this->addSql('DROP TABLE statistics_updates');
        $this->addSql('DROP TABLE page_ranks_categories');
        $this->addSql('ALTER TABLE page_ranks
                DROP COLUMN url_full,
                DROP COLUMN status,
                DROP COLUMN dns_created_at,
                DROP COLUMN google_results,
                DROP COLUMN reassess_reason,
                DROP COLUMN reassess_available_at');
        $this->addSql('DROP TRIGGER page_ranks_before_insert');
        $this->addSql('DROP TRIGGER page_ranks_before_update');
    }

    public function down(Schema $schema): void
    {
        // no way back
    }
}
