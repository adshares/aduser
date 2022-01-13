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

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Throwable;

final class Cleaner
{
    private int $defaultInterval;
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(int $defaultInterval, Connection $connection, LoggerInterface $logger)
    {
        $this->defaultInterval = $defaultInterval;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function clearDatabase(?int $interval = null): bool
    {
        if (null === $interval) {
            $interval = $this->defaultInterval;
        }
        $this->logger->info(sprintf('Clearing database (%d hours)', $interval));
        try {
            $result = $this->connection->executeStatement(
                'DELETE FROM adserver_register WHERE updated_at <= NOW() - INTERVAL :interval HOUR',
                ['interval' => $interval]
            );
            $result += $this->connection->executeStatement(
                'DELETE FROM users WHERE updated_at <= NOW() - INTERVAL :interval HOUR',
                ['interval' => $interval]
            );
            $this->logger->info(sprintf('Removed %d records', $result));
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
        return true;
    }
}
