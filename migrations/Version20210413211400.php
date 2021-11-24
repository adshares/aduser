<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210413211400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sessions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            '
            CREATE TABLE sessions (
                sess_id VARCHAR(128) NOT NULL PRIMARY KEY,
                sess_data BLOB NOT NULL,
                sess_time INTEGER UNSIGNED NOT NULL,
                sess_lifetime INTEGER NOT NULL
            ) COLLATE utf8_bin;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sessions;');
    }
}
