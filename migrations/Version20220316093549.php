<?php

/**
 * Copyright (c) 2018-2022 Adshares sp. z o.o.
 *
 * This file is part of AdUser
 *
 * AdUser is free software: you can redistribute and/or modify it
 * under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AdUser is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AdServer. If not, see <https://www.gnu.org/licenses/>
 */

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220316093549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Cookie3 data table';
    }

    public function up(Schema $schema): void
    {
        // user
        $this->addSql(
            'CREATE TABLE cookie3_wallets (
                id BIGINT NOT NULL AUTO_INCREMENT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                address VARCHAR(64) NOT NULL,
                status int NOT NULL DEFAULT 1,
                tags JSON NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX address (address)
            )'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cookie3_wallets');
    }
}
