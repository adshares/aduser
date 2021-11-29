<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190520094213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactors indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pixel_log
            DROP INDEX date,
            ADD COLUMN id BIGINT NOT NULL AUTO_INCREMENT FIRST,
            ADD PRIMARY KEY (id)
        ');

        $this->addSql('ALTER TABLE recaptcha_score
            DROP INDEX date,
            ADD COLUMN id BIGINT NOT NULL AUTO_INCREMENT FIRST,
            ADD PRIMARY KEY (id)
        ');

        $this->addSql('ALTER TABLE location
            DROP INDEX tracking_id,
            DROP INDEX country,
            DROP INDEX ip,
            ADD UNIQUE INDEX tracking_id_country_ip (tracking_id, country, ip)
        ');

        $this->addSql('ALTER TABLE tracking
            DROP INDEX tracking_id,
            ADD UNIQUE INDEX tracking_id (tracking_id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pixel_log
        	DROP COLUMN id,
            ADD INDEX date (date)
        ');

        $this->addSql('ALTER TABLE recaptcha_score
        	DROP COLUMN id,
            ADD INDEX date (date)
        ');

        $this->addSql('ALTER TABLE location
            DROP INDEX tracking_id_country_ip,
            ADD INDEX tracking_id (tracking_id),
            ADD INDEX country (country),
            ADD INDEX ip (ip)
        ');

        $this->addSql('ALTER TABLE tracking
            DROP INDEX tracking_id,
            ADD INDEX tracking_id (tracking_id)
        ');
    }
}
