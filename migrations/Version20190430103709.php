<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190430103709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds tracking stats';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE tracking_stats (
            name VARCHAR(32) NOT NULL,
            value VARCHAR(256) NOT NULL,
	        count BIGINT NOT NULL,
            share FLOAT NOT NULL,
            PRIMARY KEY (name, value)
        )'
        );

        $this->addSql(
            'UPDATE tracking SET
              plugins = SUBSTRING(plugins, 1, 32),
              fonts = SUBSTRING(fonts, 1, 32)
        '
        );

        $this->addSql(
            'ALTER TABLE tracking
            ADD COLUMN last_ip VARCHAR(40) NOT NULL DEFAULT "" AFTER hash,
            CHANGE COLUMN plugins plugins VARCHAR(32) NULL,
            CHANGE COLUMN fonts fonts VARCHAR(32) NULL
        '
        );

        $this->addSql(
            'UPDATE tracking t
            LEFT JOIN (
                SELECT tracking_id, IF(country = "T1", "t1", ip) as ip FROM location GROUP BY tracking_id
            ) c ON c.tracking_id = t.tracking_id
            SET t.last_ip = c.ip
            WHERE c.ip IS NOT NULL
        '
        );

        $this->addSql(
            'ALTER TABLE tracking
	        CHANGE COLUMN last_ip last_ip VARCHAR(40) NOT NULL;
        '
        );

        $this->addSql(
            'UPDATE tracking SET HASH =
            MD5(CONCAT(
              last_ip, user_agent, accept, accept_encoding, accept_language,language,color_depth,device_memory,
              hardware_concurrency,screen_resolution,available_screen_resolution,timezone_offset,timezone,
              session_storage,local_storage,indexed_db,add_behavior,open_database,cpu_class,platform,plugins,canvas,
              webgl,webgl_vendor_and_renderer,ad_block,has_lied_languages,has_lied_resolution,has_lied_os,
              has_lied_browser,touch_support,fonts,audio
            ))'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tracking_stats');
        $this->addSql(
            'ALTER TABLE tracking
            DROP COLUMN last_ip,
            CHANGE COLUMN plugins plugins TEXT NULL,
            CHANGE COLUMN fonts fonts TEXT NULL
        '
        );
    }
}
