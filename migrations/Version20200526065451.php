<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200526065451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reassessment to page_ranks ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            '
            ALTER TABLE page_ranks
                ADD COLUMN reassess_reason TEXT NULL DEFAULT NULL,
                ADD COLUMN reassess_available_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ;'
        );

        $this->addSql(
            '
            UPDATE page_ranks 
                SET reassess_available_at = NOW() + INTERVAL FLOOR(RAND() * 8) DAY
            ;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            '
            ALTER TABLE page_ranks
                DROP COLUMN reassess_reason,
                DROP COLUMN reassess_available_at
            ;'
        );
    }
}
