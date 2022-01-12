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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220112112553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes to users and adserver_register';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX updated_at ON users (updated_at)');
        $this->addSql('CREATE INDEX updated_at ON adserver_register (updated_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX updated_at ON users');
        $this->addSql('DROP INDEX updated_at ON adserver_register');
    }
}
