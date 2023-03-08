<?php

/**
 * Copyright (c) 2018-2023 Adshares sp. z o.o.
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

final class Version20230302105553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external_user_id to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
ALTER TABLE users
    ADD COLUMN external_user_id VARCHAR(255) NULL COLLATE `utf8mb4_unicode_520_ci`
;
SQL
        );
        $this->addSql('CREATE INDEX external_user_id ON users (external_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
ALTER TABLE users
    DROP COLUMN external_user_id
;
SQL
        );
    }
}
