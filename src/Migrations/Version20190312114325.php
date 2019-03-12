<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190312114325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // user mapping
        $this->addSql('CREATE TABLE user_map (
            adserver_id VARCHAR(255) NOT NULL,
            adserver_user_id VARCHAR(255) NOT NULL,
            tracking_id VARCHAR(64) NOT NULL,
            PRIMARY KEY (adserver_id, adserver_user_id),
            INDEX tracking_id (tracking_id)
        )');

        // pixel registration log
        $this->addSql('CREATE TABLE pixel_log (
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            uri TEXT NOT NULL,
            attributes TEXT NOT NULL,
            query TEXT NOT NULL,
            headers MEDIUMTEXT NOT NULL,
            cookies MEDIUMTEXT NOT NULL,
            ip VARCHAR(40) NOT NULL,
            ips TEXT NOT NULL,
            port INT(11) NOT NULL,
            INDEX date (date),
	        INDEX tracking_id (tracking_id)
        )');

        // data provider registration log
        $this->addSql('CREATE TABLE provider_log (
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            uri TEXT NOT NULL,
            attributes TEXT NOT NULL,
            query TEXT NOT NULL,
            headers MEDIUMTEXT NOT NULL,
            cookies MEDIUMTEXT NOT NULL,
            ip VARCHAR(40) NOT NULL,
            ips TEXT NOT NULL,
            port INT(11) NOT NULL,
            INDEX date (date),
	        INDEX tracking_id (tracking_id)
        )');

        // simple data provider log
        $this->addSql('CREATE TABLE sim_log (
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            uri TEXT NOT NULL,
            attributes TEXT NOT NULL,
            query TEXT NOT NULL,
            headers MEDIUMTEXT NOT NULL,
            cookies MEDIUMTEXT NOT NULL,
            ip VARCHAR(40) NOT NULL,
            ips TEXT NOT NULL,
            port INT(11) NOT NULL,
            INDEX date (date),
	        INDEX tracking_id (tracking_id)
        )');

        // reCaptcha data provider log
        $this->addSql('CREATE TABLE rec_log (
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            uri TEXT NOT NULL,
            attributes TEXT NOT NULL,
            query TEXT NOT NULL,
            headers MEDIUMTEXT NOT NULL,
            cookies MEDIUMTEXT NOT NULL,
            ip VARCHAR(40) NOT NULL,
            ips TEXT NOT NULL,
            port INT(11) NOT NULL,
            INDEX date (date),
	        INDEX tracking_id (tracking_id)
        )');

        // reCaptcha score
        $this->addSql('CREATE TABLE rec_score (
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            score FLOAT NOT NULL,
            success TINYINT(4) NOT NULL,
            data TEXT NOT NULL,
            INDEX date (date),
	        INDEX tracking_id (tracking_id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_map');
        $this->addSql('DROP TABLE pixel_log');
        $this->addSql('DROP TABLE provider_log');
        $this->addSql('DROP TABLE sim_log');
        $this->addSql('DROP TABLE rec_log');
        $this->addSql('DROP TABLE rec_score');
    }
}
