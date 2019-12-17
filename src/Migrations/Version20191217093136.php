<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191217093136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Database refactor';
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
        $this->addSql('DROP TABLE url_site_map');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_map');

        // users
        $this->addSql(
            '
            CREATE TABLE users (
                id BIGINT NOT NULL AUTO_INCREMENT,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
                tracking_id VARBINARY(16) NOT NULL,
                country VARCHAR(8) NOT NULL,
                languages JSON NOT NULL,
                human_score FLOAT NULL DEFAULT NULL,
                human_score_time TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX tracking_id (tracking_id),
                INDEX human_score (human_score)
            )'
        );

        // user <-> adserver mapping
        $this->addSql(
            '
            CREATE TABLE adserver_register (
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

        $this->addSql(
            '
            CREATE TABLE page_ranks (
                id BIGINT NOT NULL AUTO_INCREMENT,
                url VARCHAR(512) NOT NULL,
                rank DECIMAL(3,2) NULL DEFAULT NULL,
                info VARCHAR(64) NULL DEFAULT NULL,
                status TINYINT NOT NULL DEFAULT 1,
                dns_created_at TIMESTAMP NULL DEFAULT NULL,
                google_results INT NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
                PRIMARY KEY (id),
                UNIQUE INDEX url (url)
            ) COLLATE="utf8_general_ci";'
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

        $this->addSql(
            '
            CREATE TABLE user (
                id INT NOT NULL AUTO_INCREMENT,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                roles JSON NOT NULL,
                full_name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX email (email)
            );'
        );

        $this->addSql(
            '
            CREATE TABLE api_key (
                id INT NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                name VARCHAR(11) NOT NULL,
                secret VARCHAR(22) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX name (name),
                INDEX user_id (user_id)
            );'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE adserver_register');
        $this->addSql('DROP TRIGGER page_ranks_before_insert');
        $this->addSql('DROP TRIGGER page_ranks_before_update');
        $this->addSql('DROP TABLE page_ranks');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE api_key');
    }
}
