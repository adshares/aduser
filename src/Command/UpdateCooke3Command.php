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

use App\Service\Cookie3;
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
use Throwable;

#[AsCommand(name: 'ops:cookie3:update')]
class UpdateCooke3Command extends Command
{
    use LockableTrait;

    private const DEFAULT_EXPIRATION = 3;

    private const DEFAULT_LIMIT = 500;

    public function __construct(
        private readonly Connection $connection,
        private readonly Cookie3 $cookie3,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Update users\' tags from Cookie3')
            ->setHelp('This command allows you to update users\' tags from Cookie3 service')
            ->addOption(
                'expiration',
                't',
                InputOption::VALUE_OPTIONAL,
                'Wallet tags expiration time (days)',
                self::DEFAULT_EXPIRATION
            )
            ->addOption(
                'package-limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'How many wallets we want to fetch in one sql query?',
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

        $expiration = (int)$input->getOption('expiration');
        $limit = (int)$input->getOption('package-limit');

        $fetchedWallets = 0;
        $updatedWallets = 0;
        $date = (new DateTime(sprintf('-%s days', $expiration)))->format('Y-m-d H:i:s');

        $io->info(sprintf('Started updating wallets older than %s...', $date));

        $query = sprintf(
            'SELECT id, address FROM cookie3_wallets
            WHERE id > :id AND status = %d OR (status = %d AND updated_at < visited_at AND updated_at <= :updated_at)
            ORDER BY id ASC
            LIMIT %d',
            Cookie3::STATUS_PENDING,
            Cookie3::STATUS_READY,
            $limit
        );

        try {
            $lastId = 0;
            do {
                $info = sprintf('Fetching %d wallets (already fetched: %d).', $limit, $fetchedWallets);
                $this->logger->debug($info);
                if ($io->isVerbose()) {
                    $io->comment($info);
                }
                $rows = $this->connection->fetchAllAssociative($query, ['id' => $lastId, 'updated_at' => $date]);
                $fetchedWallets += count($rows);
                foreach ($rows as $row) {
                    $lastId = (int)$row['id'];
                    try {
                        if (null !== $this->cookie3->updateTags($row['address'], false, (int)$row['id'])) {
                            ++$updatedWallets;
                        }
                    } catch (Throwable $exception) {
                        $io->error($exception->getMessage());
                        $this->logger->error($exception->getMessage());
                    }
                }
            } while (count($rows) === $limit);
        } catch (DBALException $exception) {
            $io->error($exception->getMessage());
            $this->release();
            return self::FAILURE;
        }

        if ($fetchedWallets === 0) {
            $io->warning('No wallets to update.');
        } else {
            $io->success(sprintf('Updated %d from %d wallets.', $updatedWallets, $fetchedWallets));
        }
        $this->release();

        return self::SUCCESS;
    }
}
