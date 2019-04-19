<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190419131052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE site (
                id MEDIUMINT NOT NULL AUTO_INCREMENT,
                url TEXT NOT NULL,
                keywords TEXT,
                description TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        ');

        $this->addSql('
            CREATE TABLE url_site_map (
                id MEDIUMINT NOT NULL AUTO_INCREMENT,
                url TEXT NOT NULL,
                site_id INT,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id)    
            );'
        );

    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP site');
        $this->addSql('DROP url_site_map');
    }
}
