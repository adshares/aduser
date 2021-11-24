<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191126131122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor database structure';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE data_log');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE pixel_log');
        $this->addSql('DROP TABLE provider_log');
        $this->addSql('DROP TABLE recaptcha_score');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP TABLE tracking_map');
        $this->addSql('DROP TABLE tracking');
        $this->addSql('DROP TABLE tracking_stats');
        $this->addSql('DROP TABLE url_site_map');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_map');

        // users
        $this->addSql(
            'CREATE TABLE users (
            id BIGINT NOT NULL AUTO_INCREMENT,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
            tracking_id VARBINARY(16) NOT NULL,
            country VARCHAR(8) NOT NULL,
            languages JSON NOT NULL,
            human_score FLOAT NULL DEFAULT NULL,
            human_score_time TIMESTAMP NULL DEFAULT NULL,
            fingerprint VARBINARY(20) NULL DEFAULT NULL,
            fingerprint_time TIMESTAMP NULL DEFAULT NULL,
            mapped_user_id INT NULL DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX tracking_id (tracking_id),
            INDEX human_score (human_score),
            INDEX fingerprint (fingerprint),
            INDEX fingerprint_time (fingerprint_time),
            INDEX mapped_user_id (mapped_user_id)
        )'
        );

        // user <-> adserver mapping
        $this->addSql(
            'CREATE TABLE adserver_register (
            id BIGINT NOT NULL AUTO_INCREMENT,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
            adserver_id VARCHAR(255) NOT NULL,
            tracking_id VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            PRIMARY KEY (id),
            INDEX user_id (user_id),
            UNIQUE INDEX adserver_id_tracking_id (adserver_id, tracking_id)
        )'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE adserver_register');
    }
}
