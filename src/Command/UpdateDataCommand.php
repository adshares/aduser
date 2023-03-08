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

use App\Service\Browscap;
use App\Service\Cleaner;
use App\Service\PageInfo;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ops:update')]
class UpdateDataCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private readonly Browscap $browscap,
        private readonly Cleaner $cleaner,
        private readonly PageInfo $pageInfo,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Update Aduser data')
            ->setHelp('This command allows you to update data used by Aduser')
            ->addOption('full', 'f', InputOption::VALUE_NONE, 'Perform full update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');
            return self::FAILURE;
        }

        $io->comment('Updating pages...');

        $changedAfter = null;
        if (!$input->getOption('full')) {
            $changedAfter = new DateTimeImmutable('-24 hours');
        }

        if ($this->pageInfo->update($changedAfter)) {
            $io->success('Pages successfully updated!');
        } else {
            $io->error('Pages updated with errors');
        }

        $io->comment('Updating Browscap...');

        if ($this->browscap->update()) {
            $io->success('Browscap successfully updated!');
        } else {
            $io->error('Browscap updated with errors');
        }

        $io->comment('Clearing database...');

        if ($this->cleaner->clearDatabase()) {
            $io->success('database successfully cleared!');
        } else {
            $io->error('Database cleared with errors');
        }

        $this->release();
        return self::SUCCESS;
    }
}
