<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200608170836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add categories to page_ranks ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            '
            ALTER TABLE page_ranks
                ADD COLUMN categories JSON NULL
            ;'
        );

        $this->addSql("UPDATE page_ranks SET categories = '[\"unknown\"]';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            '
            ALTER TABLE page_ranks
                DROP COLUMN categories
            ;'
        );
    }
}
