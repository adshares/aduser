<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190313105221 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create data request log table';
    }

    public function up(Schema $schema) : void
    {
        // data request log
        $this->addSql('CREATE TABLE data_log (
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tracking_id VARCHAR(64) NOT NULL,
            data TEXT NOT NULL,
            uri TEXT NOT NULL,
            attributes TEXT NOT NULL,
            headers MEDIUMTEXT NOT NULL,
            ip VARCHAR(40) NOT NULL,
            ips TEXT NOT NULL,
            port INT(11) NOT NULL,
            INDEX date (date),
	        INDEX tracking_id (tracking_id)
        )');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE data_log');
    }
}
