<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230302105553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external_account_id to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
ALTER TABLE users
    ADD COLUMN external_account_id VARCHAR(255) NULL COLLATE `utf8mb4_unicode_520_ci`
;
SQL
        );
        $this->addSql('CREATE INDEX external_account_id ON users (external_account_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
ALTER TABLE users
    DROP COLUMN external_account_id
;
SQL
        );
    }
}
