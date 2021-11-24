<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210921112903 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quality to page_ranks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            '
            ALTER TABLE page_ranks
                ADD COLUMN quality VARCHAR(16) NULL
            ;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            '
            ALTER TABLE page_ranks
                DROP COLUMN quality
            ;'
        );
    }
}
