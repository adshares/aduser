<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190410131920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // user
        $this->addSql(
            'CREATE TABLE user (
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_id VARCHAR(64) NOT NULL,
            human_score FLOAT NOT NULL DEFAULT 0.0,
            PRIMARY KEY (user_id)
        )'
        );

        // tracking
        $this->addSql(
            'CREATE TABLE tracking (
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
            timezone VARCHAR(16) NULL,
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
            PRIMARY KEY (tracking_id),
            FOREIGN KEY (user_id) REFERENCES user(user_id),
            INDEX user_id (user_id)
        )'
        );

        // tracking_map
        $this->addSql(
            'CREATE TABLE tracking_map (
            tracking_id_a VARCHAR(64) NOT NULL,
            tracking_id_b VARCHAR(64) NOT NULL,
            FOREIGN KEY (tracking_id_a) REFERENCES tracking(tracking_id),
            FOREIGN KEY (tracking_id_b) REFERENCES tracking(tracking_id),
            INDEX tracking_id_a (tracking_id_a),
            INDEX tracking_id_b (tracking_id_b)
        )'
        );

        // location
        $this->addSql(
            'CREATE TABLE location (
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            country VARCHAR(8) NOT NULL,
            ip VARCHAR(40) NOT NULL,
            count INT(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (tracking_id, ip, country),
            FOREIGN KEY (tracking_id) REFERENCES tracking(tracking_id),
            INDEX country (country),
            INDEX ip (ip)
        )'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE tracking');
        $this->addSql('DROP TABLE tracking_map');
        $this->addSql('DROP TABLE location');
    }
}
