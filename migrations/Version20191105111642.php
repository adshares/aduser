<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191105111642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates page ranks table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            '
            CREATE TABLE page_ranks (
                id BIGINT NOT NULL AUTO_INCREMENT,
                url VARCHAR(512) NOT NULL,
                rank FLOAT NOT NULL DEFAULT 1.0,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
                PRIMARY KEY (id),
                UNIQUE INDEX url (url)
            );'
        );

        $this->addSql(
            '
            CREATE TRIGGER page_ranks_before_insert
            BEFORE INSERT ON page_ranks FOR EACH ROW
            BEGIN
               SET NEW.url = TRIM(LEADING "www." FROM SUBSTRING_INDEX(
                    TRIM(TRAILING "/" FROM SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(NEW.url), "#", 1), "?", 1))
                , "://", -1));
            END;
        '
        );

        $this->addSql(
            '
            CREATE TRIGGER page_ranks_before_update
            BEFORE UPDATE ON page_ranks FOR EACH ROW
            BEGIN
               SET NEW.url = TRIM(LEADING "www." FROM SUBSTRING_INDEX(
                    TRIM(TRAILING "/" FROM SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(NEW.url), "#", 1), "?", 1))
                , "://", -1));
            END;
        '
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER page_ranks_before_insert');
        $this->addSql('DROP TRIGGER page_ranks_before_update');
        $this->addSql('DROP TABLE page_ranks');
    }
}
