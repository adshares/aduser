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

namespace App\Command;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ops:users:merge')]
class MergeUsersCommand extends Command
{
    use LockableTrait;

    private const DEFAULT_INTERVAL = 5;

    private const DEFAULT_LIMIT = 500;

    private string $currentTime;

    private array $hashCache = [];

    public function __construct(private readonly Connection $connection, private readonly LoggerInterface $logger)
    {
        $this->currentTime = (new DateTime())->format('Y-m-d H:i:s');

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Merge users by hashes')
            ->setHelp('This command is used to merge non unique users (different tracking_id) by hash')
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                'This parameter tells how many minutes we want to look back to the database to fetch trackings',
                self::DEFAULT_INTERVAL
            )
            ->addOption(
                'package-limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'How many trackings we want to fetch in one sql query?',
                self::DEFAULT_LIMIT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');
            return self::FAILURE;
        }

        $interval = (int)$input->getOption('interval');
        $limit = (int)$input->getOption('package-limit');
        $offset = 0;
        $mergedUsers = 0;
        $date = (new DateTime(sprintf('-%s minutes', $interval)))->format('Y-m-d H:i:s');

        $io->comment(sprintf('Started merging users from %s...', $date));

        $fetchQuery = <<<SQL
            SELECT id, fingerprint, human_score, human_score_time FROM users
            WHERE mapped_user_id IS NULL AND fingerprint is NOT NULL AND fingerprint_time > :created_at            
            LIMIT %d OFFSET %d
SQL;

        try {
            do {
                $query = sprintf($fetchQuery, $limit, $offset);

                $this->logger->debug(sprintf('Merging WITH LIMIT %d, OFFSET %d.', $limit, $offset));
                $rows = $this->connection->fetchAllAssociative($query, ['created_at' => $date]);
                $users = $this->findUsersToMerge($rows);

                if ($users) {
                    foreach ($users as $userId => $data) {
                        $mergedUsers += count($data);
                        $this->updateUserId($userId, $data);
                    }
                }

                $offset += $limit;
            } while (count($rows) === $limit);
        } catch (DBALException $exception) {
            $io->error($exception->getMessage());
            $this->release();
            return self::FAILURE;
        }

        if ($mergedUsers === 0) {
            $io->warning('No users to merge.');
        } else {
            $io->success(sprintf('Merged %d users.', $mergedUsers));
        }
        $this->release();

        return self::SUCCESS;
    }

    /**
     * @param array $rows
     *
     * @return array
     * @throws DBALException
     */
    protected function findUsersToMerge(array $rows): array
    {
        $users = [];
        foreach ($rows as $row) {
            if (!array_key_exists($row['fingerprint'], $this->hashCache)) {
                $this->hashCache[$row['fingerprint']] = $this->connection->fetchAssociative(
                    'SELECT id, fingerprint FROM users WHERE fingerprint = ? ORDER BY id LIMIT 1',
                    [$row['fingerprint']]
                );
            }

            $existedRow = $this->hashCache[$row['fingerprint']] ?? null;
            if (!$existedRow || $existedRow['id'] === $row['id']) {
                continue;
            }

            if ((int)$row['id'] > (int)$existedRow['id']) {
                $users[$existedRow['id']][] = $row;
            }
        }

        return $users;
    }

    protected function updateUserId(int $userId, array $userRowsToUpdate): void
    {
        if (empty($userRowsToUpdate)) {
            return;
        }

        try {
            $this->connection->beginTransaction();

            $ids = array_map(
                function ($row) {
                    return $row['id'];
                },
                $userRowsToUpdate
            );

            $updateUserQuery = sprintf(
                'UPDATE users SET mapped_user_id = :user_id WHERE id IN (%s)',
                implode(',', $ids)
            );

            $updateStmt = $this->connection->prepare($updateUserQuery);
            $updateStmt->bindParam('user_id', $userId);
            $updateStmt->executeQuery();

            $updateAdserverQuery = sprintf(
                'UPDATE adserver_register SET user_id = :user_id WHERE user_id IN (%s)',
                implode(',', $ids)
            );

            $updateStmt = $this->connection->prepare($updateAdserverQuery);
            $updateStmt->bindParam('user_id', $userId);
            $updateStmt->executeQuery();

            $humanScore = 0;
            $humanScoreTime = 0;

            foreach ($userRowsToUpdate as $row) {
                if ($row['human_score_time'] > $humanScoreTime) {
                    $humanScore = $row['human_score'];
                    $humanScoreTime = $row['human_score_time'];
                }
            }

            if ($humanScore > 0) {
                $this->connection->update(
                    'users',
                    [
                        'human_score' => $humanScore,
                        'human_score_time' => $humanScoreTime,
                    ],
                    ['id' => $userId]
                );
            }

            $this->connection->commit();
        } catch (DBALException $exception) {
            $this->logger->error($exception->getMessage());
            $this->connection->rollBack();
        }
    }
}
