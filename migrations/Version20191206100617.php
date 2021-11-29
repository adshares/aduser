<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191206100617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Site verification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            '
            ALTER TABLE page_ranks 
                CHANGE COLUMN rank rank DECIMAL(3,2) NULL DEFAULT NULL,
                ADD COLUMN info VARCHAR(64) NULL DEFAULT NULL,
                ADD COLUMN status TINYINT NOT NULL DEFAULT 1,
                ADD COLUMN dns_created_at TIMESTAMP NULL DEFAULT NULL,
                ADD COLUMN google_results INT NULL DEFAULT NULL,
                COLLATE="utf8_general_ci"
            ;'
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
        $this->addSql('ALTER TABLE page_ranks DROP COLUMN info');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE api_key');
    }
}
