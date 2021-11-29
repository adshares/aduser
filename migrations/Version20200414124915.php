<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200414124915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add url_full to page_ranks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            '
            ALTER TABLE page_ranks
                MODIFY url VARCHAR(512) CHARACTER SET utf8 NOT NULL,
                ADD COLUMN url_full VARCHAR(1024) NOT NULL AFTER url
            ;'
        );

        $this->addSql(
            '
            UPDATE page_ranks 
                SET url_full = CONCAT("https://", url)
            ;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page_ranks DROP COLUMN url_full');
    }
}
