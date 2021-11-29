<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190312114325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Inits database schema';
    }

    public function up(Schema $schema): void
    {
        // user mapping
        $this->addSql(
            'CREATE TABLE user_map (
            adserver_id VARCHAR(255) NOT NULL,
            adserver_user_id VARCHAR(255) NOT NULL,
            tracking_id VARCHAR(64) NOT NULL,
            PRIMARY KEY (adserver_id, adserver_user_id),
            INDEX tracking_id (tracking_id)
        )'
        );

        // pixel registration log
        $this->addSql(
            'CREATE TABLE pixel_log (
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            uri TEXT NOT NULL,
            attributes TEXT NOT NULL,
            query TEXT NOT NULL,
            request TEXT NOT NULL,
            headers MEDIUMTEXT NOT NULL,
            cookies MEDIUMTEXT NOT NULL,
            ip VARCHAR(40) NOT NULL,
            port INT(11) NOT NULL,
            INDEX date (date),
	        INDEX tracking_id (tracking_id)
        )'
        );

        // data provider registration log
        $this->addSql(
            'CREATE TABLE provider_log (
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            uri TEXT NOT NULL,
            attributes TEXT NOT NULL,
            query TEXT NOT NULL,
            request TEXT NOT NULL,
            headers MEDIUMTEXT NOT NULL,
            cookies MEDIUMTEXT NOT NULL,
            ip VARCHAR(40) NOT NULL,
            port INT(11) NOT NULL,
            INDEX date (date),
	        INDEX tracking_id (tracking_id)
        )'
        );

        // reCaptcha score
        $this->addSql(
            'CREATE TABLE recaptcha_score (
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            score FLOAT NOT NULL,
            success TINYINT(1) NOT NULL,
            data TEXT NOT NULL,
            INDEX date (date),
	        INDEX tracking_id (tracking_id)
        )'
        );

        // data request log
        $this->addSql(
            'CREATE TABLE data_log (
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            data TEXT NOT NULL,
            uri TEXT NOT NULL,
            attributes TEXT NOT NULL,
            query TEXT NOT NULL,
            request TEXT NOT NULL,
            headers MEDIUMTEXT NOT NULL,
            ip VARCHAR(40) NOT NULL,
            port INT(11) NOT NULL,
            INDEX date (date),
	        INDEX tracking_id (tracking_id)
        )'
        );

        // user
        $this->addSql(
            'CREATE TABLE user (
            id BIGINT NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_id VARCHAR(64) NOT NULL,
            mapped_user_id VARCHAR(64),
            human_score FLOAT NOT NULL DEFAULT 0.0,
            PRIMARY KEY (id),
            INDEX user_id (user_id) USING HASH
        )'
        );

        // tracking
        $this->addSql(
            'CREATE TABLE tracking (
            id BIGINT NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            user_id VARCHAR(64) NOT NULL,
            hash VARCHAR(32) NULL,
            user_agent VARCHAR(256) NULL,
            accept VARCHAR(256) NULL,
            accept_encoding VARCHAR(256) NULL,
            accept_language VARCHAR(256) NULL,
            language VARCHAR(8) NULL,
            color_depth INT(11) NULL,
            device_memory INT(11) NULL,
            hardware_concurrency INT(11) NULL,
            screen_resolution VARCHAR(16) NULL,
            available_screen_resolution VARCHAR(16) NULL,
            timezone_offset INT(11) NULL,
            timezone VARCHAR(64) NULL,
            session_storage TINYINT(1) NULL,
            local_storage TINYINT(1) NULL,
            indexed_db TINYINT(1) NULL,
            add_behavior TINYINT(1) NULL,
            open_database TINYINT(1) NULL,
            cpu_class VARCHAR(16) NULL,
            platform VARCHAR(64) NULL,
            plugins TEXT NULL,
            canvas VARCHAR(32) NULL,
            webgl VARCHAR(32) NULL,
            webgl_vendor_and_renderer VARCHAR(256) NULL,
            ad_block TINYINT(1) NULL,
            has_lied_languages TINYINT(1) NULL,
            has_lied_resolution TINYINT(1) NULL,
            has_lied_os TINYINT(1) NULL,
            has_lied_browser TINYINT(1) NULL,
            touch_support VARCHAR(16) NULL,
            fonts TEXT NULL,
            audio VARCHAR(32) NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (user_id) REFERENCES user(user_id),
            INDEX user_id (user_id) USING HASH,
            INDEX tracking_id (tracking_id) USING HASH,
            INDEX hash (hash) USING HASH
        )'
        );

        // tracking_map
        $this->addSql(
            'CREATE TABLE tracking_map (
            tracking_id_a VARCHAR(64) NOT NULL,
            tracking_id_b VARCHAR(64) NOT NULL,
            INDEX tracking_id_a (tracking_id_a) USING HASH,
            INDEX tracking_id_b (tracking_id_b) USING HASH
        )'
        );

        // location
        $this->addSql(
            'CREATE TABLE location (
            id BIGINT NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            country VARCHAR(8) NOT NULL,
            ip VARCHAR(40) NOT NULL,
            count INT(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            INDEX tracking_id (tracking_id) USING HASH,
            INDEX country (country),
            INDEX ip (ip)
        )'
        );

        $this->addSql(
            '
            CREATE TABLE site (
                id BIGINT NOT NULL AUTO_INCREMENT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                url TEXT NOT NULL,
                keywords TEXT,
                description TEXT,
                PRIMARY KEY (id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        '
        );

        $this->addSql(
            '
            CREATE TABLE url_site_map (
                id BIGINT NOT NULL AUTO_INCREMENT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                url TEXT NOT NULL,
                site_id INT,
                PRIMARY KEY (id)    
            );'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_map');
        $this->addSql('DROP TABLE pixel_log');
        $this->addSql('DROP TABLE provider_log');
        $this->addSql('DROP TABLE recaptcha_score');
        $this->addSql('DROP TABLE data_log');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE tracking');
        $this->addSql('DROP TABLE tracking_map');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP site');
        $this->addSql('DROP url_site_map');
    }
}
